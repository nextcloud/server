<?php declare(strict_types=1);
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
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

namespace SearchDAV\Query;


class Scope {
	/**
	 * @var string
	 *
	 * The scope of the search, either as absolute uri or as a path relative to the
	 * search arbiter.
	 */
	public $href;

	/**
	 * @var string|int 0, 1 or 'infinite'
	 *
	 * How deep the search query should be with 0 being only the scope itself,
	 * 1 being all direct child entries of the scope and infinite being all entries
	 * in the scope collection at any depth.
	 */
	public $depth;

	/**
	 * @var string|null
	 *
	 * the path of the search scope relative to the dav server, or null if the scope is outside the dav server
	 */
	public $path;

	/**
	 * @param string $href
	 * @param int|string $depth
	 * @param string|null $path
	 */
	public function __construct(string $href = '', $depth = 1, string $path = null) {
		$this->href = $href;
		$this->depth = $depth;
		$this->path = $path;
	}
}
