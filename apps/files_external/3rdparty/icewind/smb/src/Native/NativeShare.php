<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB\Native;

use Icewind\SMB\AbstractShare;
use Icewind\SMB\Exception\DependencyException;
use Icewind\SMB\Exception\InvalidPathException;
use Icewind\SMB\Exception\InvalidResourceException;
use Icewind\SMB\INotifyHandler;
use Icewind\SMB\IServer;
use Icewind\SMB\Wrapped\Server;
use Icewind\SMB\Wrapped\Share;

class NativeShare extends AbstractShare {
	/**
	 * @var IServer $server
	 */
	private $server;

	/**
	 * @var string $name
	 */
	private $name;

	/**
	 * @var NativeState $state
	 */
	private $state;

	/**
	 * @param IServer $server
	 * @param string $name
	 */
	public function __construct($server, $name) {
		parent::__construct();
		$this->server = $server;
		$this->name = $name;
	}

	/**
	 * @throws \Icewind\SMB\Exception\ConnectionException
	 * @throws \Icewind\SMB\Exception\AuthenticationException
	 * @throws \Icewind\SMB\Exception\InvalidHostException
	 */
	protected function getState() {
		if ($this->state and $this->state instanceof NativeState) {
			return $this->state;
		}

		$this->state = new NativeState();
		$this->state->init($this->server->getAuth());
		return $this->state;
	}

	/**
	 * Get the name of the share
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	private function buildUrl($path) {
		$this->verifyPath($path);
		$url = sprintf('smb://%s/%s', $this->server->getHost(), $this->name);
		if ($path) {
			$path = trim($path, '/');
			$url .= '/';
			$url .= implode('/', array_map('rawurlencode', explode('/', $path)));
		}
		return $url;
	}

	/**
	 * List the content of a remote folder
	 *
	 * @param string $path
	 * @return \Icewind\SMB\IFileInfo[]
	 *
	 * @throws \Icewind\SMB\Exception\NotFoundException
	 * @throws \Icewind\SMB\Exception\InvalidTypeException
	 */
	public function dir($path) {
		$files = array();

		$dh = $this->getState()->opendir($this->buildUrl($path));
		while ($file = $this->getState()->readdir($dh)) {
			$name = $file['name'];
			if ($name !== '.' and $name !== '..') {
				$files [] = new NativeFileInfo($this, $path . '/' . $name, $name);
			}
		}

		$this->getState()->closedir($dh);
		return $files;
	}

	/**
	 * @param string $path
	 * @return \Icewind\SMB\IFileInfo
	 */
	public function stat($path) {
		return new NativeFileInfo($this, $path, basename($path), $this->getStat($path));
	}

	public function getStat($path) {
		return $this->getState()->stat($this->buildUrl($path));
	}

	/**
	 * Create a folder on the share
	 *
	 * @param string $path
	 * @return bool
	 *
	 * @throws \Icewind\SMB\Exception\NotFoundException
	 * @throws \Icewind\SMB\Exception\AlreadyExistsException
	 */
	public function mkdir($path) {
		return $this->getState()->mkdir($this->buildUrl($path));
	}

	/**
	 * Remove a folder on the share
	 *
	 * @param string $path
	 * @return bool
	 *
	 * @throws \Icewind\SMB\Exception\NotFoundException
	 * @throws \Icewind\SMB\Exception\InvalidTypeException
	 */
	public function rmdir($path) {
		return $this->getState()->rmdir($this->buildUrl($path));
	}

	/**
	 * Delete a file on the share
	 *
	 * @param string $path
	 * @return bool
	 *
	 * @throws \Icewind\SMB\Exception\NotFoundException
	 * @throws \Icewind\SMB\Exception\InvalidTypeException
	 */
	public function del($path) {
		return $this->getState()->unlink($this->buildUrl($path));
	}

	/**
	 * Rename a remote file
	 *
	 * @param string $from
	 * @param string $to
	 * @return bool
	 *
	 * @throws \Icewind\SMB\Exception\NotFoundException
	 * @throws \Icewind\SMB\Exception\AlreadyExistsException
	 */
	public function rename($from, $to) {
		return $this->getState()->rename($this->buildUrl($from), $this->buildUrl($to));
	}

	/**
	 * Upload a local file
	 *
	 * @param string $source local file
	 * @param string $target remove file
	 * @return bool
	 *
	 * @throws \Icewind\SMB\Exception\NotFoundException
	 * @throws \Icewind\SMB\Exception\InvalidTypeException
	 */
	public function put($source, $target) {
		$sourceHandle = fopen($source, 'rb');
		$targetHandle = $this->getState()->create($this->buildUrl($target));

		while ($data = fread($sourceHandle, NativeReadStream::CHUNK_SIZE)) {
			$this->getState()->write($targetHandle, $data);
		}
		$this->getState()->close($targetHandle);
		return true;
	}

	/**
	 * Download a remote file
	 *
	 * @param string $source remove file
	 * @param string $target local file
	 * @return bool
	 *
	 * @throws \Icewind\SMB\Exception\NotFoundException
	 * @throws \Icewind\SMB\Exception\InvalidTypeException
	 * @throws \Icewind\SMB\Exception\InvalidPathException
	 * @throws \Icewind\SMB\Exception\InvalidResourceException
	 */
	public function get($source, $target) {
		if (!$target) {
			throw new InvalidPathException('Invalid target path: Filename cannot be empty');
		}
		$targetHandle = @fopen($target, 'wb');
		if (!$targetHandle) {
			$error = error_get_last();
			if (is_array($error)) {
				$reason = $error['message'];
			} else {
				$reason = 'Unknown error';
			}
			throw new InvalidResourceException('Failed opening local file "' . $target . '" for writing: ' . $reason);
		}

		$sourceHandle = $this->getState()->open($this->buildUrl($source), 'r');
		if (!$sourceHandle) {
			fclose($targetHandle);
			throw new InvalidResourceException('Failed opening remote file "' . $source . '" for reading');
		}

		while ($data = $this->getState()->read($sourceHandle, NativeReadStream::CHUNK_SIZE)) {
			fwrite($targetHandle, $data);
		}
		$this->getState()->close($sourceHandle);
		return true;
	}

	/**
	 * Open a readable stream top a remote file
	 *
	 * @param string $source
	 * @return resource a read only stream with the contents of the remote file
	 *
	 * @throws \Icewind\SMB\Exception\NotFoundException
	 * @throws \Icewind\SMB\Exception\InvalidTypeException
	 */
	public function read($source) {
		$url = $this->buildUrl($source);
		$handle = $this->getState()->open($url, 'r');
		return NativeReadStream::wrap($this->getState(), $handle, 'r', $url);
	}

	/**
	 * Open a readable stream top a remote file
	 *
	 * @param string $source
	 * @return resource a read only stream with the contents of the remote file
	 *
	 * @throws \Icewind\SMB\Exception\NotFoundException
	 * @throws \Icewind\SMB\Exception\InvalidTypeException
	 */
	public function write($source) {
		$url = $this->buildUrl($source);
		$handle = $this->getState()->create($url);
		return NativeWriteStream::wrap($this->getState(), $handle, 'w', $url);
	}

	/**
	 * Get extended attributes for the path
	 *
	 * @param string $path
	 * @param string $attribute attribute to get the info
	 * @return string the attribute value
	 */
	public function getAttribute($path, $attribute) {
		return $this->getState()->getxattr($this->buildUrl($path), $attribute);
	}

	/**
	 * Get extended attributes for the path
	 *
	 * @param string $path
	 * @param string $attribute attribute to get the info
	 * @param mixed $value
	 * @return string the attribute value
	 */
	public function setAttribute($path, $attribute, $value) {

		if ($attribute === 'system.dos_attr.mode' and is_int($value)) {
			$value = '0x' . dechex($value);
		}

		return $this->getState()->setxattr($this->buildUrl($path), $attribute, $value);
	}

	/**
	 * @param string $path
	 * @param int $mode a combination of FileInfo::MODE_READONLY, FileInfo::MODE_ARCHIVE, FileInfo::MODE_SYSTEM and FileInfo::MODE_HIDDEN, FileInfo::NORMAL
	 * @return mixed
	 */
	public function setMode($path, $mode) {
		return $this->setAttribute($path, 'system.dos_attr.mode', $mode);
	}

	/**
	 * @param string $path
	 * @return INotifyHandler
	 */
	public function notify($path) {
		// php-smbclient does support notify (https://github.com/eduardok/libsmbclient-php/issues/29)
		// so we use the smbclient based backend for this
		if (!Server::available($this->server->getSystem())) {
			throw new DependencyException('smbclient not found in path for notify command');
		}
		$share = new Share($this->server, $this->getName());
		return $share->notify($path);
	}

	public function __destruct() {
		unset($this->state);
	}
}
