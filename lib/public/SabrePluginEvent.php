<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP;

use OCP\AppFramework\Http;
use OCP\EventDispatcher\Event;
use Sabre\DAV\Server;

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
		$this->statusCode = (int)$statusCode;
		return $this;
	}

	/**
	 * @param string $message
	 * @return self
	 * @since 8.2.0
	 */
	public function setMessage($message) {
		$this->message = (string)$message;
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
