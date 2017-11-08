<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Markus Goetz <markus@woboq.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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
		// http://stackoverflow.com/a/10985500
		@usort($nodes, function (IFile $a, IFile $b) {
			return strnatcmp($a->getName(), $b->getName());
		});
		$this->nodes = array_values($nodes);
		if (count($this->nodes) > 0) {
			$this->currentStream = $this->getStream($this->nodes[0]);
		}
		$this->size = array_reduce($this->nodes, function ($size, IFile $file) {
			return $size + $file->getSize();
		}, 0);
		return true;
	}

	/**
	 * @param string $offset
	 * @param int $whence
	 * @return bool
	 */
	public function stream_seek($offset, $whence = SEEK_SET) {
		return false;
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
			return '';
		}

		do {
			$data = fread($this->currentStream, $count);
			$read = strlen($data);

			if (feof($this->currentStream)) {
				fclose($this->currentStream);
				$this->currentNode++;
				if ($this->currentNode < count($this->nodes)) {
					$this->currentStream = $this->getStream($this->nodes[$this->currentNode]);
				} else {
					$this->currentStream = null;
				}
			}
			// if no data read, try again with the next node because
			// returning empty data can make the caller think there is no more
			// data left to read
		} while ($read === 0 && !is_null($this->currentStream));

		// update position
		$this->pos += $read;
		return $data;
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
		return [];
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
		return $this->pos >= $this->size;
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
	 * @throws \Exception
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
			$wrapped = fopen('assembly://', 'r', null, $context);
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
