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
class ExternalShareMenuAction extends SimpleMenuAction {

	/**
	 * ExternalShareMenuAction constructor.
	 *
	 * @param string $label Translated label
	 * @param string $icon Icon CSS class
	 * @param string $owner Owner user ID (unused)
	 * @param string $displayname Display name of the owner (unused)
	 * @param string $shareName Name of the share (unused)
	 * @since 14.0.0
	 */
	public function __construct(string $label, string $icon, string $owner, string $displayname, string $shareName) {
		parent::__construct('save', $label, $icon);
	}
}
