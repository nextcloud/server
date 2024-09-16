<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Listener;

use OCA\Files\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IL10N;
use OCP\Settings\DeclarativeSettingsTypes;
use OCP\Settings\Events\DeclarativeSettingsRegisterFormEvent;

/** @template-implements IEventListener<DeclarativeSettingsRegisterFormEvent> */
class DeclarativeSettingsRegisterFormEventListener implements IEventListener {

	public function __construct(
		private IL10N $l,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof DeclarativeSettingsRegisterFormEvent)) {
			return;
		}

		$event->registerSchema(Application::APP_ID, [
			'id' => 'files-filename-support',
			'priority' => 10,
			'section_type' => DeclarativeSettingsTypes::SECTION_TYPE_ADMIN,
			'section_id' => 'server',
			'storage_type' => DeclarativeSettingsTypes::STORAGE_TYPE_EXTERNAL,
			'title' => $this->l->t('Files compatibility'),
			'description' => $this->l->t('Allow to restrict filenames to ensure files can be synced with all clients. By default all filenames valid on POSIX (e.g. Linux or macOS) are allowed.'),

			'fields' => [
				[
					'id' => 'windows_support',
					'title' => $this->l->t('Enforce Windows compatibility'),
					'description' => $this->l->t('This will block filenames not valid on Windows systems, like using reserved names or special characters. But this will not enforce compatibility of case sensitivity.'),
					'type' => DeclarativeSettingsTypes::CHECKBOX,
					'default' => false,
				],
			],
		]);
	}
}
