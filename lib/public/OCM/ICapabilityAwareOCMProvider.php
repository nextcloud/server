<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\OCM;

/**
 * Version 1.1 and 1.2 extensions to the Open Cloud Mesh Discovery API
 * @link https://github.com/cs3org/OCM-API/
 * @since 32.0.0
 * @deprecated 33.0.0 {@see IOCMProvider}
 */
interface ICapabilityAwareOCMProvider extends IOCMProvider {
}
