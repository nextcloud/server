<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Http;

use OCP\IRequest;
use OCP\IURLGenerator;

/**
 * Helper for OCS controllers that paginate a list of results via a limit/offset
 * pair and need to tell the caller whether more results might exist beyond the
 * current page, without the backing query natively reporting a total count.
 */
trait PaginationTrait {
	/**
	 * Whether a page of results might be followed by more results.
	 *
	 * A page that came back with fewer items than the requested limit cannot
	 * be followed by more. A full page might be the last one too (the total
	 * count could be an exact multiple of $limit), but there is no way to
	 * tell without a second query, so a full page is treated as "more".
	 *
	 * @param array $items The page of results as returned for $limit
	 * @param non-negative-int|null $limit Requested page size, or null for "no limit" (never "more" in that case)
	 */
	protected function hasMoreResults(array $items, ?int $limit): bool {
		return $limit !== null && $limit > 0 && count($items) === $limit;
	}

	/**
	 * Builds a `Link: <url>; rel="next"` response header value pointing at the
	 * next page of the current request, keeping its path (including any route
	 * placeholders already resolved into it) and replacing the query string.
	 *
	 * @param array<string, mixed> $params Query parameters for the next page, e.g. the incremented offset
	 */
	protected function buildNextPageLinkHeader(IRequest $request, IURLGenerator $urlGenerator, array $params): string {
		$path = (string)parse_url($request->getRequestUri(), PHP_URL_PATH);
		$url = $urlGenerator->getAbsoluteURL($path) . '?' . http_build_query($params);
		return '<' . $url . '>; rel="next"';
	}
}
