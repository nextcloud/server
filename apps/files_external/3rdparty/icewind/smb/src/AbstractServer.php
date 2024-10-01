<?php
/**
 * SPDX-FileCopyrightText: 2018 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Icewind\SMB;

abstract class AbstractServer implements IServer {
	const LOCALE = 'en_US.UTF-8';

	/** @var string */
	protected $host;

	/** @var IAuth */
	protected $auth;

	/** @var ISystem */
	protected $system;

	/** @var ITimeZoneProvider */
	protected $timezoneProvider;

	/** @var IOptions */
	protected $options;

	/**
	 * @param string $host
	 * @param IAuth $auth
	 * @param ISystem $system
	 * @param ITimeZoneProvider $timeZoneProvider
	 * @param IOptions $options
	 */
	public function __construct(string $host, IAuth $auth, ISystem $system, ITimeZoneProvider $timeZoneProvider, IOptions $options) {
		$this->host = $host;
		$this->auth = $auth;
		$this->system = $system;
		$this->timezoneProvider = $timeZoneProvider;
		$this->options = $options;
	}

	public function getAuth(): IAuth {
		return $this->auth;
	}

	public function getHost(): string {
		return $this->host;
	}

	public function getTimeZone(): string {
		return $this->timezoneProvider->get($this->host);
	}

	public function getSystem(): ISystem {
		return $this->system;
	}

	public function getOptions(): IOptions {
		return $this->options;
	}
}
