<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Search;

/**
 * Interface for search filters
 *
 * @since 28.0.0
 */
interface IFilter {
	/** @since 28.0.0 */
	public const BUILTIN_TERM = 'term';
	/** @since 28.0.0 */
	public const BUILTIN_SINCE = 'since';
	/** @since 28.0.0 */
	public const BUILTIN_UNTIL = 'until';
	/** @since 28.0.0 */
	public const BUILTIN_PERSON = 'person';
	/** @since 28.0.0 */
	public const BUILTIN_TITLE_ONLY = 'title-only';
	/** @since 28.0.0 */
	public const BUILTIN_PLACES = 'places';
	/** @since 28.0.0 */
	public const BUILTIN_PROVIDER = 'provider';

	/**
	 * Get filter value
	 *
	 * @since 28.0.0
	 */
	public function get(): mixed;
}
