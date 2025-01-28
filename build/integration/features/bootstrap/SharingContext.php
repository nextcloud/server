<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;

require __DIR__ . '/../../vendor/autoload.php';


/**
 * Features context.
 */
class SharingContext implements Context, SnippetAcceptingContext {
	use WebDav;
	use Trashbin;
	use AppConfiguration;
	use CommandLine;

	protected function resetAppConfigs() {
		$this->deleteServerConfig('core', 'shareapi_default_permissions');
		$this->deleteServerConfig('core', 'shareapi_default_internal_expire_date');
		$this->deleteServerConfig('core', 'shareapi_internal_expire_after_n_days');
		$this->deleteServerConfig('core', 'internal_defaultExpDays');
		$this->deleteServerConfig('core', 'shareapi_enforce_links_password');
		$this->deleteServerConfig('core', 'shareapi_default_expire_date');
		$this->deleteServerConfig('core', 'shareapi_expire_after_n_days');
		$this->deleteServerConfig('core', 'link_defaultExpDays');
		$this->deleteServerConfig('files_sharing', 'outgoing_server2server_share_enabled');

		$this->runOcc(['config:system:delete', 'share_folder']);
	}
}
