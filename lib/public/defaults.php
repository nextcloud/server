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

/**
 * Public interface of ownCloud for apps to use.
 * Defaults Class
 *
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
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
