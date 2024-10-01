<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV;

use OCA\DAV\CalDAV\InvitationResponse\InvitationResponseServer;

class ServerFactory {

	public function createInviationResponseServer(bool $public): InvitationResponseServer {
		return new InvitationResponseServer(false);
	}
}
