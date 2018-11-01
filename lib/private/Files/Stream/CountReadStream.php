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

namespace OC\Files\Stream;

use Icewind\Streams\Wrapper;

class CountReadStream extends Wrapper {
	/** @var int */
	private $count;

	/** @var callback */
	private $callback;

	public static function wrap($source, $callback) {
		$context = stream_context_create(array(
			'count' => array(
				'source' => $source,
				'callback' => $callback,
			)
		));
		return Wrapper::wrapSource($source, $context, 'count', self::class);
	}

	public function dir_opendir($path, $options) {
		return false;
	}

	public function stream_open($path, $mode, $options, &$opened_path) {
		$context = $this->loadContext('count');

		$this->callback = $context['callback'];
		return true;
	}

	public function stream_read($count) {
		$result = parent::stream_read($count);
		$this->count += strlen($result);
		return $result;
	}

	public function stream_close() {
		$result = parent::stream_close();
		call_user_func($this->callback, $this->count);
		return $result;
	}
}
