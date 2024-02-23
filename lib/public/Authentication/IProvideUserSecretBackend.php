<?php
/**
 * @copyright Copyright (c) 2021, MichaIng <micha@dietpi.com>
 *
 * @author MichaIng <micha@dietpi.com>
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
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes

namespace OCP\Authentication;

/**
 * Interface IProvideUserSecretBackend
 *
 * @since 23.0.0
 */
interface IProvideUserSecretBackend {
	/**
	 * Optionally returns a stable per-user secret. This secret is for
	 * instance used to secure file encryption keys.
	 * @return string
	 * @since 23.0.0
	 */
	public function getCurrentUserSecret(): string;
}
