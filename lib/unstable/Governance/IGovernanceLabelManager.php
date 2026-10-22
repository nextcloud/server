<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance;

use NCU\Governance\Actor\Actor;
use NCU\Governance\Resource\Exception\ResourceNotFoundException;
use NCU\Governance\Resource\IGovernanceResource;
use OCA\Governance\Db\LabelType;
use OCP\AppFramework\Attribute\Consumable;

/**
 * Reading and changing the labels on a resource — for rendering a badge, a
 * label picker, or an action that depends on what is currently assigned.
 *
 * Always injectable, whether or not Governance is installed: check
 * {@see isAvailable()} once rather than guarding every call site.
 *
 * This is for display and authoring. Enforcement goes through
 * `NCU\Governance\ResourceRight\RestrictRightEvent` — never by inspecting the label
 * list and deciding for yourself what it implies.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface IGovernanceLabelManager {
	/**
	 * Labels currently on each of these resources, as $actor is allowed to see
	 * them — for rendering a list without asking once per row. A resource
	 * $actor cannot see is omitted rather than failing the whole batch.
	 *
	 * @param class-string<IGovernanceResource> $resourceType
	 * @param non-empty-list<string> $resourceIds
	 * @return array<string, list<Label>> resource id => labels
	 * @throws GovernanceUnavailableException when no backend is registered
	 * @experimental 36.0.0
	 */
	public function getAssignedLabelsForResources(string $resourceType, array $resourceIds, Actor $actor): array;

	/**
	 * Labels $actor may put on each of these resources — what a picker renders
	 * for a list of rows.
	 *
	 * @param class-string<IGovernanceResource> $resourceType
	 * @param non-empty-list<string> $resourceIds
	 * @return array<string, list<Label>> resource id => labels
	 * @throws GovernanceUnavailableException when no backend is registered
	 * @experimental 36.0.0
	 */
	public function getAssignableLabelsForResources(string $resourceType, array $resourceIds, LabelType $labelType, Actor $actor): array;

	/**
	 * Labels currently on this resource, as $actor is allowed to see them.
	 *
	 * @param class-string<IGovernanceResource> $resourceType
	 * @return list<Label>
	 * @throws GovernanceUnavailableException when no backend is registered
	 * @throws ResourceNotFoundException when $actor cannot see the resource
	 * @experimental 36.0.0
	 */
	public function getAssignedLabels(string $resourceType, string $resourceId, Actor $actor): array;

	/**
	 * Labels $actor may put on this resource — what a picker renders.
	 *
	 * @param class-string<IGovernanceResource> $resourceType
	 * @return list<Label>
	 * @throws GovernanceUnavailableException when no backend is registered
	 * @throws ResourceNotFoundException when $actor cannot see the resource
	 * @experimental 36.0.0
	 */
	public function getAssignableLabels(string $resourceType, string $resourceId, LabelType $labelType, Actor $actor): array;

	/**
	 * @param class-string<IGovernanceResource> $resourceType
	 * @throws GovernanceUnavailableException when no backend is registered
	 * @throws LabelPermissionException when $actor may not assign this label here
	 * @throws ResourceNotFoundException when $actor cannot see the resource
	 * @experimental 36.0.0
	 */
	public function setLabel(string $resourceType, string $resourceId, LabelType $labelType, string $labelId, Actor $actor): void;

	/**
	 * @param class-string<IGovernanceResource> $resourceType
	 * @throws GovernanceUnavailableException when no backend is registered
	 * @throws LabelPermissionException when $actor may not remove this label here
	 * @throws ResourceNotFoundException when $actor cannot see the resource
	 * @experimental 36.0.0
	 */
	public function removeLabel(string $resourceType, string $resourceId, LabelType $labelType, string $labelId, Actor $actor): void;

	/**
	 * @param class-string<IGovernanceResource> $resourceType
	 * @throws GovernanceUnavailableException when no backend is registered
	 * @throws LabelPermissionException when $actor may not change the label
	 * @throws ResourceNotFoundException when the resource does not exist or $actor does not have access to it
	 * @experimental 36.0.0
	 */
	public function changeLabel(string $resourceType, string $resourceId, LabelType $labelType, string $labelId, Actor $actor): void;
}
