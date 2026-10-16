<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Whether an actor may change a label assignment. WITH_REASON is not a softer
 * YES: the change is permitted but the actor must supply a justification,
 * which is recorded — so a UI has to prompt for one rather than acting
 * straight away.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
enum LabelModification: string {
	/**
	 * @experimental 36.0.0
	 */
	case NO = 'no';
	/**
	 * @experimental 36.0.0
	 */
	case YES = 'yes';
	/**
	 * @experimental 36.0.0
	 */
	case WITH_REASON = 'with-reason';
}
