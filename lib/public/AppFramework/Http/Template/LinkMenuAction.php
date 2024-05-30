<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Http\Template;

use OCP\Util;

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
		parent::__construct('directLink-container', $label, $icon, $link);
	}

	/**
	 * @return string
	 * @since 14.0.0
	 */
	public function render(): string {
		return '<li>' .
			'<a id="directLink-container">' .
			'<span class="icon ' . Util::sanitizeHTML($this->getIcon()) . '"></span>' .
			'<label for="directLink">' . Util::sanitizeHTML($this->getLabel()) . '</label>' .
			'</a>' .
			'</li>' .
			'<li>' .
			'<span class="menuitem">' .
			'<input id="directLink" type="text" readonly="" value="' . Util::sanitizeHTML($this->getLink()) . '">' .
			'</span>' .
			'</li>';
	}
}
