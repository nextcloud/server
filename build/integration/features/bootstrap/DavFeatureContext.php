<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use Behat\Behat\Context\Context;
use Behat\Step\Given;

require __DIR__ . '/autoload.php';

class DavFeatureContext implements Context {
	use AppConfiguration;
	use ContactsMenu;
	use ExternalStorage;
	use Search;
	use WebDav;
	use Trashbin;

	#[Given('link sharing is disabled for group :group')]
	public function linkSharingIsDisabledForGroup(string $group): void {
		$this->serverParameterIsSetTo('shareapi_allow_links_exclude_groups', 'core', json_encode([$group]));
	}

	protected function resetAppConfigs() {
		$this->deleteServerConfig('files_sharing', 'outgoing_server2server_share_enabled');
		$this->deleteServerConfig('core', 'shareapi_allow_links');
		$this->deleteServerConfig('core', 'shareapi_allow_links_exclude_groups');
	}
}
