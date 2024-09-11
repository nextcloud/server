<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Mail\Provider;

/**
 * Mail Message Send Interface
 *
 * This interface is the required set of methods and functionality used to extend IService with message sending functionality
 *
 * @since 30.0.0
 *
 */
interface IMessageSend {

	/**
	 * send an outbound message
	 *
	 * @since 30.0.0
	 *
	 * @param IMessage $message mail message object with all required parameters to send a message
	 * @param array $options array of options reserved for future use
	 *
	 * @throws \OCP\Mail\Provider\Exception\SendException on failure, check message for reason
	 */
	public function sendMessage(IMessage $message, array $options = []): void;

}
