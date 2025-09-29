<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Settings\Admin;

use OCP\IL10N;
use OCP\Settings\DeclarativeSettingsTypes;
use OCP\Settings\IDeclarativeSettingsForm;

class SystemAddressBookSettings implements IDeclarativeSettingsForm {

	public function __construct(
		private IL10N $l,
	) {
	}

	public function getSchema(): array {
		return [
			'id' => 'dav-admin-system-address-book',
			'priority' => 10,
			'section_type' => DeclarativeSettingsTypes::SECTION_TYPE_ADMIN,
			'section_id' => 'groupware',
			'storage_type' => DeclarativeSettingsTypes::STORAGE_TYPE_EXTERNAL,
			'title' => $this->l->t('System Address Book'),
			'description' => $this->l->t('The system address book contains contact information for all users in your instance.'),

			'fields' => [
				[
					'id' => 'system_addressbook_enabled',
					'title' => $this->l->t('Enable System Address Book'),
					'type' => DeclarativeSettingsTypes::CHECKBOX,
					'default' => false,
					'options' => [],
				],
			],
		];
	}

}
