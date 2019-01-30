<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Jan-Christoph Borchardt, http://jancborchardt.net
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 */

class OC_Theme {

	/**
	 * Returns the base URL
	 * @return string URL
	 */
	public function getBaseUrl() {
		return 'https://www.huemer-dc.com/';
	}

	/**
	 * Returns the URL where the sync clients are listed
	 * @return string URL
	 */
	public function getSyncClientUrl() {
		return 'http://hdc.pw/sync';
	}

	/**
	 * Returns the URL to the App Store for the iOS Client
	 * @return string URL
	 */
	public function getiOSClientUrl() {
		return 'https://itunes.apple.com/us/app/hbox/id988733986?mt=8';
	}

	/**
	 * Returns the AppId for the App Store for the iOS Client
	 * @return string AppId
	 */
	public function getiTunesAppId() {
		return '988733986';
	}

	/**
	 * Returns the URL to Google Play for the Android Client
	 * @return string URL
	 */
	public function getAndroidClientUrl() {
		return 'https://play.google.com/store/apps/details?id=com.hbox.android';
	}

	/**
	 * Returns the documentation URL
	 * @return string URL
	 */
	public function getDocBaseUrl() {
		return 'https://docs.nextcloud.com';
	}

	/**
	 * Returns the title
	 * @return string title
	 */
	public function getTitle() {
		return 'hBOX';
	}

	/**
	 * Returns the short name of the software
	 * @return string title
	 */
	public function getName() {
		return 'hBOX';
	}

	/**
	 * Returns the short name of the software containing HTML strings
	 * @return string title
	 */
	public function getHTMLName() {
		return 'hBOX';
	}

	/**
	 * Returns entity (e.g. company name) - used for footer, copyright
	 * @return string entity name
	 */
	public function getEntity() {
		return 'Huemer Data Center GmbH';
	}

	/**
	 * Returns slogan
	 * @return string slogan
	 */
	public function getSlogan() {
		return 'Huemer hBOX - Secure Webstorage';
	}

	/**
	 * Returns logo claim
	 * @return string logo claim
	 */
	public function getLogoClaim() {
		return '';
	}

	/**
	 * Returns short version of the footer
	 * @return string short footer
	 */
	public function getShortFooter() {
        $footer = 'safe.hbox - <a href="'.$this->getBaseUrl().'" target="_blank\">'.$this->getEntity().'</a>'.
            '<br/>' . $this->getSlogan();

        return $footer;
	}

	/**
	 * Returns long version of the footer
	 * @return string long footer
	 */
	public function getLongFooter() {
		$footer = 'safe.hbox - <a href="'.$this->getBaseUrl().'" target="_blank\">'.$this->getEntity().'</a>'.
			'<br/>' . $this->getSlogan();

		return $footer;
	}

	public function buildDocLinkToKey($key) {
		return $this->getDocBaseUrl() . '/server/12/go.php?to=' . $key;
	}


	/**
	 * Returns mail header color
	 * @return string
	 */
	public function getColorPrimary() {
		return '#80CC2B';
	}

	/**
	 * Returns variables to overload defaults from core/css/variables.scss
	 * @return array
	 */
	public function getScssVariables() {
		return [
			'color-primary' => '#7DC12A'
		];
	}

}
