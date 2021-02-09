<?php
/**
 * @copyright Copyright (c) 2021, hosting.de, Johannes Leuker <j.leuker@hosting.de>.
 *
 * @author Johannes Leuker <j.leuker@hosting.de>.
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCP\Group\Backend;

/**
 * @since 22.0.0
 */
interface INamedBackend {

	/**
	 * Backend name to be shown in group management
	 * @return string the name of the backend to be shown
	 * @since 22.0.0
	 */
	public function getBackendName(): string;
}
