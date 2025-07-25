<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\App\AppStore\Bundles;

use OCP\IL10N;

class BundleFetcher {
	public function __construct(
		private IL10N $l10n,
	) {
	}

	/**
	 * @return Bundle[]
	 */
	public function getBundles(): array {
		return [
			new EnterpriseBundle($this->l10n),
			new HubBundle($this->l10n),
			new GroupwareBundle($this->l10n),
			new SocialSharingBundle($this->l10n),
			new EducationBundle($this->l10n),
			new PublicSectorBundle($this->l10n),
		];
	}

	/**
	 * Get the bundle with the specified identifier
	 *
	 * @param string $identifier
	 * @return Bundle
	 * @throws \BadMethodCallException If the bundle does not exist
	 */
	public function getBundleByIdentifier(string $identifier): Bundle {
		foreach ($this->getBundles() as $bundle) {
			if ($bundle->getIdentifier() === $identifier) {
				return $bundle;
			}
		}

		throw new \BadMethodCallException('Bundle with specified identifier does not exist');
	}
}
