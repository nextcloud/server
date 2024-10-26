<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Events;

use OC\Authentication\Token\IToken;
use OCP\EventDispatcher\Event;

abstract class ARemoteWipeEvent extends Event {
	/** @var IToken */
	private $token;

	public function __construct(IToken $token) {
		parent::__construct();
		$this->token = $token;
	}

	public function getToken(): IToken {
		return $this->token;
	}
}
