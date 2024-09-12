<?php
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
			'whiteboard',
		];
	}

}
