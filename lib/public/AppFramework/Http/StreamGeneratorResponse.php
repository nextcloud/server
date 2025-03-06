<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework\Http;

use Generator;
use OCP\AppFramework\Http;

/**
 * @since 32.0.0
 *
 * @template S of Http::STATUS_*
 * @template H of array<string, mixed>
 * @template-extends Response<Http::STATUS_*, array<string, mixed>>
 */
class StreamGeneratorResponse extends Response implements ICallbackResponse {
	protected $generator;

	/**
	 * @since 32.0.0
	 *
	 * @param Generator $generator the function to call to generate the response
	 * @param string $contentType http response content type e.g. 'application/json; charset=UTF-8'
	 * @param S $status http response status
	 * @param array|null $headers additional headers
	 */
	public function __construct(Generator $generator, string $contentType, int $status = Http::STATUS_OK, ?array $headers = []) {
		parent::__construct();

		$this->generator = $generator;
		
		$this->setStatus($status);
		$this->addHeader('Content-Type', $contentType);
		
		foreach ($headers as $key => $value) {
			$this->addHeader($key, $value);
		}
	}

	/**
	 * Streams content directly to client
	 *
	 * @since 32.0.0
	 *
	 * @param IOutput $output a small wrapper that handles output
	 */
	public function callback(IOutput $output) {
		
		foreach ($this->generator as $chunk) {
			print($chunk);
			flush();
		}

	}

}
