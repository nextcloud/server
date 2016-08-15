<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\Settings;

/**
 * @since 9.1
 */
interface IManager {
	/**
	 * @since 9.1.0
	 */
	const KEY_ADMIN_SETTINGS = 'admin';

	/**
	 * @since 9.1.0
	 */
	const KEY_ADMIN_SECTION  = 'admin-section';

	/**
	 * sets up settings according to data specified by an apps info.xml, within
	 * the <settings> element.
	 *
	 * @param array $settings an associative array, allowed keys are as specified
	 *                        by the KEY_ constant of  this interface. The value
	 *                        must always be a class name, implement either
	 *                        IAdmin or ISection. I.e. only one section and admin
	 *                        setting can be configured per app.
	 * @since 9.1.0
	 */
	public function setupSettings(array $settings);

	/**
	 * attempts to remove an apps section and/or settings entry. A listener is
	 * added centrally making sure that this method is called ones an app was
	 * disabled.
	 *
	 * What this does not help with is when applications change their settings
	 * or section classes during their life time. New entries will be added,
	 * but inactive ones will still reside in the database.
	 *
	 * @param string $appId
	 * @since 9.1.0
	 */
	public function onAppDisabled($appId);

	/**
	 * The method should check all registered classes whether they are still
	 * instantiable and remove them, if not. This method is called by a
	 * background job once, after one or more apps were updated.
	 *
	 * An app`s info.xml can change during an update and make it unknown whether
	 * a registered class name was changed or not. An old one would just stay
	 * registered. Another case is if an admin takes a radical approach and
	 * simply removes an app from the app folder. These unregular checks will
	 * take care of such situations.
	 *
	 * @since 9.1.0
	 */
	public function checkForOrphanedClassNames();

	/**
	 * returns a list of the admin sections
	 *
	 * @return array array of ISection[] where key is the priority
	 * @since 9.1.0
	 */
	public function getAdminSections();

	/**
	 * returns a list of the admin settings
	 *
	 * @param string $section the section id for which to load the settings
	 * @return array array of IAdmin[] where key is the priority
	 * @since 9.1.0
	 */
	public function getAdminSettings($section);
}
