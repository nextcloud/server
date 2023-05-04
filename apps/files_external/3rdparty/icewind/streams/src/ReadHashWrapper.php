<?php
/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

namespace Icewind\Streams;

/**
 * Wrapper that calculates the hash on the stream on read
 *
 * The stream and hash should be passed in when wrapping the stream.
 * On close the callback will be called with the calculated checksum.
 *
 * For supported hashes see: http://php.net/manual/en/function.hash-algos.php
 */
class ReadHashWrapper extends HashWrapper {
	public function stream_read($count) {
		$data = parent::stream_read($count);
		$this->updateHash($data);
		return $data;
	}
}
