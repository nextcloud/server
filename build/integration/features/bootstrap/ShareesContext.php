<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;

require __DIR__ . '/../../vendor/autoload.php';


/**
 * Features context.
 */
class ShareesContext implements Context, SnippetAcceptingContext {
	use Sharing;
	use AppConfiguration;

	protected function resetAppConfigs() {
		$this->deleteServerConfig('core', 'shareapi_only_share_with_group_members');
		$this->deleteServerConfig('core', 'shareapi_allow_share_dialog_user_enumeration');
		$this->deleteServerConfig('core', 'shareapi_allow_group_sharing');
	}
}
