<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Testing\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Settings\DeclarativeSettingsTypes;
use OCP\Settings\Events\DeclarativeSettingsRegisterFormEvent;

/**
 * @template-implements IEventListener<DeclarativeSettingsRegisterFormEvent>
 */
class RegisterDeclarativeSettingsListener implements IEventListener {

	public function __construct() {
	}

	public function handle(Event $event): void {
		if (!($event instanceof DeclarativeSettingsRegisterFormEvent)) {
			// Unrelated
			return;
		}

		$event->registerSchema('testing', [
			'id' => 'test_declarative_form_event',
			'priority' => 20,
			'section_type' => DeclarativeSettingsTypes::SECTION_TYPE_ADMIN,
			'section_id' => 'additional',
			'storage_type' => DeclarativeSettingsTypes::STORAGE_TYPE_INTERNAL,
			'title' => 'Test declarative settings event', // NcSettingsSection name
			'description' => 'This form is registered via the RegisterDeclarativeSettingsFormEvent', // NcSettingsSection description
			'fields' => [
				[
					'id' => 'event_field_1',
					'title' => 'Why is 42 this answer to all questions?',
					'description' => 'Hint: It\'s not',
					'type' => DeclarativeSettingsTypes::TEXT,
					'placeholder' => 'Enter your answer',
					'default' => 'Because it is',
				],
				[
					'id' => 'feature_rating',
					'title' => 'How would you rate this feature?',
					'description' => 'Your vote is not anonymous',
					'type' => DeclarativeSettingsTypes::RADIO, // radio, radio-button (NcCheckboxRadioSwitch button-variant)
					'label' => 'Select single toggle',
					'default' => '3',
					'options' => [
						[
							'name' => 'Awesome', // NcCheckboxRadioSwitch display name
							'value' => '1' // NcCheckboxRadioSwitch value
						],
						[
							'name' => 'Very awesome',
							'value' => '2'
						],
						[
							'name' => 'Super awesome',
							'value' => '3'
						],
					],
				],
			],
		]);
	}
}
