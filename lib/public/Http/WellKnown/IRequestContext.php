<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Http\WellKnown;

use OCP\IRequest;

/**
 * The context object for \OCP\Http\IWellKnownHandler::handle
 *
 * Objects of this type will transport any optional information, e.g. the request
 * object through which the app well known handler can obtain URL parameters
 *
 * @since 21.0.0
 */
interface IRequestContext {
	/**
	 * @return IRequest
	 *
	 * @since 21.0.0
	 */
	public function getHttpRequest(): IRequest;
}
