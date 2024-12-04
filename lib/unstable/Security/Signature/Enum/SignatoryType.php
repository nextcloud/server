<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace NCU\Security\Signature\Enum;

/**
 * type of link between local and remote instance
 *
 * - FORGIVABLE = the keypair can be deleted and refreshed anytime; silently
 * - REFRESHABLE = the keypair can be refreshed but a notice will be generated
 * - TRUSTED = any changes of keypair will require human interaction, warning will be issued
 * - STATIC = error will be issued on conflict,  assume keypair cannot be reset.
 *
 * @experimental 31.0.0
 */
enum SignatoryType: int {
	/** @experimental 31.0.0 */
	case FORGIVABLE = 1; // no notice on refresh
	/** @experimental 31.0.0 */
	case REFRESHABLE = 4; // notice on refresh
	/** @experimental 31.0.0 */
	case TRUSTED = 8; // warning on refresh
	/** @experimental 31.0.0 */
	case STATIC = 9; // error on refresh
}
