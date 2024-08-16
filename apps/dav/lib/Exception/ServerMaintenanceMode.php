<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Exception;

use Sabre\DAV\Exception\ServiceUnavailable;

class ServerMaintenanceMode extends ServiceUnavailable {

}
