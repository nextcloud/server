<?php
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;

require __DIR__ . '/../../vendor/autoload.php';

class DavFeatureContext implements Context, SnippetAcceptingContext {
	use AppConfiguration;
	use ContactsMenu;
	use ExternalStorage;
	use Search;
	use WebDav;
	use Trashbin;

	protected function resetAppConfigs() {
		$this->deleteServerConfig('files_sharing', 'outgoing_server2server_share_enabled');
	}
}
