<?php
/**
* ownCloud
*
* @author Frank Karlitschek
* @copyright 2010 Frank Karlitschek karlitschek@kde.org
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

/**
 * Public interface of ownCloud for apps to use.
 * Utility Class.
 *
 */

// use OCP namespace for all classes that are considered public. 
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;

/**
 * This class provides different helper functions to make the life of a developer easier
 */
class Util {


	// consts for Logging
	const DEBUG=0;
	const INFO=1;
	const WARN=2;
	const ERROR=3;
	const FATAL=4;


	/**
	 * @brief get the current installed version of ownCloud
	 * @return array
	 */
	public static function getVersion(){
		return(\OC_Util::getVersion());
	}


	/**
	 * @brief send an email 
	 * @param string $toaddress
	 * @param string $toname
	 * @param string $subject
	 * @param string $mailtext
	 * @param string $fromaddress
	 * @param string $fromname
	 * @param bool $html
	 */
	public static function sendMail( $toaddress, $toname, $subject, $mailtext, $fromaddress, $fromname, $html=0, $altbody='', $ccaddress='', $ccname='', $bcc='') {
		// call the internal mail class
		\OC_MAIL::send( $toaddress, $toname, $subject, $mailtext, $fromaddress, $fromname, $html=0, $altbody='', $ccaddress='', $ccname='', $bcc='');
	}


        /**
	 * @brief write a message in the log
	 * @param string $app
	 * @param string $message
	 * @param int level
         */
        public static function writeLog( $app, $message, $level ) {
                // call the internal log class
                \OC_LOG::write( $app, $message, $level );
        }


	/**
	 * @brief add a css file
	 * @param url  $url
	 */
	public static function addStyle( $application, $file = null ){
		\OC_Util::addStyle( $application, $file );
        }


	/**
	 * @brief add a javascript file
	 * @param appid  $application
	 * @param filename  $file
	 */
	public static function addScript( $application, $file = null ){
		\OC_Util::addScript( $application, $file );
        }

	/**
	 * @brief Add a custom element to the header
	 * @param string tag tag name of the element
	 * @param array $attributes array of attributes for the element
	 * @param string $text the text content for the element
	 */
	public static function addHeader( $tag, $attributes, $text=''){
		\OC_Util::addHeader( $tag, $attributes, $text );
	}

	/**
	 * @brief formats a timestamp in the "right" way
	 * @param int timestamp $timestamp
	 * @param bool dateOnly option to ommit time from the result
	 */
	public static function formatDate( $timestamp,$dateOnly=false){
		return(\OC_Util::formatDate( $timestamp,$dateOnly ));
	}



	/**
	 * @brief Creates an absolute url
	 * @param $app app
	 * @param $file file
	 * @returns the url
	 *
	 * Returns a absolute url to the given app and file.
	 */
	public static function linkToAbsolute( $app, $file ) {
		return(\OC_Helper::linkToAbsolute( $app, $file ));
	}


	/**
	 * @brief Creates an absolute url for remote use
	 * @param $service id
	 * @returns the url
	 *
	 * Returns a absolute url to the given app and file.
	 */
	public static function linkToRemote( $service ) {
		return(\OC_Helper::linkToRemote( $service ));
	}


        /**
         * @brief Creates an url
         * @param $app app
         * @param $file file
         * @returns the url
         *
         * Returns a url to the given app and file.
         */
        public static function linkTo( $app, $file ){
		return(\OC_Helper::linkTo( $app, $file ));
	}

	/**
	 * @brief Returns the server host
	 * @returns the server host
	 *
	 * Returns the server host, even if the website uses one or more
	 * reverse proxies
	 */
	public static function getServerHost() {
		return(\OC_Helper::serverHost());
	}


	/**
	 * @brief Returns the server protocol
	 * @returns the server protocol
	 *
	 * Returns the server protocol. It respects reverse proxy servers and load balancers
	 */
	 public static function getServerProtocol() {
		return(\OC_Helper::serverProtocol());
	}


	/**
	 * @brief Creates path to an image
	 * @param $app app
	 * @param $image image name
	 * @returns the url
	 *
	 * Returns the path to the image.
	 */
        public static function imagePath( $app, $image ){
		return(\OC_Helper::imagePath( $app, $image ));
	}


	/**
	 * @brief Make a human file size
	 * @param $bytes file size in bytes
	 * @returns a human readable file size
	 *
	 * Makes 2048 to 2 kB.
	 */
	public static function humanFileSize( $bytes ){
		return(\OC_Helper::humanFileSize( $bytes ));
	}

	/**
	 * @brief Make a computer file size
	 * @param $str file size in a fancy format
	 * @returns a file size in bytes
	 *
	 * Makes 2kB to 2048.
	 *
	 * Inspired by: http://www.php.net/manual/en/function.filesize.php#92418
	 */
	public static function computerFileSize( $str ){
		return(\OC_Helper::computerFileSize( $str ));
	}

	/**
	 * @brief connects a function to a hook
	 * @param $signalclass class name of emitter
	 * @param $signalname name of signal
	 * @param $slotclass class name of slot
	 * @param $slotname name of slot
	 * @returns true/false
	 *
	 * This function makes it very easy to connect to use hooks.
	 *
	 * TODO: write example
	 */
	static public function connectHook( $signalclass, $signalname, $slotclass, $slotname ){
		return(\OC_Hook::connect( $signalclass, $signalname, $slotclass, $slotname ));
	}


	/**
	 * @brief emitts a signal
	 * @param $signalclass class name of emitter
	 * @param $signalname name of signal
	 * @param $params defautl: array() array with additional data
	 * @returns true if slots exists or false if not
	 *
	 * Emits a signal. To get data from the slot use references!
	 *
	 * TODO: write example
	 */
	static public function emitHook( $signalclass, $signalname, $params = array()){
		return(\OC_Hook::emit( $signalclass, $signalname, $params ));
	}

	/**
 	 * Register an get/post call. This is important to prevent CSRF attacks
	 * TODO: write example
	 */
	public static function callRegister(){
		return(\OC_Util::callRegister());
	}


	/**
	 * Check an ajax get/post call if the request token is valid. exit if not.
	 * Todo: Write howto
	 */
	public static function callCheck(){
		return(\OC_Util::callCheck());
	}

	/**
	 * @brief Used to sanitize HTML
	 *
	 * This function is used to sanitize HTML and should be applied on any string or array of strings before displaying it on a web page.
	 *
	 * @param string or array of strings
	 * @return array with sanitized strings or a single sinitized string, depends on the input parameter.
	 */
	public static function sanitizeHTML( $value ){
		return(\OC_Util::sanitizeHTML($value));
	}
}

?>
