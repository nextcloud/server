<?php
/**
 * ownCloud
 *
 * @author Vincent Petry
 * @copyright 2013 Vincent Petry <pvince81@owncloud.com>
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
 * This class extends the SabreDAV client with additional functionality
 * like request timeout.
 */

class OC_DAVClient extends \Sabre_DAV_Client {

	protected $requestTimeout;

	protected $verifyHost;

	/**
	 * Sets the request timeout or 0 to disable timeout.
	 * @param integer $timeout in seconds or 0 to disable
	 */
	public function setRequestTimeout($timeout) {
		$this->requestTimeout = (int)$timeout;
	}

	/**
	 * Sets the CURLOPT_SSL_VERIFYHOST setting
	 * @param integer $value value to set CURLOPT_SSL_VERIFYHOST to
	*/
	public function setVerifyHost($value) {
		$this->verifyHost = $value;
	}

	protected function curlRequest($url, $settings) {
		if ($this->requestTimeout > 0) {
			$settings[CURLOPT_TIMEOUT] = $this->requestTimeout;
		}
		if (!is_null($this->verifyHost)) {
			$settings[CURLOPT_SSL_VERIFYHOST] = $this->verifyHost;
		}
		return parent::curlRequest($url, $settings);
	}
}
