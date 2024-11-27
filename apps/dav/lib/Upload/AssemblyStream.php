<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Upload;

use Sabre\DAV\IFile;

/**
 * Class AssemblyStream
 *
 * The assembly stream is a virtual stream that wraps multiple chunks.
 * Reading from the stream transparently accessed the underlying chunks and
 * give a representation as if they were already merged together.
 *
 * @package OCA\DAV\Upload
 */
class AssemblyStream implements \Icewind\Streams\File {

	/** @var resource */
	private $context;

	/** @var IFile[] */
	private $nodes;

	/** @var int */
	private $pos = 0;

	/** @var int */
	private $size = 0;

	/** @var resource */
	private $currentStream = null;

	/** @var int */
	private $currentNode = 0;

	/** @var int */
	private $currentNodeRead = 0;

	/**
	 * @param string $path
	 * @param string $mode
	 * @param int $options
	 * @param string &$opened_path
	 * @return bool
	 */
	public function stream_open($path, $mode, $options, &$opened_path) {
		$this->loadContext('assembly');

		$nodes = $this->nodes;
		usort($nodes, function (IFile $a, IFile $b) {
			return strnatcmp($a->getName(), $b->getName());
		});
		$this->nodes = array_values($nodes);
		$this->size = array_reduce($this->nodes, function ($size, IFile $file) {
			return $size + $file->getSize();
		}, 0);

		return true;
	}

	/**
	 * @param int $offset
	 * @param int $whence
	 * @return bool
	 */
	public function stream_seek($offset, $whence = SEEK_SET) {
		if ($whence === SEEK_CUR) {
			$offset = $this->stream_tell() + $offset;
		} elseif ($whence === SEEK_END) {
			$offset = $this->size + $offset;
		}

		if ($offset === $this->pos) {
			return true;
		}

		if ($offset > $this->size) {
			return false;
		}

		$nodeIndex = 0;
		$nodeStart = 0;
		while (true) {
			if (!isset($this->nodes[$nodeIndex + 1])) {
				break;
			}
			$node = $this->nodes[$nodeIndex];
			if ($nodeStart + $node->getSize() > $offset) {
				break;
			}
			$nodeIndex++;
			$nodeStart += $node->getSize();
		}

		$stream = $this->getStream($this->nodes[$nodeIndex]);
		$nodeOffset = $offset - $nodeStart;
		if ($nodeOffset > 0 && fseek($stream, $nodeOffset) === -1) {
			return false;
		}
		$this->currentNode = $nodeIndex;
		$this->currentNodeRead = $nodeOffset;
		$this->currentStream = $stream;
		$this->pos = $offset;

		return true;
	}

	/**
	 * @return int
	 */
	public function stream_tell() {
		return $this->pos;
	}

	/**
	 * @param int $count
	 * @return string
	 */
	public function stream_read($count) {
		if (is_null($this->currentStream)) {
			if ($this->currentNode < count($this->nodes)) {
				$this->currentStream = $this->getStream($this->nodes[$this->currentNode]);
			} else {
				return '';
			}
		}

		$collectedData = '';
		// read data until we either got all the data requested or there is no more stream left
		while ($count > 0 && !is_null($this->currentStream)) {
			$data = fread($this->currentStream, $count);
			$read = strlen($data);

			$count -= $read;
			$collectedData .= $data;
			$this->currentNodeRead += $read;

			if (feof($this->currentStream)) {
				fclose($this->currentStream);
				$currentNodeSize = $this->nodes[$this->currentNode]->getSize();
				if ($this->currentNodeRead < $currentNodeSize) {
					throw new \Exception('Stream from assembly node shorter than expected, got ' . $this->currentNodeRead . ' bytes, expected ' . $currentNodeSize);
				}
				$this->currentNode++;
				$this->currentNodeRead = 0;
				if ($this->currentNode < count($this->nodes)) {
					$this->currentStream = $this->getStream($this->nodes[$this->currentNode]);
				} else {
					$this->currentStream = null;
				}
			}
		}

		// update position
		$this->pos += strlen($collectedData);
		return $collectedData;
	}

	/**
	 * @param string $data
	 * @return int
	 */
	public function stream_write($data) {
		return false;
	}

	/**
	 * @param int $option
	 * @param int $arg1
	 * @param int $arg2
	 * @return bool
	 */
	public function stream_set_option($option, $arg1, $arg2) {
		return false;
	}

	/**
	 * @param int $size
	 * @return bool
	 */
	public function stream_truncate($size) {
		return false;
	}

	/**
	 * @return array
	 */
	public function stream_stat() {
		return [
			'size' => $this->size,
		];
	}

	/**
	 * @param int $operation
	 * @return bool
	 */
	public function stream_lock($operation) {
		return false;
	}

	/**
	 * @return bool
	 */
	public function stream_flush() {
		return false;
	}

	/**
	 * @return bool
	 */
	public function stream_eof() {
		return $this->pos >= $this->size || ($this->currentNode >= count($this->nodes) && $this->currentNode === null);
	}

	/**
	 * @return bool
	 */
	public function stream_close() {
		return true;
	}


	/**
	 * Load the source from the stream context and return the context options
	 *
	 * @param string $name
	 * @return array
	 * @throws \BadMethodCallException
	 */
	protected function loadContext($name) {
		$context = stream_context_get_options($this->context);
		if (isset($context[$name])) {
			$context = $context[$name];
		} else {
			throw new \BadMethodCallException('Invalid context, "' . $name . '" options not set');
		}
		if (isset($context['nodes']) and is_array($context['nodes'])) {
			$this->nodes = $context['nodes'];
		} else {
			throw new \BadMethodCallException('Invalid context, nodes not set');
		}
		return $context;
	}

	/**
	 * @param IFile[] $nodes
	 * @return resource
	 *
	 * @throws \BadMethodCallException
	 */
	public static function wrap(array $nodes) {
		$context = stream_context_create([
			'assembly' => [
				'nodes' => $nodes
			]
		]);
		stream_wrapper_register('assembly', self::class);
		try {
			$wrapped = fopen('assembly://', 'r', false, $context);
		} catch (\BadMethodCallException $e) {
			stream_wrapper_unregister('assembly');
			throw $e;
		}
		stream_wrapper_unregister('assembly');
		return $wrapped;
	}

	/**
	 * @param IFile $node
	 * @return resource
	 */
	private function getStream(IFile $node) {
		$data = $node->get();
		if (is_resource($data)) {
			return $data;
		} else {
			$tmp = fopen('php://temp', 'w+');
			fwrite($tmp, $data);
			rewind($tmp);
			return $tmp;
		}
	}
}
