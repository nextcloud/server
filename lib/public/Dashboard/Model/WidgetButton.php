<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Dashboard\Model;

/**
 * Button for a dashboard widget
 *
 * @since 25.0.0
 */
class WidgetButton {
	/**
	 * @since 25.0.0
	 */
	public const TYPE_NEW = 'new';

	/**
	 * @since 25.0.0
	 */
	public const TYPE_MORE = 'more';

	/**
	 * @since 25.0.0
	 */
	public const TYPE_SETUP = 'setup';

	private string $type;
	private string $link;
	private string $text;

	/**
	 * @param string $type
	 * @param string $link
	 * @param string $text
	 * @since 25.0.0
	 */
	public function __construct(string $type, string $link, string $text) {
		$this->type = $type;
		$this->link = $link;
		$this->text = $text;
	}

	/**
	 * Get the button type, either "new", "more" or "setup"
	 *
	 * @return string
	 * @since 25.0.0
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * Get the absolute url the buttons links to
	 *
	 * @return string
	 * @since 25.0.0
	 */
	public function getLink(): string {
		return $this->link;
	}

	/**
	 * Get the translated text for the button
	 *
	 * @return string
	 * @since 25.0.0
	 */
	public function getText(): string {
		return $this->text;
	}
}
