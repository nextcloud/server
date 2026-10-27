<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

/**
 * App-owned navigation and optional embedded views on the Teams page.
 *
 * Register the implementation with IRegistrationContext::registerTeamAppProvider().
 *
 * @since 36.0.0
 */
interface ITeamAppProvider {
	/**
	 * Return the owning app ID.
	 *
	 * @since 36.0.0
	 */
	public function getId(): string;

	/**
	 * Prepare initial state/styles and return an optional embedded-view module URL.
	 *
	 * @since 36.0.0
	 */
	public function load(): ?string;

	/** @since 36.0.0 */
	public function getOrder(): int;

	/**
	 * Return alternate routes and the route parameter identifying an entry.
	 *
	 * @return list<TeamAppRouteAlias>
	 * @since 36.0.0
	 */
	public function getRouteAliases(): array;

	/**
	 * Return navigation entries, available actions and shared resources represented by the entries.
	 *
	 * @param array{teamId: string, teamName: string, userId: string, isAdmin: bool, folder: ?TeamFolder, resources: list<TeamResource>, navigationEntryIds: list<string>, saveNavigationEntryIds: callable} $context
	 * @return TeamAppNavigation Entries and optional actions and resource keys for this team.
	 * @since 36.0.0
	 */
	public function getNavigation(array $context): TeamAppNavigation;

	/**
	 * Execute an app action and return optional replacement navigation entry IDs and an entry ID to open.
	 *
	 * @param array{teamId: string, teamName: string, userId: string, isAdmin: bool, folder: ?TeamFolder, resources: list<TeamResource>, navigationEntryIds: list<string>, saveNavigationEntryIds: callable} $context
	 * @param array<string, mixed> $parameters
	 * @return TeamAppActionResult Omitted keys leave the current navigation and open entry unchanged.
	 * @since 36.0.0
	 */
	public function executeAction(array $context, string $actionId, array $parameters): TeamAppActionResult;
}
