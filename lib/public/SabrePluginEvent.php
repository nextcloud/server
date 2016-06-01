<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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
 *
 */

namespace OCP;


use OCP\AppFramework\Http;
use Sabre\DAV\Server;
use Symfony\Component\EventDispatcher\Event;

/**
 * @since 8.2.0
 */
class SabrePluginEvent extends Event {

	/** @var int */
	protected $statusCode;

	/** @var string */
	protected $message;

	/** @var Server */
	protected $server;

	/**
	 * @since 8.2.0
	 */
	public function __construct($server = null) {
		$this->message = '';
		$this->statusCode = Http::STATUS_OK;
		$this->server = $server;
	}

	/**
	 * @param int $statusCode
	 * @return self
	 * @since 8.2.0
	 */
	public function setStatusCode($statusCode) {
		$this->statusCode = (int) $statusCode;
		return $this;
	}

	/**
	 * @param string $message
	 * @return self
	 * @since 8.2.0
	 */
	public function setMessage($message) {
		$this->message = (string) $message;
		return $this;
	}

	/**
	 * @return int
	 * @since 8.2.0
	 */
	public function getStatusCode() {
		return $this->statusCode;
	}

	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * @return null|Server
	 * @since 9.0.0
	 */
	public function getServer() {
		return $this->server;
	}
}
