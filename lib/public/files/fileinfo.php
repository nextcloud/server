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
	 * Get the Etag of the file or folder
	 *
	 * @return string
	 */
	public function getEtag();

	/**
	 * Get the size in bytes for the file or folder
	 *
	 * @return int
	 */
	public function getSize();

	/**
	 * Get the last modified date as timestamp for the file or folder
	 *
	 * @return int
	 */
	public function getMtime();

	/**
	 * Get the name of the file or folder
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Get the path relative to the storage
	 *
	 * @return string
	 */
	public function getInternalPath();

	/**
	 * Get the absolute path
	 *
	 * @return string
	 */
	public function getPath();

	/**
	 * Get the full mimetype of the file or folder i.e. 'image/png'
	 *
	 * @return string
	 */
	public function getMimetype();

	/**
	 * Get the first part of the mimetype of the file or folder i.e. 'image'
	 *
	 * @return string
	 */
	public function getMimePart();

	/**
	 * Get the storage the file or folder is storage on
	 *
	 * @return \OCP\Files\Storage
	 */
	public function getStorage();

	/**
	 * Get the file id of the file or folder
	 *
	 * @return int
	 */
	public function getId();

	/**
	 * Check whether the file is encrypted
	 *
	 * @return bool
	 */
	public function isEncrypted();

	/**
	 * Get the permissions of the file or folder as bitmasked combination of the following constants
	 * \OCP\PERMISSION_CREATE
	 * \OCP\PERMISSION_READ
	 * \OCP\PERMISSION_UPDATE
	 * \OCP\PERMISSION_DELETE
	 * \OCP\PERMISSION_SHARE
	 * \OCP\PERMISSION_ALL
	 *
	 * @return int
	 */
	public function getPermissions();

	/**
	 * Check whether this is a file or a folder
	 *
	 * @return \OCP\Files\FileInfo::TYPE_FILE | \OCP\Files\FileInfo::TYPE_FOLDER
	 */
	public function getType();
}
