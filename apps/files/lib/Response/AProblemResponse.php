<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Response;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;

/**
 * @template S of Http::STATUS_*
 * @template H of array<string, mixed>
 * @template-extends Response<Http::STATUS_*, array<string, mixed>>
 */
abstract class AProblemResponse extends Response {
	public const MEDIA_TYPE_PROBLEM_JSON = 'application/problem+json';

	/**
	 * @param array<string, mixed> $data
	 * @psalm-param S $status
	 * @psalm-param H $headers
	 */
	public function __construct(
		private readonly string $type,
		private readonly string $title,
		private readonly array $data,
		int $status,
		array $headers = [],
	) {
		$headers['Content-Type'] = self::MEDIA_TYPE_PROBLEM_JSON;
		parent::__construct($status, $headers);
	}

	public function render(): string {
		return json_encode([
			'type' => $this->type,
			'title' => $this->title,
			...$this->data,
		], JSON_THROW_ON_ERROR);
	}
}
