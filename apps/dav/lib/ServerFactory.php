<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV;

use OCA\DAV\CalDAV\InvitationResponse\InvitationResponseServer;
use OCA\DAV\Connector\Sabre\Server;

class ServerFactory {

	public function createInviationResponseServer(bool $public): InvitationResponseServer {
		return new InvitationResponseServer(false);
	}

	public function createAttendeeAvailabilityServer(): Server {
		return (new InvitationResponseServer(false))->getServer();
	}
}
