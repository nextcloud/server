<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;

/**
 * A renderer for JSON calls
 * @since 6.0.0
 * @template S of Http::STATUS_*
 * @template-covariant T of null|string|int|float|bool|array|\stdClass|\JsonSerializable
 * @template H of array<string, mixed>
 * @template-extends Response<Http::STATUS_*, array<string, mixed>>
 */
class JSONResponse extends Response {
	/**
	 * response data
	 * @var T
	 */
	protected $data;
	/**
	 * Additional `json_encode` flags
	 * @var int
	 */
	protected $encodeFlags;


	/**
	 * constructor of JSONResponse
	 * @param T $data the object or array that should be transformed
	 * @param S $statusCode the Http status code, defaults to 200
	 * @param H $headers
	 * @param int $encodeFlags Additional `json_encode` flags
	 * @since 6.0.0
	 * @since 30.0.0 Added `$encodeFlags` param
	 */
	public function __construct(
		mixed $data = [],
		int $statusCode = Http::STATUS_OK,
		array $headers = [],
		int $encodeFlags = 0,
	) {
		parent::__construct($statusCode, $headers);

		$this->data = $data;
		$this->encodeFlags = $encodeFlags;
		$this->addHeader('Content-Type', 'application/json; charset=utf-8');
	}


	/**
	 * Returns the rendered json
	 * @return string the rendered json
	 * @since 6.0.0
	 * @throws \Exception If data could not get encoded
	 */
	public function render() {
		return json_encode($this->data, JSON_HEX_TAG | JSON_THROW_ON_ERROR | $this->encodeFlags, 2048);
	}

	/**
	 * Sets values in the data json array
	 * @psalm-suppress InvalidTemplateParam
	 * @param T $data an array or object which will be transformed
	 *                to JSON
	 * @return JSONResponse Reference to this object
	 * @since 6.0.0 - return value was added in 7.0.0
	 */
	public function setData($data) {
		$this->data = $data;

		return $this;
	}


	/**
	 * @return T the data
	 * @since 6.0.0
	 */
	public function getData() {
		return $this->data;
	}
}
