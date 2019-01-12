<?php
/**
 * @copyright Copyright (c) 2019, Tomasz Grobelny <tomasz@grobelny.net>
 *
 * @author Tomasz Grobelny <tomasz@grobelny.net>
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

namespace OC;

class StreamHashFilter extends \php_user_filter
{
	static function getContextObject($stream) {
		stream_filter_register('hash.md5', 'OC\\StreamHashFilter');
		$obj = new \stdClass();
		$md5_filter = stream_filter_append($stream, 'hash.md5', STREAM_FILTER_ALL, $obj);
		return $obj;
	}

	function onCreate () {
		$this->params->hashContext = hash_init('md5');
		return TRUE;
	}

	function onClose () {
		return TRUE;
	}

	function filter ($in, $out, &$consumed, $closing)
	{
		while ($bucket = stream_bucket_make_writeable($in)) {
			hash_update($this->params->hashContext, $bucket->data);
			$consumed += $bucket->datalen;
			stream_bucket_append($out, $bucket);
		}
		return PSFS_PASS_ON;
	}
}
