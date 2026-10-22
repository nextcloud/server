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
 * and to know whether this actor may take it off again.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface ILabel {
	/**
	 * The ID of the label.
	 *
	 * @experimental 36.0.0
	 */
	public function getId(): string;

	/**
	 * The type of label.
	 *
	 * @experimental 36.0.0
	 */
	public function getType(): LabelType;

	/**
	 * The name of the label.
	 *
	 * @experimental 36.0.0
	 */
	public function getDisplayName(): string;

	/**
	 * A description for the label.
	 *
	 * @experimental 36.0.0
	 */
	public function getDescription(): ?string;

	/**
	 * A color in hex format to use to display the label.
	 *
	 * @experimental 36.0.0
	 */
	public function getColor(): ?string;

	/**
	 * Whether the label can be assigned or not.
	 *
	 * NOTE: please refrain from matching on {@see LabelModification} without a default
	 *   branch as future addition are likely to happen and would break your app.
	 *   For this reason please treat any unknown value as "no".
	 *
	 * @experimental 36.0.0
	 */
	public function getCanAssign(): LabelModification;

	/**
	 * Whether the label can be removed or not.
	 *
	 * NOTE: please refrain from matching on {@see LabelModification} without a default
	 *   branch as future addition are likely to happen and would break your app.
	 *   For this reason please treat any unknown value as "no".
	 *
	 * @experimental 36.0.0
	 */
	public function getCanRemove(): LabelModification;
}
