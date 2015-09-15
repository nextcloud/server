<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Jan-Christoph Borchardt, http://jancborchardt.net
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

	public function getBaseUrl() {
		return 'https://owncloud.org';
	}

	public function getSyncClientUrl() {
		return 'https://owncloud.org/install';
	}

	public function getDocBaseUrl() {
		return 'https://doc.owncloud.org';
	}

	public function getTitle() {
		return 'Custom Cloud';
	}

	public function getName() {
		return 'Custom Cloud';
	}

	public function getEntity() {
		return 'Custom Cloud Co.';
	}

	public function getSlogan() {
		return 'Your custom cloud, personalized for you!';
	}

	public function getShortFooter() {
		$footer = '© 2015 <a href="'.$this->getBaseUrl().'" target="_blank\">'.$this->getEntity().'</a>'.
			'<br/>' . $this->getSlogan();

		return $footer;
	}

	public function getLongFooter() {
		$footer = '© 2015 <a href="'.$this->getBaseUrl().'" target="_blank\">'.$this->getEntity().'</a>'.
			'<br/>' . $this->getSlogan();

		return $footer;
	}

	public function buildDocLinkToKey($key) {
		return $this->getDocBaseUrl() . '/server/8.0/go.php?to=' . $key;
	}

	public function getMailHeaderColor() {
		return '#745bca';
	}

}
