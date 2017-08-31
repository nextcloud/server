<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
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

namespace OCP\Collaboration\Collaborators;


interface ISearchResult {
	/**
	 * @param string $type one of: users, groups, remotes, email, circles, lookup
	 * @param array $matches
	 * @param array|null $exactMatches
	 * @since 13.0.0
	 */
	public function addResultSet($type, array $matches, array $exactMatches = null);

	/**
	 * @param string $type one of: users, groups, remotes, email, circles, lookup
	 * @param string $collaboratorId
	 * @return bool
	 * @since 13.0.0
	 */
	public function hasResult($type, $collaboratorId);

	/**
	 * @param string $type one of: users, groups, remotes, email, circles, lookup
	 * @since 13.0.0
	 */
	public function unsetResult($type);

	/**
	 * @param string $type one of: users, groups, remotes, email, circles, lookup
	 * @since 13.0.0
	 */
	public function markExactIdMatch($type);

	/**
	 * @param string $type one of: users, groups, remotes, email, circles, lookup
	 * @return bool
	 * @since 13.0.0
	 */
	public function hasExactIdMatch($type);

	/**
	 * @return array
	 * @since 13.0.0
	 */
	public function asArray();
}
