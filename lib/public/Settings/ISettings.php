<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Settings;

use OCP\AppFramework\Http\TemplateResponse;

/**
 * @since 9.1
 */
interface ISettings {
	/**
	 * @return TemplateResponse returns the instance with all parameters set, ready to be rendered
	 * @since 9.1
	 */
	public function getForm();

	/**
	 * @return string|null the section ID, e.g. 'sharing' or null to not show the setting
	 * @since 9.1
	 */
	public function getSection();

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 * @since 9.1
	 */
	public function getPriority();
}
