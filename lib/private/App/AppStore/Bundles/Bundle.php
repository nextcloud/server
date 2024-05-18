<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@statuscode.ch>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\App\AppStore\Bundles;

use OCP\IL10N;

abstract class Bundle {
	/**
	 * @param IL10N $l10n
	 */
	public function __construct(
		protected IL10N $l10n,
	) {
	}

	/**
	 * Get the identifier of the bundle
	 *
	 * @return string
	 */
	final public function getIdentifier() {
		return substr(strrchr(get_class($this), '\\'), 1);
	}

	/**
	 * Get the name of the bundle
	 *
	 * @return string
	 */
	abstract public function getName();

	/**
	 * Get the list of app identifiers in the bundle
	 *
	 * @return array
	 */
	abstract public function getAppIdentifiers();
}
