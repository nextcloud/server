<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\App\AppStore;

use OC\Core\AppInfo\ConfigLexicon;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\Support\Subscription\IRegistry;

/**
 * Decides whether the app store link is offered to accounts without admin rights.
 */
class AppStoreLinkVisibility {
	public function __construct(
		private readonly IConfig $config,
		private readonly IAppConfig $appConfig,
		private readonly IRegistry $registry,
	) {
	}

	/**
	 * An explicitly set value wins. Without one, a disabled app store or an
	 * available subscription hides the link.
	 */
	public function isShownToUsers(): bool {
		if (!$this->appConfig->hasKey('core', ConfigLexicon::APPSTORE_LINK_SHOWN)) {
			if (!$this->config->getSystemValueBool('appstoreenabled', true)) {
				return false;
			}

			if ($this->registry->delegateHasValidSubscription()) {
				return false;
			}
		}

		return $this->appConfig->getValueBool('core', ConfigLexicon::APPSTORE_LINK_SHOWN);
	}
}
