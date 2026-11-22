<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Config;

use OCP\Files\Mount\IShareOwnerlessMount;
use OCP\Files\Mount\ISystemMountPoint;

class SystemMountPoint extends ExternalMountPoint implements ISystemMountPoint, IShareOwnerlessMount {
}
