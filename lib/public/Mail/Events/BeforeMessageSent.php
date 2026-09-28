<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Mail\Events;

use OCP\EventDispatcher\Event;
use OCP\Mail\IMessage;

/**
 * Emitted before a system mail is sent. It can be used to alter the message.
 *
 * @since 19.0.0
 */
class BeforeMessageSent extends Event {
	/** @var IMessage */
	private $message;

	/**
	 * @param IMessage $message
	 * @since 19.0.0
	 */
	public function __construct(IMessage $message) {
		parent::__construct();
		$this->message = $message;
	}

	/**
	 * @return IMessage
	 * @since 19.0.0
	 */
	public function getMessage(): IMessage {
		return $this->message;
	}
}
