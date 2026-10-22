<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance;

use OCP\AppFramework\Attribute\Consumable;

/**
 * A label as it applies to one resource for one actor — enough to render it
 * and to know whether this actor may take it off again. Which policy put it
 * within reach, and how it won over any competing label, stays inside
 * Governance.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
final readonly class Label {
	/**
	 * @experimental 36.0.0
	 */
	public function __construct(
		public string $id,
		public LabelType $type,
		public string $displayName,
		public ?string $description = null,
		public ?string $color = null,
		public LabelModification $canRemove = LabelModification::NO,
	) {
	}
}
