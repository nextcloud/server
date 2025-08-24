<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;

/**
 * A generic DataResponse class that is used to return generic data responses
 * for responders to transform
 * @since 8.0.0
 * @psalm-type DataResponseType = array|int|float|string|bool|object|null|\stdClass|\JsonSerializable
 * @template S of Http::STATUS_*
 * @template-covariant T of DataResponseType
 * @template H of array<string, mixed>
 * @template-extends Response<Http::STATUS_*, array<string, mixed>>
 */
class DataResponse extends Response {
	/**
	 * response data
	 * @var T
	 */
	protected $data;


	/**
	 * @param T $data the object or array that should be transformed
	 * @param S $statusCode the Http status code, defaults to 200
	 * @param H $headers additional key value based headers
	 * @since 8.0.0
	 */
	public function __construct(mixed $data = [], int $statusCode = Http::STATUS_OK, array $headers = []) {
		parent::__construct($statusCode, $headers);

		$this->data = $data;
	}


	/**
	 * Sets values in the data json array
	 * @psalm-suppress InvalidTemplateParam
	 * @param T $data an array or object which will be transformed
	 * @return DataResponse Reference to this object
	 * @since 8.0.0
	 */
	public function setData($data) {
		$this->data = $data;

		return $this;
	}


	/**
	 * Used to get the set parameters
	 * @return T the data
	 * @since 8.0.0
	 */
	public function getData() {
		return $this->data;
	}
}
