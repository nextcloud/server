<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;
use Override;
use Traversable;

/**
 * Class StreamResponse
 *
 * @since 33.0.0
 * @template S of Http::STATUS_*
 * @template H of array<string, mixed>
 * @template-extends Response<Http::STATUS_*, array<string, mixed>>
 */
class StreamTraversableResponse extends Response implements ICallbackResponse {
	/**
	 * @param S $status
	 * @param H $headers
	 * @since 33.0.0
	 */
	public function __construct(
		private Traversable $generator,
		int $status = Http::STATUS_OK,
		array $headers = [],
	) {
		parent::__construct($status, $headers);
	}


	/**
	 * Streams the generator output
	 *
	 * @param IOutput $output a small wrapper that handles output
	 * @since 33.0.0
	 */
	#[Override]
	public function callback(IOutput $output): void {
		foreach ($this->generator as $content) {
			$output->setOutput($content);
			flush();
		}
	}
}
