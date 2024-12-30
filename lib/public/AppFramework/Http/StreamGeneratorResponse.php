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
 * Class StreamResponse
 *
 * @since 31.0.0
 *
 * @template-extends Response<int, array<string, mixed>>
 */
class StreamGeneratorResponse extends Response implements ICallbackResponse {
	protected $generator;

	/**
	 * @since 32.0.0
	 *
	 * @param \Generator $generator the function to call to generate the response
	 * @param String $contentType http response content type e.g. 'application/json; charset=UTF-8'
	 * @param int $status http response status
	 */
	public function __construct(Generator $generator, string $contentType, int $status = 200) {
		parent::__construct();

		$this->generator = $generator;
		
		$this->setStatus($status);
		$this->cacheFor(0);
		$this->addHeader('Content-Type', $contentType);

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
