<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Bootstrap;

/**
 * @psalm-immutable
 */
abstract class ARegistration {
	/** @var string */
	private $appId;

	public function __construct(string $appId) {
		$this->appId = $appId;
	}

	/**
	 * @return string
	 */
	public function getAppId(): string {
		return $this->appId;
	}
}
