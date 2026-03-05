<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Http\Template;

/**
 * Class LinkMenuAction
 *
 * @since 14.0.0
 */
class LinkMenuAction extends SimpleMenuAction {
	/**
	 * LinkMenuAction constructor.
	 *
	 * @param string $label
	 * @param string $icon
	 * @param string $link
	 * @since 14.0.0
	 */
	public function __construct(string $label, string $icon, string $link) {
		parent::__construct('directLink', $label, $icon, $link);
	}
}
