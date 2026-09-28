<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Http\WellKnown;

use OCP\AppFramework\Http\Response;

/**
 * @since 21.0.0
 */
final class GenericResponse implements IResponse {
	/** @var Response */
	private $response;

	/**
	 * @since 21.0.0
	 */
	public function __construct(Response $response) {
		$this->response = $response;
	}

	/**
	 * @since 21.0.0
	 */
	public function toHttpResponse(): Response {
		return $this->response;
	}
}
