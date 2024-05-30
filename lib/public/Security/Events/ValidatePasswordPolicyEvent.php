<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Security\Events;

use OCP\EventDispatcher\Event;

/**
 * @since 18.0.0
 */
class ValidatePasswordPolicyEvent extends Event {
	/** @var string */
	private $password;

	/**
	 * @since 18.0.0
	 */
	public function __construct(string $password) {
		parent::__construct();
		$this->password = $password;
	}

	/**
	 * @since 18.0.0
	 */
	public function getPassword(): string {
		return $this->password;
	}
}
