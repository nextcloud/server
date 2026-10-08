<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Mail;

/**
 * One part of an email, recorded by EMailTemplate and rendered at the end
 *
 * $values holds the escaped values the HTML is built from, $text the plain
 * text version of the block.
 */
final class EMailTemplateBlock {
	public const HEADER = 'header';
	public const HEADING = 'heading';
	public const TEXT = 'text';
	public const LIST_ITEM = 'list-item';
	public const BUTTON_GROUP = 'button-group';
	public const BUTTON = 'button';
	public const BUTTONS = 'buttons';
	public const SENDER = 'sender';
	public const NOTE = 'note';
	public const DETAILS = 'details';
	public const FOOTER = 'footer';
	/** Closes the email when it is rendered without a footer */
	public const END = 'end';
	/** HTML and text a subclass wrote to htmlBody and plainBody directly */
	public const RAW = 'raw';

	/**
	 * @param self::* $type
	 * @param array<string, mixed> $values
	 */
	public function __construct(
		public readonly string $type,
		public readonly array $values = [],
		public readonly string $text = '',
	) {
	}
}
