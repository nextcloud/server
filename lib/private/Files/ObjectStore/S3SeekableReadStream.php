<?php
/**
 *
 * @copyright Copyright (c) 2020, Lukas Stabe (lukas@stabe.de)
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

namespace OC\Files\ObjectStore;

/**
 * A stream wrapper that uses http range requests to provide a seekable
 * stream of a file in S3 storage.
 */
class S3SeekableReadStream {
	private static $registered = false;

	/**
	 * Registers the stream wrapper using the `s3seek://` url scheme
	 * $return void
	 */
	public static function registerIfNeeded() {
		if (!self::$registered) {
			stream_wrapper_register(
				's3seek',
				'OC\Files\ObjectStore\S3SeekableReadStream'
			);
			self::$registered = true;
		}
	}

	private $client;
	private $bucket;
	private $urn;

	private $current;
	private $offset = 0;

	private function reconnect($range) {
		if ($this->current != null) {
			fclose($this->current);
		}

		$command = $this->client->getCommand('GetObject', [
			'Bucket' => $this->bucket,
			'Key' => $this->urn,
			'Range' => 'bytes=' . $range,
		]);
		$request = \Aws\serialize($command);
		$headers = [];
		foreach ($request->getHeaders() as $key => $values) {
			foreach ($values as $value) {
				$headers[] = "$key: $value";
			}
		}
		$opts = [
			'http' => [
				'protocol_version' => 1.1,
				'header' => $headers,
			],
		];

		$context = stream_context_create($opts);
		$this->current = fopen($request->getUri(), 'r', false, $context);

		if ($this->current === false) {return false;}

		$responseHead = stream_get_meta_data($this->current)['wrapper_data'];
		$contentRange = array_values(array_filter($responseHead, function ($v) {
			return preg_match('#^content-range:#i', $v) === 1;
		}))[0];

		$content = trim(explode(':', $contentRange)[1]);
		$range = trim(explode(' ', $content)[1]);
		$begin = explode('-', $range)[0];
		$this->offset = intval($begin);

		return true;
	}

	function stream_open($path, $mode, $options, &$opened_path) {
		$o = stream_context_get_options($this->context)['s3seek'];
		$this->bucket = $o['bucket'];
		$this->urn = $o['urn'];
		$this->client = $o['client'];

		return $this->reconnect('0-');
	}

	function stream_read($count) {
		$ret = fread($this->current, $count);
		$this->offset += strlen($ret);
		return $ret;
	}

	function stream_seek($offset, $whence) {
		switch ($whence) {
		case SEEK_SET:
			if ($offset === $this->offset) {return true;}
			return $this->reconnect($offset . '-');
		case SEEK_CUR:
			if ($offset === 0) {return true;}
			return $this->reconnect(($this->offset + $offset) . '-');
		case SEEK_END:
			return false;
		}
		return false;
	}

	function stream_tell() {
		return $this->offset;
	}

	function stream_stat() {
		return fstat($this->current);
	}

	function stream_eof() {
		return feof($this->current);
	}

	function stream_close() {
		fclose($this->current);
	}
}
