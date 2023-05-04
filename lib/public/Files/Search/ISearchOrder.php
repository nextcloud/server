<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Robin Appelman <robin@icewind.nl>
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
namespace OCP\Files\Search;

use OCP\Files\FileInfo;

/**
 * @since 12.0.0
 */
interface ISearchOrder {
	public const DIRECTION_ASCENDING = 'asc';
	public const DIRECTION_DESCENDING = 'desc';

	/**
	 * The direction to sort in, either ISearchOrder::DIRECTION_ASCENDING or ISearchOrder::DIRECTION_DESCENDING
	 *
	 * @return string
	 * @since 12.0.0
	 */
	public function getDirection();

	/**
	 * The field to sort on
	 *
	 * @return string
	 * @since 12.0.0
	 */
	public function getField();

	/**
	 * Apply the sorting on 2 FileInfo objects
	 *
	 * @param FileInfo $a
	 * @param FileInfo $b
	 * @return int -1 if $a < $b, 0 if $a = $b, 1 if $a > $b (for ascending, reverse for descending)
	 * @since 22.0.0
	 */
	public function sortFileInfo(FileInfo $a, FileInfo $b): int;
}
