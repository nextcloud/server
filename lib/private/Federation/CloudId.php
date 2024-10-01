<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Federation;

use OCP\Federation\ICloudId;

class CloudId implements ICloudId {
	/** @var string */
	private $id;
	/** @var string */
	private $user;
	/** @var string */
	private $remote;
	/** @var string|null */
	private $displayName;

	/**
	 * CloudId constructor.
	 *
	 * @param string $id
	 * @param string $user
	 * @param string $remote
	 */
	public function __construct(string $id, string $user, string $remote, ?string $displayName = null) {
		$this->id = $id;
		$this->user = $user;
		$this->remote = $remote;
		$this->displayName = $displayName;
	}

	/**
	 * The full remote cloud id
	 *
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}

	public function getDisplayId(): string {
		if ($this->displayName) {
			$atPos = strrpos($this->getId(), '@');
			$atHost = substr($this->getId(), $atPos);
			return $this->displayName . $atHost;
		}
		return str_replace('https://', '', str_replace('http://', '', $this->getId()));
	}

	/**
	 * The username on the remote server
	 *
	 * @return string
	 */
	public function getUser(): string {
		return $this->user;
	}

	/**
	 * The base address of the remote server
	 *
	 * @return string
	 */
	public function getRemote(): string {
		return $this->remote;
	}
}
