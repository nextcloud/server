<?php
/** * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 * 
 */
namespace OCP;

/**
 * Access to all the configuration options ownCloud offers
 */
interface IConfig {
	/**
	 * Sets a new systemwide value
	 * @param string $key the key of the value, under which will be saved
	 * @param string $value the value that should be stored
	 * @todo need a use case for this
	 */
// 	public function setSystemValue($key, $value);

	/**
	 * Looks up a systemwide defined value
	 * @param string $key the key of the value, under which it was saved
	 * @return string the saved value
	 */
	public function getSystemValue($key);


	/**
	 * Writes a new appwide value
	 * @param string $key the key of the value, under which will be saved
	 * @param string $value the value that should be stored
	 */
	public function setAppValue($key, $value, $appName=null);

	/**
	 * Looks up an appwide defined value
	 * @param string $key the key of the value, under which it was saved
	 * @return string the saved value
	 */
	public function getAppValue($key, $appName=null);


	/**
	 * Shortcut for setting a user defined value
	 * @param string $key the key under which the value is being stored
	 * @param string $value the value that you want to store
	 * @param string $userId the userId of the user that we want to store the value under, defaults to the current one
	 */
	public function setUserValue($key, $value, $userId=null);

	/**
	 * Shortcut for getting a user defined value
	 * @param string $key the key under which the value is being stored
	 * @param string $userId the userId of the user that we want to store the value under, defaults to the current one
	 */
	public function getUserValue($key, $userId=null);
}
