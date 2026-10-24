<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CardDAV;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\ConfigLexicon;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IUser;
use function array_intersect;

/**
 * Applies the access rules of {@see SystemAddressbook} for a given viewer
 * outside of a DAV request.
 */
class SystemAddressBookVisibility {
	public function __construct(
		private IAppConfig $appConfig,
		private IGroupManager $groupManager,
	) {
	}

	/**
	 * Whether $viewer can see the system address book card of $target
	 * This depends on things such as the system address book being exposed,
	 * the viewer and target not being guests, and the share enumeration settings.
	 */
	public function isUserCardVisible(IUser $viewer, IUser $target): bool {
		if (!$this->appConfig->getValueBool(Application::APP_ID, ConfigLexicon::SYSTEM_ADDRESSBOOK_EXPOSED)) {
			return false;
		}
		if ($viewer->getUID() === $target->getUID()) {
			return true;
		}
		if ($viewer->getBackendClassName() === 'Guests' || $target->getBackendClassName() === 'Guests') {
			return false;
		}

		$shareEnumeration = $this->appConfig->getValueString('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes') === 'yes';
		$shareEnumerationGroup = $this->appConfig->getValueString('core', 'shareapi_restrict_user_enumeration_to_group', 'no') === 'yes';
		$shareEnumerationPhone = $this->appConfig->getValueString('core', 'shareapi_restrict_user_enumeration_to_phone', 'no') === 'yes';
		if (!$shareEnumeration || (!$shareEnumerationGroup && $shareEnumerationPhone)) {
			return false;
		}
		if ($shareEnumerationGroup) {
			return array_intersect(
				$this->groupManager->getUserGroupIds($viewer),
				$this->groupManager->getUserGroupIds($target),
			) !== [];
		}

		return true;
	}
}
