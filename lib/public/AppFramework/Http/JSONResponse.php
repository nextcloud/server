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
 * @template S of int
 * @template-covariant T of array|object|\stdClass|\JsonSerializable
 * @template H of array<string, mixed>
 * @template-extends Response<int, array<string, mixed>>
 */
class JSONResponse extends Response {
	/**
	 * response data
	 * @var T
	 */
	protected $data;


	/**
	 * constructor of JSONResponse
	 * @param T $data the object or array that should be transformed
	 * @param S $statusCode the Http status code, defaults to 200
	 * @param H $headers
	 * @since 6.0.0
	 */
	public function __construct(mixed $data = [], int $statusCode = Http::STATUS_OK, array $headers = []) {
		parent::__construct($statusCode, $headers);

		$this->data = $data;
		$this->addHeader('Content-Type', 'application/json; charset=utf-8');
	}


	/**
	 * Returns the rendered json
	 * @return string the rendered json
	 * @since 6.0.0
	 * @throws \Exception If data could not get encoded
	 */
	public function render() {
		return json_encode($this->data, JSON_HEX_TAG | JSON_THROW_ON_ERROR);
	}

	/**
	 * Sets values in the data json array
	 * @psalm-suppress InvalidTemplateParam
	 * @param T $data an array or object which will be transformed
	 *                             to JSON
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
