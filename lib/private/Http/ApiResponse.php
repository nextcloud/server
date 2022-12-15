<?php

declare(strict_types=1);

/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

namespace OC\Http;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;

/**
 * JSend-link API response
 *
 * @see https://github.com/omniti-labs/jsend
 */
class ApiResponse extends JSONResponse {
	public static function success($data = null, int $statusCode = Http::STATUS_OK): self {
		return new self(
			[
				'status' => 'success',
				'data' => $data,
			],
			$statusCode
		);
	}

	public static function fail($data = null, int $statusCode = Http::STATUS_BAD_REQUEST): self {
		return new self(
			[
				'status' => 'fail',
				'data' => $data,
			],
			$statusCode
		);
	}

	public static function error(string $message,
		int $statusCode = Http::STATUS_INTERNAL_SERVER_ERROR,
		int $code = null,
		$data = null): self {
		return new self(
			[
				'status' => 'error',
				'message' => $message,
				'code' => $code,
				'data' => $data,
			],
			$statusCode
		);
	}

	private function __construct($data = [], $statusCode = Http::STATUS_OK) {
		parent::__construct($data, $statusCode);
	}
}
