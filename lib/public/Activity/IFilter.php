<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Activity;

/**
 * Interface IFilter
 *
 * @since 11.0.0
 */
interface IFilter {
	/**
	 * @return string Lowercase a-z and underscore only identifier
	 * @since 11.0.0
	 */
	public function getIdentifier();

	/**
	 * @return string A translated string
	 * @since 11.0.0
	 */
	public function getName();

	/**
	 * @return int whether the filter should be rather on the top or bottom of
	 *             the admin section. The filters are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 * @since 11.0.0
	 */
	public function getPriority();

	/**
	 * @return string Full URL to an icon, empty string when none is given
	 * @since 11.0.0
	 */
	public function getIcon();

	/**
	 * @param string[] $types
	 * @return string[] An array of allowed apps from which activities should be displayed
	 * @since 11.0.0
	 */
	public function filterTypes(array $types);

	/**
	 * @return string[] An array of allowed apps from which activities should be displayed
	 * @since 11.0.0
	 */
	public function allowedApps();
}
