<?php
declare(strict_types=1);

namespace OC\Files\SimpleFS;

use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;

/**
 * This class represents a file that is only hold in memory.
 *
 * @package OC\Files\SimpleFS
 */
class InMemoryFile implements ISimpleFile {
	/**
	 * Holds the file name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Holds the file contents.
	 *
	 * @var string
	 */
	private $contents;

	/**
	 * InMemoryFile constructor.
	 *
	 * @param string $name The file name
	 * @param string $contents The file contents
	 */
	public function __construct(string $name, string $contents) {
		$this->name = $name;
		$this->contents = $contents;
	}

	/**
	 * Returns the file name.
	 *
	 * @return string
	 * @since 11.0.0
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Get the size in bytes
	 *
	 * @return int
	 * @since 11.0.0
	 */
	public function getSize() {
		return strlen($this->contents);
	}

	/**
	 * Get the ETag
	 *
	 * @return string
	 * @since 11.0.0
	 */
	public function getETag() {
		return '';
	}

	/**
	 * Get the last modification time
	 *
	 * @return int
	 * @since 11.0.0
	 */
	public function getMTime() {
		return time();
	}

	/**
	 * Get the content
	 *
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 * @return string
	 * @since 11.0.0
	 */
	public function getContent() {
		return $this->contents;
	}

	/**
	 * Overwrite the file
	 *
	 * @param string|resource $data
	 * @throws NotPermittedException
	 * @since 11.0.0
	 */
	public function putContent($data) {
		$this->contents = $data;
	}

	/**
	 * Delete the file
	 *
	 * @throws NotPermittedException
	 * @since 11.0.0
	 */
	public function delete() {
		// unimplemented for in memory files
	}

	/**
	 * Get the MimeType
	 *
	 * @return string
	 * @since 11.0.0
	 */
	public function getMimeType() {
		$fileInfo = new \finfo(FILEINFO_MIME_TYPE);
		return $fileInfo->buffer($this->contents);
	}
}
