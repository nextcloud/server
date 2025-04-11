<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Capabilities;

/**
 * Indicate that a capability should not be injected in the initial state
 * of the page as it might be expensive to query and not useful for the
 * webui.
 *
 * @since 24.0.0
 */
interface IInitialStateExcludedCapability {
}
