<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Search;

enum MetadataFieldStatus: string {
	case Captured = 'captured';

	/** The source has no such concept. */
	// TODO: not sure if actually needed
	case NotApplicable = 'not_applicable';

	/** The source has the concept but the read failed; the item is still collected. */
	case NotCaptured = 'not_captured';
}
