<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance;

use OCP\AppFramework\Attribute\Consumable;

/**
 * A label as it applies to one resource for one actor.
 *
 * @psalm-immutable
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
final readonly class ResourceLabel {
	/**
	 * @param non-empty-string $id
	 * @param string $displayName Name to show to the user
	 * @param ?string $color Color in hex format, e.g. `#0082c9`
	 * @param int $priority Order of the label within its type
	 * @param bool $assigned Whether the label is assigned to the resource
	 * @param LabelModification $canAssign Whether the actor may assign the label to the resource
	 * @param LabelModification $canRemove Whether the actor may remove the label from the resource
	 * @experimental 36.0.0
	 */
	public function __construct(
		public string $id,
		public LabelType $type,
		public string $displayName,
		public ?string $description,
		public ?string $color,
		public int $priority,
		public bool $assigned,
		public LabelModification $canAssign,
		public LabelModification $canRemove,
	) {
	}
}
