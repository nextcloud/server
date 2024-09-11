<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Settings;

/**
 * @since 12
 */
interface IIconSection {
	/**
	 * returns the ID of the section. It is supposed to be a lower case string,
	 * e.g. 'ldap'
	 *
	 * @return string
	 * @since 9.1
	 */
	public function getID();

	/**
	 * returns the translated name as it should be displayed, e.g. 'LDAP / AD
	 * integration'. Use the L10N service to translate it.
	 *
	 * @return string
	 * @since 9.1
	 */
	public function getName();

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the settings navigation. The sections are arranged in ascending order of
	 *             the priority values. It is required to return a value between 0 and 99.
	 *
	 * E.g.: 70
	 * @since 9.1
	 */
	public function getPriority();

	/**
	 * returns the relative path to an 16*16 icon describing the section.
	 * e.g. '/core/img/places/files.svg'
	 *
	 * @return string
	 * @since 12
	 */
	public function getIcon();
}
