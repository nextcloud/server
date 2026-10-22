<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance;

use NCU\Governance\Context\Context;
use NCU\Governance\Resource\Exception\ResourceNotFoundException;
use OCP\AppFramework\Attribute\Consumable;

/**
 * Reading and changing the labels on a resource — for rendering a badge, a
 * label picker, or an action that depends on what is currently assigned.
 *
 * This is for display and authoring.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface IGovernanceLabelManager {
	/**
	 * Labels currently on each of these resources, as $context is allowed to see
	 * them — for rendering a list without asking once per row. A resource
	 * $context cannot see is omitted rather than failing the whole batch.
	 *
	 * @param non-empty-lowercase-string $resourceIdentifier
	 * @param non-empty-list<string> $resourceIds
	 * @return array<string, list<ILabel>> resource id => labels
	 * @throws GovernanceUnavailableException when no backend is registered
	 * @experimental 36.0.0
	 */
	public function getAssignedLabelsForResources(Context $context, string $resourceIdentifier, array $resourceIds): array;

	/**
	 * Labels $context may put on each of these resources — what a picker renders
	 * for a list of rows.
	 *
	 * @param non-empty-lowercase-string $resourceIdentifier
	 * @param non-empty-list<string> $resourceIds
	 * @return array<string, list<ILabel>> resource id => labels
	 * @throws GovernanceUnavailableException when no backend is registered
	 * @experimental 36.0.0
	 */
	public function getAssignableLabelsForResources(Context $context, string $resourceIdentifier, array $resourceIds, LabelType $labelType): array;

	/**
	 * Labels currently on this resource, as $context is allowed to see them.
	 *
	 * @param non-empty-lowercase-string $resourceIdentifier
	 * @return list<ILabel>
	 * @throws GovernanceUnavailableException when no backend is registered
	 * @throws ResourceNotFoundException when $context cannot see the resource
	 * @experimental 36.0.0
	 */
	public function getAssignedLabels(Context $context, string $resourceIdentifier, string $resourceId): array;

	/**
	 * Labels $context may put on this resource — what a picker renders.
	 *
	 * @param non-empty-lowercase-string $resourceIdentifier
	 * @return list<ILabel>
	 * @throws GovernanceUnavailableException when no backend is registered
	 * @throws ResourceNotFoundException when $context cannot see the resource
	 * @experimental 36.0.0
	 */
	public function getAssignableLabels(Context $context, string $resourceIdentifier, string $resourceId, LabelType $labelType): array;

	/**
	 * @param non-empty-lowercase-string $resourceIdentifier
	 * @throws GovernanceUnavailableException when no backend is registered
	 * @throws LabelPermissionException when $context may not assign this label here
	 * @throws ResourceNotFoundException when $context cannot see the resource
	 * @experimental 36.0.0
	 */
	public function setLabel(Context $context, string $resourceIdentifier, string $resourceId, LabelType $labelType, string $labelId): void;

	/**
	 * @param non-empty-lowercase-string $resourceIdentifier
	 * @throws GovernanceUnavailableException when no backend is registered
	 * @throws LabelPermissionException when $context may not remove this label here
	 * @throws ResourceNotFoundException when $context cannot see the resource
	 * @experimental 36.0.0
	 */
	public function removeLabel(Context $context, string $resourceIdentifier, string $resourceId, LabelType $labelType, string $labelId): void;
}
