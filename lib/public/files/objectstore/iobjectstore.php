<?php

namespace OCP\Files\ObjectStore;

interface IObjectStore {

	/**
	 * @return string the container or bucket name where objects are stored
	 */
	function getStorageId();

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @param string $tmpFile path to the local temporary file that should be
	 *        used to store the object
	 * @return void
	 * @throws Exception when something goes wrong, message will be logged
	 */
	function getObject($urn, $tmpFile);

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @param string $tmpFile path to the local temporary file that the object
	 *        should be loaded from
	 * @return void
	 * @throws Exception when something goes wrong, message will be logged
	 */
	function writeObject($urn, $tmpFile = null);

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return void
	 * @throws Exception when something goes wrong, message will be logged
	 */
	 function deleteObject($urn);

}