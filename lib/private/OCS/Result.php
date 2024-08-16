<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\OCS;

class Result {
	protected array $data;

	/** @var null|string */
	protected ?string $message;

	/** @var int */
	protected int $statusCode;

	/** @var integer */
	protected $items;

	/** @var integer */
	protected $perPage;

	/** @var array */
	private array $headers = [];

	/**
	 * create the OCS_Result object
	 *
	 * @param mixed|null $data the data to return
	 * @param int $code
	 * @param string|null $message
	 * @param array $headers
	 */
	public function __construct(mixed $data = null, int $code = 100, ?string $message = null, array $headers = []) {
		if ($data === null) {
			$this->data = [];
		} elseif (!is_array($data)) {
			$this->data = [$this->data];
		} else {
			$this->data = $data;
		}
		$this->statusCode = $code;
		$this->message = $message;
		$this->headers = $headers;
	}

	/**
	 * optionally set the total number of items available
	 *
	 * @param int $items
	 */
	public function setTotalItems(int $items): void {
		$this->items = $items;
	}

	/**
	 * optionally set the number of items per page
	 *
	 * @param int $items
	 */
	public function setItemsPerPage(int $items): void {
		$this->perPage = $items;
	}

	/**
	 * get the status code
	 * @return int
	 */
	public function getStatusCode(): int {
		return $this->statusCode;
	}

	/**
	 * get the meta data for the result
	 * @return array
	 */
	public function getMeta(): array {
		$meta = [];
		$meta['status'] = $this->succeeded() ? 'ok' : 'failure';
		$meta['statuscode'] = $this->statusCode;
		$meta['message'] = $this->message;
		if ($this->items !== null) {
			$meta['totalitems'] = $this->items;
		}
		if ($this->perPage !== null) {
			$meta['itemsperpage'] = $this->perPage;
		}
		return $meta;
	}

	/**
	 * get the result data
	 * @return array
	 */
	public function getData(): array {
		return $this->data;
	}

	/**
	 * return bool Whether the method succeeded
	 * @return bool
	 */
	public function succeeded(): bool {
		return ($this->statusCode == 100);
	}

	/**
	 * Adds a new header to the response
	 *
	 * @param string $name The name of the HTTP header
	 * @param string $value The value, null will delete it
	 * @return $this
	 */
	public function addHeader(string $name, ?string $value): static {
		$name = trim($name);  // always remove leading and trailing whitespace
		// to be able to reliably check for security
		// headers

		if (is_null($value)) {
			unset($this->headers[$name]);
		} else {
			$this->headers[$name] = $value;
		}

		return $this;
	}

	/**
	 * Returns the set headers
	 * @return array the headers
	 */
	public function getHeaders(): array {
		return $this->headers;
	}
}
