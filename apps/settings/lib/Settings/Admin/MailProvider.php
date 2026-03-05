<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Settings\Admin;

use OCP\IL10N;
use OCP\Settings\DeclarativeSettingsTypes;
use OCP\Settings\IDeclarativeSettingsForm;

class MailProvider implements IDeclarativeSettingsForm {

	public function __construct(
		private IL10N $l,
	) {
	}

	public function getSchema(): array {
		return [
			'id' => 'mail-provider-support',
			'priority' => 10,
			'section_type' => DeclarativeSettingsTypes::SECTION_TYPE_ADMIN,
			'section_id' => 'server',
			'storage_type' => DeclarativeSettingsTypes::STORAGE_TYPE_EXTERNAL,
			'title' => $this->l->t('Mail Providers'),
			'description' => $this->l->t('Mail provider enables sending emails directly through the user\'s personal email account. At present, this functionality is limited to calendar invitations. It requires Nextcloud Mail 4.1 and an email account in Nextcloud Mail that matches the user\'s email address in Nextcloud.'),

			'fields' => [
				[
					'id' => 'mail_providers_enabled',
					'title' => $this->l->t('Send emails using'),
					'type' => DeclarativeSettingsTypes::RADIO,
					'default' => 1,
					'options' => [
						[
							'name' => $this->l->t('User\'s email account'),
							'value' => 1
						],
						[
							'name' => $this->l->t('System email account'),
							'value' => 0
						],
					],
				],
			],
		];
	}

}
