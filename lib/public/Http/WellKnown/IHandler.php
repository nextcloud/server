<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Http\WellKnown;

/**
 * Interface for an app handler that reacts to requests to Nextcloud's well
 * known URLs, e.g. to a WebFinger
 *
 * @ref https://tools.ietf.org/html/rfc5785
 *
 * @since 21.0.0
 */
interface IHandler {
	/**
	 * @param string $service the name of the well known service, e.g. 'webfinger'
	 * @param IRequestContext $context
	 * @param IResponse|null $previousResponse the response of the previous handler, if any
	 *
	 * @return IResponse|null a response object if the request could be handled, null otherwise
	 *
	 * @since 21.0.0
	 */
	public function handle(string $service, IRequestContext $context, ?IResponse $previousResponse): ?IResponse;
}
