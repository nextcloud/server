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
}
