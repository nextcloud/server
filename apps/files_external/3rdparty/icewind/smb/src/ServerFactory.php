<?php
/**
 * SPDX-FileCopyrightText: 2018 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Icewind\SMB;

use Icewind\SMB\Exception\DependencyException;
use Icewind\SMB\Native\NativeServer;
use Icewind\SMB\Wrapped\Server;

class ServerFactory {
	const BACKENDS = [
		NativeServer::class,
		Server::class
	];

	/** @var ISystem */
	private $system;

	/** @var IOptions */
	private $options;

	/** @var ITimeZoneProvider */
	private $timeZoneProvider;

	/**
	 * ServerFactory constructor.
	 *
	 * @param IOptions|null $options
	 * @param ISystem|null $system
	 * @param ITimeZoneProvider|null $timeZoneProvider
	 */
	public function __construct(
		?IOptions $options = null,
		?ISystem $system = null,
		?ITimeZoneProvider $timeZoneProvider = null
	) {
		if (is_null($options)) {
			$options = new Options();
		}
		if (is_null($system)) {
			$system = new System();
		}
		if (is_null($timeZoneProvider)) {
			$timeZoneProvider = new TimeZoneProvider($system);
		}
		$this->options = $options;
		$this->system = $system;
		$this->timeZoneProvider = $timeZoneProvider;
	}


	/**
	 * @param string $host
	 * @param IAuth $credentials
	 * @return IServer
	 * @throws DependencyException
	 */
	public function createServer(string $host, IAuth $credentials): IServer {
		foreach (self::BACKENDS as $backend) {
			if (call_user_func("$backend::available", $this->system)) {
				return new $backend($host, $credentials, $this->system, $this->timeZoneProvider, $this->options);
			}
		}

		throw new DependencyException('No valid backend available, ensure smbclient is in the path or php-smbclient is installed');
	}
}
