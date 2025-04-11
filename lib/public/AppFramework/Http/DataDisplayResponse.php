<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;

/**
 * Class DataDisplayResponse
 *
 * @since 8.1.0
 * @template S of Http::STATUS_*
 * @template H of array<string, mixed>
 * @template-extends Response<Http::STATUS_*, array<string, mixed>>
 */
class DataDisplayResponse extends Response {
	/**
	 * response data
	 * @var string
	 */
	protected $data;


	/**
	 * @param string $data the data to display
	 * @param S $statusCode the Http status code, defaults to 200
	 * @param H $headers additional key value based headers
	 * @since 8.1.0
	 */
	public function __construct(string $data = '', int $statusCode = Http::STATUS_OK, array $headers = []) {
		parent::__construct($statusCode, $headers);

		$this->data = $data;
		$this->addHeader('Content-Disposition', 'inline; filename=""');
	}

	/**
	 * Outputs data. No processing is done.
	 * @return string
	 * @since 8.1.0
	 */
	public function render() {
		return $this->data;
	}


	/**
	 * Sets values in the data
	 * @param string $data the data to display
	 * @return DataDisplayResponse Reference to this object
	 * @since 8.1.0
	 */
	public function setData($data) {
		$this->data = $data;

		return $this;
	}


	/**
	 * Used to get the set parameters
	 * @return string the data
	 * @since 8.1.0
	 */
	public function getData() {
		return $this->data;
	}
}
