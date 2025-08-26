<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Federation;

use OCP\Federation\ICloudId;
use OCP\Federation\ICloudIdManager;

class CloudId implements ICloudId {
	public function __construct(
		protected string $id,
		protected string $user,
		protected string $remote,
		protected ?string $displayName = null,
	) {
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
		if ($this->displayName === null) {
			/** @var CloudIdManager $cloudIdManager */
			$cloudIdManager = \OCP\Server::get(ICloudIdManager::class);
			$this->displayName = $cloudIdManager->getDisplayNameFromContact($this->getId());
		}

		$atHost = str_replace(['http://', 'https://'], '', $this->getRemote());

		if ($this->displayName) {
			return $this->displayName . '@' . $atHost;
		}
		return $this->getUser() . '@' . $atHost;
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
