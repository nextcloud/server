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
interface IResponse {
	/**
	 * @since 21.0.0
	 */
	public function toHttpResponse(): Response;
}
