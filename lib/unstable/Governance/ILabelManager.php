<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance;

use NCU\Governance\Exception\GovernanceUnavailableException;
use NCU\Governance\Exception\LabelNotFoundException;
use NCU\Governance\Exception\LabelPermissionException;
use NCU\Governance\Exception\ResourceNotFoundException;
use NCU\Resource\ResourceReference;
use OCP\Actor\Actor;
use OCP\AppFramework\Attribute\Consumable;
use OCP\AppFramework\Attribute\ExceptionalImplementable;

/**
 * Reads and changes the labels assigned to resources.
 *
 * The labels are managed by the governance app. Without it no labels are returned and they cannot be changed.
 * This is not an extension point, only the governance app implements it.
 *
 * The governance app decides which labels an actor sees and may change. This usually requires access
 * to the resource, but not always, e.g. a legal hold is placed by someone the resource is not shared with.
 *
 * Only labels assigned to the resource itself are returned,
 * e.g. not the ones a file inherits from its parent folders.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
#[ExceptionalImplementable(app: 'governance')]
interface ILabelManager {
	/**
	 * Whether labels can be read and changed.
	 *
	 * @experimental 36.0.0
	 */
	public function isAvailable(): bool;

	/**
	 * Get the labels assigned to the resources, the resources can be of different types.
	 *
	 * The labels can be of any type. A resource has no labels if the actor may not see any of them.
	 *
	 * @param list<ResourceReference> $resources
	 * @return array<non-empty-string, list<ResourceLabel>> keyed by {@see ResourceReference::key}, with an entry for every resource
	 * @throws \InvalidArgumentException if a resource type is not registered
	 * @experimental 36.0.0
	 */
	public function getAssignedLabelsForResources(Actor $actor, array $resources): array;

	/**
	 * Same as {@see self::getAssignedLabelsForResources} for a single resource.
	 *
	 * @return list<ResourceLabel>
	 * @throws \InvalidArgumentException if the resource type is not registered
	 * @experimental 36.0.0
	 */
	public function getAssignedLabels(Actor $actor, ResourceReference $resource): array;

	/**
	 * Get the labels that apply to the resource, including the ones already assigned.
	 *
	 * Whether the actor may assign or remove a label is part of the label.
	 * No labels are returned if the actor may not label the resource.
	 *
	 * @param ?LabelType $type Only return labels of this type
	 * @return list<ResourceLabel>
	 * @throws \InvalidArgumentException if the resource type is not registered
	 * @experimental 36.0.0
	 */
	public function getAssignableLabels(Actor $actor, ResourceReference $resource, ?LabelType $type = null): array;

	/**
	 * Assign the label to the resource.
	 *
	 * A sensitivity label replaces the one currently assigned.
	 *
	 * @param non-empty-string $labelId
	 * @param ?string $reason Required if the label can only be assigned with a reason, see {@see ResourceLabel::$canAssign}
	 * @throws \InvalidArgumentException if the resource type is not registered
	 * @throws GovernanceUnavailableException
	 * @throws ResourceNotFoundException
	 * @throws LabelNotFoundException
	 * @throws LabelPermissionException
	 * @experimental 36.0.0
	 */
	public function assignLabel(Actor $actor, ResourceReference $resource, string $labelId, ?string $reason = null): void;

	/**
	 * Remove the label from the resource.
	 *
	 * @param non-empty-string $labelId
	 * @param ?string $reason Required if the label can only be removed with a reason, see {@see ResourceLabel::$canRemove}
	 * @throws \InvalidArgumentException if the resource type is not registered
	 * @throws GovernanceUnavailableException
	 * @throws ResourceNotFoundException
	 * @throws LabelNotFoundException
	 * @throws LabelPermissionException
	 * @experimental 36.0.0
	 */
	public function removeLabel(Actor $actor, ResourceReference $resource, string $labelId, ?string $reason = null): void;
}
