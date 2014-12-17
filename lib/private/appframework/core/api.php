<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt <dev@bernhard-posselt.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OC\AppFramework\Core;
use OCP\AppFramework\IApi;


/**
 * This is used to wrap the owncloud static api calls into an object to make the
 * code better abstractable for use in the dependency injection container
 *
 * Should you find yourself in need for more methods, simply inherit from this
 * class and add your methods
 * @deprecated
 */
class API implements IApi{

	private $appName;

	/**
	 * constructor
	 * @param string $appName the name of your application
	 */
	public function __construct($appName){
		$this->appName = $appName;
	}


	/**
	 * Gets the userid of the current user
	 * @return string the user id of the current user
	 * @deprecated Use \OC::$server->getUserSession()->getUser()->getUID()
	 */
	public function getUserId(){
		return \OCP\User::getUser();
	}


	/**
	 * Adds a new javascript file
	 * @deprecated include javascript and css in template files
	 * @param string $scriptName the name of the javascript in js/ without the suffix
	 * @param string $appName the name of the app, defaults to the current one
	 */
	public function addScript($scriptName, $appName=null){
		if($appName === null){
			$appName = $this->appName;
		}
		\OCP\Util::addScript($appName, $scriptName);
	}


	/**
	 * Adds a new css file
	 * @deprecated include javascript and css in template files
	 * @param string $styleName the name of the css file in css/without the suffix
	 * @param string $appName the name of the app, defaults to the current one
	 */
	public function addStyle($styleName, $appName=null){
		if($appName === null){
			$appName = $this->appName;
		}
		\OCP\Util::addStyle($appName, $styleName);
	}


	/**
	 * @deprecated include javascript and css in template files
	 * shorthand for addScript for files in the 3rdparty directory
	 * @param string $name the name of the file without the suffix
	 */
	public function add3rdPartyScript($name){
		\OCP\Util::addScript($this->appName . '/3rdparty', $name);
	}


	/**
	 * @deprecated include javascript and css in template files
	 * shorthand for addStyle for files in the 3rdparty directory
	 * @param string $name the name of the file without the suffix
	 */
	public function add3rdPartyStyle($name){
		\OCP\Util::addStyle($this->appName . '/3rdparty', $name);
	}


	/**
	 * @deprecated communication between apps should happen over built in
	 * callbacks or interfaces (check the contacts and calendar managers)
	 * Checks if an app is enabled
	 * also use \OC::$server->getAppManager()->isEnabledForUser($appName)
	 * @param string $appName the name of an app
	 * @return bool true if app is enabled
	 */
	public function isAppEnabled($appName){
		return \OCP\App::isEnabled($appName);
	}


	/**
	 * used to return and open a new event source
	 * @return \OCP\IEventSource a new open EventSource class
	 * @deprecated Use \OC::$server->createEventSource();
	 */
	public function openEventSource(){
		return \OC::$server->createEventSource();
	}

	/**
	 * @deprecated register hooks directly for class that build in hook interfaces
	 * connects a function to a hook
	 * @param string $signalClass class name of emitter
	 * @param string $signalName name of signal
	 * @param string $slotClass class name of slot
	 * @param string $slotName name of slot, in another word, this is the
	 *               name of the method that will be called when registered
	 *               signal is emitted.
	 * @return bool, always true
	 */
	public function connectHook($signalClass, $signalName, $slotClass, $slotName) {
		return \OCP\Util::connectHook($signalClass, $signalName, $slotClass, $slotName);
	}

	/**
	 * @deprecated implement the emitter interface instead
	 * Emits a signal. To get data from the slot use references!
	 * @param string $signalClass class name of emitter
	 * @param string $signalName name of signal
	 * @param array $params default: array() array with additional data
	 * @return bool, true if slots exists or false if not
	 */
	public function emitHook($signalClass, $signalName, $params = array()) {
		return  \OCP\Util::emitHook($signalClass, $signalName, $params);
	}

	/**
	 * clear hooks
	 * @deprecated clear hooks directly for class that build in hook interfaces
	 * @param string $signalClass
	 * @param string $signalName
	 */
	public function clearHook($signalClass=false, $signalName=false) {
		if ($signalClass) {
			\OC_Hook::clear($signalClass, $signalName);
		}
	}


	/**
	 * Register a backgroundjob task
	 * @param string $className full namespace and class name of the class
	 * @param string $methodName the name of the static method that should be
	 * called
	 * @deprecated Use \OC::$server->getJobList()->add();
	 */
	public function addRegularTask($className, $methodName) {
		\OCP\Backgroundjob::addRegularTask($className, $methodName);
	}


	/**
	 * Tells ownCloud to include a template in the admin overview
	 * @param string $mainPath the path to the main php file without the php
	 * suffix, relative to your apps directory! not the template directory
	 * @param string $appName the name of the app, defaults to the current one
	 */
	public function registerAdmin($mainPath, $appName=null) {
		if($appName === null){
			$appName = $this->appName;
		}

		\OCP\App::registerAdmin($appName, $mainPath);
	}


}
