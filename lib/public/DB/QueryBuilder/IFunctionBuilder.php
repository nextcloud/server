<?php
/**
 * @author Robin Appelman <robin@icewind.nl>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCP\DB\QueryBuilder;

/**
 * This class provides a builder for sql some functions
 *
 * @since 12.0.0
 */
interface IFunctionBuilder {
	/**
	 * Calculates the MD5 hash of a given input
	 *
	 * @param mixed $input The input to be hashed
	 *
	 * @return IQueryFunction
	 * @since 12.0.0
	 */
	public function md5($input);

	/**
	 * Combines two input strings
	 *
	 * @param mixed $x The first input string
	 * @param mixed $y The seccond input string
	 *
	 * @return IQueryFunction
	 * @since 12.0.0
	 */
	public function concat($x, $y);

	/**
	 * Takes a substring from the input string
	 *
	 * @param mixed $input The input string
	 * @param mixed $start The start of the substring, note that counting starts at 1
	 * @param mixed $length The length of the substring
	 *
	 * @return IQueryFunction
	 * @since 12.0.0
	 */
	public function substring($input, $start, $length = null);
}
