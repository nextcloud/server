<?php
/**
* ownCloud
*
* @author Björn Schießle
* @copyright 2013 Björn Schießle schiessle@owncloud.com
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

namespace OCP;

/*
 * public api to access default strings and urls for your templates
 */

class Defaults {

	private $defaults;

	function __construct() {
		$this->defaults = new \OC_Defaults();
	}

	/**
	 * @brief subject for share notification mail
	 * @param string $user user who shared the item
	 * @pram string $itemName name of the item
	 */
	public function getShareNotificationSubject($user, $itemName) {
		return $this->defaults->getShareNotificationSubject($user, $itemName);
	}

	/**
	 * @brief mail body for share notification mail (text only)
	 * @param string $sender owner of the file/folder
	 * @param string $itemName name of the file/folder
	 * @param string $link link directly to the file/folder in your ownCloud
	 * @param string $expiration expiration date
	 */
	public function getShareNotificationTextAlt($sender, $itemName, $link, $expiration) {
		return $this->defaults->getShareNotificationTextAlt($sender, $itemName, $link, $expiration);
	}

	/**
	 * @brief mail body for share notification mail (HTML mail)
	 * @param string $sender owner of the file/folder
	 * @param string $itemName name of the file/folder
	 * @param string $link link directly to the file/folder in your ownCloud
	 * @param string $expiration expiration date
	 */
	public function getShareNotificationTextHtml($sender, $itemName, $link, $expiration) {
		return $this->defaults->getShareNotificationTextHtml($sender, $itemName, $link, $expiration);
	}

	/**
	 * @brief return footer for mails (HTML mail)
	 */
	public function getMailFooterHtml() {
		return $this->defaults->getMailFooterHtml();
	}

		/**
	 * @brief return footer for mails (text only)
	 */
	public function getMailFooterAlt() {
		return $this->defaults->getMailFooterAlt();
	}

	/**
	 * @breif get base URL for the organisation behind your ownCloud instance
	 * @return string
	 */
	public function getBaseUrl() {
		return $this->defaults->getBaseUrl();
	}

	/**
	 * @breif link to the desktop sync client
	 * @return string
	 */
	public function getSyncClientUrl() {
		return $this->defaults->getSyncClientUrl();
	}

	/**
	 * @breif base URL to the documentation of your ownCloud instance
	 * @return string
	 */
	public function getDocBaseUrl() {
		return $this->defaults->getDocBaseUrl();
	}

	/**
	 * @breif name of your ownCloud instance
	 * @return string
	 */
	public function getName() {
		return $this->defaults->getName();
	}

	/**
	 * @breif Entity behind your onwCloud instance
	 * @return string
	 */
	public function getEntity() {
		return $this->defaults->getEntity();
	}

	/**
	 * @breif ownCloud slogan
	 * @return string
	 */
	public function getSlogan() {
		return $this->defaults->getSlogan();
	}

	/**
	 * @breif logo claim
	 * @return string
	 */
	public function getLogoClaim() {
		return $this->defaults->getLogoClaim();
	}

	/**
	 * @breif footer, short version
	 * @return string
	 */
	public function getShortFooter() {
		return $this->defaults->getShortFooter();
	}

	/**
	 * @breif footer, long version
	 * @return string
	 */
	public function getLongFooter() {
		return $this->defaults->getLongFooter();
	}
}
