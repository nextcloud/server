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
 * Helper for OCS controllers that paginate a limit/offset list and need to report
 * whether more results exist, without the query providing a total count.
 *
 * Relies on the composing class providing `$this->request` (already declared by
 * OCP\AppFramework\Controller) and its own `$this->urlGenerator`; not declared here
 * to avoid property composition conflicts with classes that mark theirs readonly.
 *
 * @property IRequest $request
 * @property IURLGenerator $urlGenerator
 */
trait PaginationTrait {
	/**
	 * @param array $items The page of results as returned for $limit
	 * @param non-negative-int|null $limit Requested page size, or null for "no limit" (never "more" in that case)
	 */
	protected function hasMoreResults(array $items, ?int $limit): bool {
		return $limit !== null && $limit > 0 && count($items) >= $limit;
	}

	/**
	 * @param array|bool $items The page of results, or an already-known "has more" bool
	 *                          when the caller has a more authoritative source than the
	 *                          item count (e.g. a search backend)
	 * @param non-negative-int|null $limit
	 */
	private function resolveHasMoreResults(array|bool $items, ?int $limit): bool {
		return is_bool($items) ? $items : $this->hasMoreResults($items, $limit);
	}

	/**
	 * Builds the `headers` for a DataResponse: a `Link: <url>; rel="next"` header if
	 * there's a next page, or an empty array otherwise.
	 *
	 * @param array<string, mixed> $params Query parameters for the next page, e.g. the incremented offset
	 * @param non-negative-int|null $limit
	 * @return array{Link?: string}
	 */
	protected function buildOffsetNextPageLinkHeader(array|bool $items, array $params, ?int $limit, int $offset): array {
		if (!$this->resolveHasMoreResults($items, $limit)) {
			return [];
		}

		$params = array_merge($params, [
			'limit' => ($limit ?? 100),
			'offset' => $offset + ($limit ?? 100),
		]);
		$path = (string)parse_url($this->request->getRequestUri(), PHP_URL_PATH);
		$url = $this->urlGenerator->getAbsoluteURL($path) . '?' . http_build_query($params);
		return ['Link' => '<' . $url . '>; rel="next"'];
	}

	/**
	 * Same as buildOffsetNextPageLinkHeader(), but using keyset (seek) pagination
	 * instead of an offset.
	 *
	 * @param array<string, mixed> $params Query parameters for the next page
	 * @param int|string|null $lastId Id of the last entity of the current page
	 * @param non-negative-int|null $limit
	 * @return array{Link?: string}
	 */
	protected function buildCursorNextPageLinkHeader(array|bool $items, array $params, ?int $limit, int|string|null $lastId): array {
		if ($lastId === null || !$this->resolveHasMoreResults($items, $limit)) {
			return [];
		}

		$params = array_merge($params, [
			'limit' => $limit,
			'lastId' => $lastId,
		]);
		$path = (string)parse_url($this->request->getRequestUri(), PHP_URL_PATH);
		$url = $this->urlGenerator->getAbsoluteURL($path) . '?' . http_build_query($params);
		return ['Link' => '<' . $url . '>; rel="next"'];
	}
}
