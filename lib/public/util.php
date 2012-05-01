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

class Util {

	// consts for Logging
	const DEBUG=0;
	const INFO=1;
	const WARN=2;
	const ERROR=3;
	const FATAL=4;

	/**
	 * get the current installed version of ownCloud
	 * @return array
	 */
	public static function getVersion(){
		return(\OC_Util::getVersion());
	}


	/**
	 * send an email 
	 *
	 * @param string $toaddress
	 * @param string $toname
	 * @param string $subject
	 * @param string $mailtext
	 * @param string $fromaddress
	 * @param string $fromname
	 * @param bool $html
	 */
	public static function sendMail($toaddress,$toname,$subject,$mailtext,$fromaddress,$fromname,$html=0,$altbody='',$ccaddress='',$ccname='',$bcc='') {

		// call the internal mail class
		\OC_MAIL::send($toaddress,$toname,$subject,$mailtext,$fromaddress,$fromname,$html=0,$altbody='',$ccaddress='',$ccname='',$bcc='');

	}

        /**
	 * write a message in the log
         *
	 * @param string $app
	 * @param string $message
	 * @param int level
         */
        public static function writeLog($app, $message, $level) {

                // call the internal log class
                \OC_LOG::write($app, $message, $level);

        }


	/**
	 * add a css file
	 *
	 * @param url  $url
	 */
	public static function addStyle( $application, $file = null ){
		\OC_Util::addStyle($application, $file);
        }

	/**
	 * add a javascript file
	 *
	 * @param appid  $application
	 * @param filename  $file
	 */
	public static function addScript( $application, $file = null ){
		\OC_Util::addScript($application, $file);
        }

	/**
	 * @brief Add a custom element to the header
	 * @param string tag tag name of the element
	 * @param array $attributes array of attributes for the element
	 * @param string $text the text content for the element
	 */
	public static function addHeader( $tag, $attributes, $text=''){
		\OC_Util::addHeader($tag, $attribute, $text);
	}

	/**
	 * formats a timestamp in the "right" way
	 *
	 * @param int timestamp $timestamp
	 * @param bool dateOnly option to ommit time from the result
	 */
	public static function formatDate( $timestamp,$dateOnly=false){
		return(\OC_Util::formatDate($timestamp,$dateOnly));
	}












}

?>
