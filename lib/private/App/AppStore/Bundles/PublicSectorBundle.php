<?php
/**
 * @copyright Copyright (c) 2024 Stephan Orbaugh <stephan.orbaugh@nextcloud.com>
 *
 * @author Stephan Orbaugh <stephan.orbaugh@nextcloud.com>
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

class PublicSectorBundle extends Bundle {
	/**
	 * {@inheritDoc}
	 */
	public function getName(): string {
		return $this->l10n->t('Public sector bundle');
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAppIdentifiers(): array {

		return [
			'files_confidential',
			'forms',
			'collectives',
			'files_antivirus',
			'twofactor_nextcloud_notification',
			'tables',
			'richdocuments',
			'admin_audit',
			'files_retention',
		];
	}

}
