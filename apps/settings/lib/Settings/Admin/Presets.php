<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Settings\Admin;

use OC\Config\PresetManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ServerVersion;
use OCP\Settings\ISettings;

class Presets implements ISettings {
	public function __construct(
		private ServerVersion $serverVersion,
		private IConfig $config,
		private IL10N $l,
		private readonly PresetManager $presetManager,
		private IInitialState $initialState,
	) {
	}

	public function getForm() {
		$presets = $this->presetManager->retrieveLexiconPreset();
		$selectedPreset = $this->presetManager->getLexiconPreset();
		$presetsApps = $this->presetManager->retrieveLexiconPresetApps();

		$this->initialState->provideInitialState('settings-selected-preset', $selectedPreset->name);
		$this->initialState->provideInitialState('settings-presets', $presets);
		$this->initialState->provideInitialState('settings-presets-apps', $presetsApps);

		return new TemplateResponse('settings', 'settings/admin/presets', [], '');
	}

	public function getSection() {
		return 'presets';
	}

	public function getPriority() {
		return 0;
	}

	public function getName(): ?string {
		return $this->l->t('Settings presets');
	}

	public function getAuthorizedAppConfig(): array {
		return [];
	}
}
