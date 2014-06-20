<?php

namespace OCP\Files\ObjectStore;

interface IObjectStore {

	/**
	 * @return string the container or bucket name where objects are stored
	 */
	function getStorageId();

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return resource stream with the read data
	 * @throws Exception when something goes wrong, message will be logged
	 */
	function readObject($urn);

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @param resource $stream stream with the data to write
	 * @throws Exception when something goes wrong, message will be logged
	 */
	function writeObject($urn, $stream);

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return void
	 * @throws Exception when something goes wrong, message will be logged
	 */
	 function deleteObject($urn);

}