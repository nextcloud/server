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
	private IURLGenerator $urlGenerator;
	private IRequest $request;

	/**
	 * @param array $items The page of results as returned for $limit
	 * @param non-negative-int|null $limit Requested page size, or null for "no limit" (never "more" in that case)
	 */
	protected function hasMoreResults(array $items, ?int $limit): bool {
		return $limit !== null && $limit > 0 && count($items) === $limit;
	}

	/**
	 * Builds a `Link: <url>; rel="next"` response header value pointing at the
	 * next page of the current request.
	 *
	 * @param array<string, mixed> $params Query parameters for the next page, e.g. the incremented offset
	 */
	protected function buildOffsetNextPageLinkHeader(array $params, int $limit, int $offset): string {
		$params = array_merge($params, [
			'limit' => $limit,
			'offset' => $offset + $limit,
		]);
		$path = (string)parse_url($this->request->getRequestUri(), PHP_URL_PATH);
		$url = $this->urlGenerator->getAbsoluteURL($path) . '?' . http_build_query($params);
		return '<' . $url . '>; rel="next"';
	}

	/**
	 * Builds a `Link: <url>; rel="next"` response header value pointing at the
	 * next page of the current request, using keyset (seek) pagination instead
	 * of an offset.
	 *
	 * @param array<string, mixed> $params Query parameters for the next page
	 * @param int|string $lastId Id of the last entity of the current page
	 */
	protected function buildCursorNextPageLinkHeader(array $params, int $limit, int|string $lastId): string {
		$params = array_merge($params, [
			'limit' => $limit,
			'lastId' => $lastId,
		]);
		$path = (string)parse_url($this->request->getRequestUri(), PHP_URL_PATH);
		$url = $this->urlGenerator->getAbsoluteURL($path) . '?' . http_build_query($params);
		return '<' . $url . '>; rel="next"';
	}
}
