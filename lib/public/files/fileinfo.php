<?php
/**
 * Created by PhpStorm.
 * User: robin
 * Date: 1/13/14
 * Time: 1:45 PM
 */
namespace OCP\Files;

interface FileInfo extends \ArrayAccess, \JsonSerializable {
	const TYPE_FILE = 'file';
	const TYPE_FOLDER = 'folder';

	public function offsetSet($offset, $value);

	public function offsetGet($offset);

	public function offsetUnset($offset);

	public function offsetExists($offset);

	public function jsonSerialize();

	/**
	 * @return string
	 */
	public function getEtag();

	/**
	 * @return int
	 */
	public function getSize();

	/**
	 * @return int
	 */
	public function getMtime();

	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @return string
	 */
	public function getInternalPath();

	/**
	 * @return string
	 */
	public function getPath();

	/**
	 * @return string
	 */
	public function getMimetype();

	/**
	 * @return \OCP\Files\Storage
	 */
	public function getStorage();

	/**
	 * @return int
	 */
	public function getId();

	/**
	 * @return string
	 */
	public function getMimePart();
	/**
	 * @return bool
	 */
	public function isEncrypted();

	/**
	 * @return int
	 */
	public function getPermissions();

	/**
	 * @return \OCP\Files\FileInfo::TYPE_FILE | \OCP\Files\FileInfo::TYPE_FOLDER
	 */
	public function getType();
}
