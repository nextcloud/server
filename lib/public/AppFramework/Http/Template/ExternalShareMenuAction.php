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
class ExternalShareMenuAction extends SimpleMenuAction {
	/** @var string */
	private $owner;

	/** @var string */
	private $displayname;

	/** @var string */
	private $shareName;

	/**
	 * ExternalShareMenuAction constructor.
	 *
	 * @param string $label
	 * @param string $icon
	 * @param string $owner
	 * @param string $displayname
	 * @param string $shareName
	 * @since 14.0.0
	 */
	public function __construct(string $label, string $icon, string $owner, string $displayname, string $shareName) {
		parent::__construct('save', $label, $icon);
		$this->owner = $owner;
		$this->displayname = $displayname;
		$this->shareName = $shareName;
	}

	/**
	 * @since 14.0.0
	 */
	public function render(): string {
		return '<li>' .
			'    <button id="save-external-share" class="icon ' . Util::sanitizeHTML($this->getIcon()) . '" data-protected="false" data-owner-display-name="' . Util::sanitizeHTML($this->displayname) . '" data-owner="' . Util::sanitizeHTML($this->owner) . '" data-name="' . Util::sanitizeHTML($this->shareName) . '">' . Util::sanitizeHTML($this->getLabel()) . '</button>' .
			'</li>' .
			'<li id="external-share-menu-item" class="hidden">' .
			'    <span class="menuitem">' .
			'        <form class="save-form" action="#">' .
			'            <input type="text" id="remote_address" placeholder="user@yourNextcloud.org">' .
			'            <input type="submit" value=" " id="save-button-confirm" class="icon-confirm" disabled="disabled"></button>' .
			'        </form>' .
			'    </span>' .
			'</li>';
	}
}
