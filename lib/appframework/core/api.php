<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt nukeawhale@gmail.com
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
	 */
	public function getUserId(){
		return \OCP\User::getUser();
	}


	/**
	 * Adds a new javascript file
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
	 * shorthand for addScript for files in the 3rdparty directory
	 * @param string $name the name of the file without the suffix
	 */
	public function add3rdPartyScript($name){
		\OCP\Util::addScript($this->appName . '/3rdparty', $name);
	}


	/**
	 * shorthand for addStyle for files in the 3rdparty directory
	 * @param string $name the name of the file without the suffix
	 */
	public function add3rdPartyStyle($name){
		\OCP\Util::addStyle($this->appName . '/3rdparty', $name);
	}


	/**
	 * Returns the translation object
	 * @return \OC_L10N the translation object
	 */
	public function getTrans(){
		# TODO: use public api
		return \OC_L10N::get($this->appName);
	}


	/**
	 * Returns the URL for a route
	 * @param string $routeName the name of the route
	 * @param array $arguments an array with arguments which will be filled into the url
	 * @return string the url
	 */
	public function linkToRoute($routeName, $arguments=array()){
		return \OCP\Util::linkToRoute($routeName, $arguments);
	}


	/**
	 * Returns an URL for an image or file
	 * @param string $file the name of the file
	 * @param string $appName the name of the app, defaults to the current one
	 */
	public function linkTo($file, $appName=null){
		if($appName === null){
			$appName = $this->appName;
		}
		return \OCP\Util::linkTo($appName, $file);
	}


	/**
	 * Returns the link to an image, like link to but only with prepending img/
	 * @param string $file the name of the file
	 * @param string $appName the name of the app, defaults to the current one
	 */
	public function imagePath($file, $appName=null){
		if($appName === null){
			$appName = $this->appName;
		}
		return \OCP\Util::imagePath($appName, $file);
	}


	/**
	 * Makes an URL absolute
	 * @param string $url the url
	 * @return string the absolute url
	 */
	public function getAbsoluteURL($url){
		# TODO: use public api
		return \OC_Helper::makeURLAbsolute($url);
	}


	/**
	 * links to a file
	 * @param string $file the name of the file
	 * @param string $appName the name of the app, defaults to the current one
	 * @deprecated replaced with linkToRoute()
	 * @return string the url
	 */
	public function linkToAbsolute($file, $appName=null){
		if($appName === null){
			$appName = $this->appName;
		}
		return \OCP\Util::linkToAbsolute($appName, $file);
	}


	/**
	 * Checks if the CSRF check was correct
	 * @return bool true if CSRF check passed
	 */
	public function passesCSRFCheck(){
		# TODO: use public api
		return \OC_Util::isCallRegistered();
	}


	/**
	 * Checks if an app is enabled
	 * @param string $appName the name of an app
	 * @return bool true if app is enabled
	 */
	public function isAppEnabled($appName){
		return \OCP\App::isEnabled($appName);
	}


	/**
	 * Writes a function into the error log
	 * @param string $msg the error message to be logged
	 * @param int $level the error level
	 */
	public function log($msg, $level=null){
		switch($level){
			case 'debug':
				$level = \OCP\Util::DEBUG;
				break;
			case 'info':
				$level = \OCP\Util::INFO;
				break;
			case 'warn':
				$level = \OCP\Util::WARN;
				break;
			case 'fatal':
				$level = \OCP\Util::FATAL;
				break;
			default:
				$level = \OCP\Util::ERROR;
				break;
		}
		\OCP\Util::writeLog($this->appName, $msg, $level);
	}


	/**
	 * turns an owncloud path into a path on the filesystem
	 * @param string path the path to the file on the oc filesystem
	 * @return string the filepath in the filesystem
	 */
	public function getLocalFilePath($path){
		# TODO: use public api
		return \OC_Filesystem::getLocalFile($path);
	}


	/**
	 * used to return and open a new eventsource
	 * @return \OC_EventSource a new open EventSource class
	 */
	public function openEventSource(){
		# TODO: use public api
		return new \OC_EventSource();
	}

	/**
	 * @brief connects a function to a hook
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
	 * @brief Emits a signal. To get data from the slot use references!
	 * @param string $signalClass class name of emitter
	 * @param string $signalName name of signal
	 * @param array $params defautl: array() array with additional data
	 * @return bool, true if slots exists or false if not
	 */
	public function emitHook($signalClass, $signalName, $params = array()) {
		return  \OCP\Util::emitHook($signalClass, $signalName, $params);
	}

	/**
	 * @brief clear hooks
	 * @param string $signalClass
	 * @param string $signalName
	 */
	public function clearHook($signalClass=false, $signalName=false) {
		if ($signalClass) {
			\OC_Hook::clear($signalClass, $signalName);
		}
	}

	/**
	 * Gets the content of an URL by using CURL or a fallback if it is not
	 * installed
	 * @param string $url the url that should be fetched
	 * @return string the content of the webpage
	 */
	public function getUrlContent($url) {
		return \OC_Util::getUrlContent($url);
	}

	/**
	 * Register a backgroundjob task
	 * @param string $className full namespace and class name of the class
	 * @param string $methodName the name of the static method that should be
	 * called
	 */
	public function addRegularTask($className, $methodName) {
		\OCP\Backgroundjob::addRegularTask($className, $methodName);
	}

	/**
	 * Returns a template
	 * @param string $templateName the name of the template
	 * @param string $renderAs how it should be rendered
	 * @param string $appName the name of the app
	 * @return \OCP\Template a new template
	 */
	public function getTemplate($templateName, $renderAs='user', $appName=null){
		if($appName === null){
			$appName = $this->appName;
		}

		if($renderAs === 'blank'){
			return new \OCP\Template($appName, $templateName);
		} else {
			return new \OCP\Template($appName, $templateName, $renderAs);
		}
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


	/**
	 * get the filesystem info
	 *
	 * @param string $path
	 * @return array with the following keys:
	 * - size
	 * - mtime
	 * - mimetype
	 * - encrypted
	 * - versioned
	 */
	public function getFileInfo($path) {
		return \OC\Files\Filesystem::getFileInfo($path);
	}

}
