<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Settings;

use OCP\Settings\IIconSection;

class Section implements IIconSection {
	/**
	 * @param string $id
	 * @param string $name
	 * @param int $priority
	 * @param string $icon
	 */
	public function __construct(
		private $id,
		private $name,
		private $priority,
		private $icon = '',
	) {
	}

	/**
	 * @return string The ID of the section. It is supposed to be a lower case string,
	 *                e.g. 'ldap'
	 */
	public function getID() {
		return $this->id;
	}

	/**
	 * @return string The translated name as it should be displayed, e.g. 'LDAP / AD
	 *                integration'. Use the L10N service to translate it.
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the settings navigation. The sections are arranged in ascending order of
	 *             the priority values. It is required to return a value between 0 and 99.
	 *
	 * E.g.: 70
	 */
	public function getPriority() {
		return $this->priority;
	}

	/**
	 * @return string The relative path to an 16*16 icon describing the section.
	 *                e.g. '/core/img/places/files.svg'
	 *
	 * @since 12
	 */
	public function getIcon() {
		return $this->icon;
	}
}
