<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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
namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;

/**
 * Class DataDownloadResponse
 *
 * @since 8.0.0
 * @template S of int
 * @template C of string
 * @template H of array<string, mixed>
 * @template-extends DownloadResponse<int, string, array<string, mixed>>
 */
class DataDownloadResponse extends DownloadResponse {
	/**
	 * @var string
	 */
	private $data;

	/**
	 * Creates a response that prompts the user to download the text
	 * @param string $data text to be downloaded
	 * @param string $filename the name that the downloaded file should have
	 * @param C $contentType the mimetype that the downloaded file should have
	 * @param S $status
	 * @param H $headers
	 * @since 8.0.0
	 */
	public function __construct(string $data, string $filename, string $contentType, int $status = Http::STATUS_OK, array $headers = []) {
		$this->data = $data;
		parent::__construct($filename, $contentType, $status, $headers);
	}

	/**
	 * @param string $data
	 * @since 8.0.0
	 */
	public function setData($data) {
		$this->data = $data;
	}

	/**
	 * @return string
	 * @since 8.0.0
	 */
	public function render() {
		return $this->data;
	}
}
