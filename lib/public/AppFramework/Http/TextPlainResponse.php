<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;

/**
 * A renderer for text responses
 * @since 22.0.0
 * @template S of Http::STATUS_*
 * @template H of array<string, mixed>
 * @template-extends Response<Http::STATUS_*, array<string, mixed>>
 */
class TextPlainResponse extends Response {
	/** @var string */
	private $text = '';

	/**
	 * constructor of TextPlainResponse
	 * @param string $text The text body
	 * @param S $statusCode the Http status code, defaults to 200
	 * @param H $headers
	 * @since 22.0.0
	 */
	public function __construct(string $text = '', int $statusCode = Http::STATUS_OK, array $headers = []) {
		parent::__construct($statusCode, $headers);

		$this->text = $text;
		$this->addHeader('Content-Type', 'text/plain');
	}


	/**
	 * Returns the text
	 * @return string
	 * @since 22.0.0
	 * @throws \Exception If data could not get encoded
	 */
	public function render() : string {
		return $this->text;
	}
}
