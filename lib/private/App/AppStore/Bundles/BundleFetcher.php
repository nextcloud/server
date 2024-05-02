<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@statuscode.ch>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
