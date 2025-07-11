<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Settings\Admin;

use OCA\Settings\AppInfo\Application;
use OCA\Settings\Service\AuthorizedGroupService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\Settings\IDelegatedSettings;
use OCP\Settings\IManager;
use OCP\Settings\ISettings;

class Delegation implements ISettings {
	public function __construct(
		private IManager $settingManager,
		private IInitialState $initialStateService,
		private IGroupManager $groupManager,
		private AuthorizedGroupService $authorizedGroupService,
		private IURLGenerator $urlGenerator,
	) {
	}

	/**
	 * Filter out the ISettings that are not IDelegatedSettings from $innerSection
	 * and add them to $settings.
	 *
	 * @param IDelegatedSettings[] $settings
	 * @param ISettings[] $innerSection
	 * @return IDelegatedSettings[]
	 */
	private function getDelegatedSettings(array $settings, array $innerSection): array {
		foreach ($innerSection as $setting) {
			if ($setting instanceof IDelegatedSettings) {
				$settings[] = $setting;
			}
		}
		return $settings;
	}

	private function initSettingState(): void {
		// Available settings page initialization
		$sections = $this->settingManager->getAdminSections();
		$settings = [];
		foreach ($sections as $sectionPriority) {
			foreach ($sectionPriority as $section) {
				$sectionSettings = $this->settingManager->getAdminSettings($section->getId());
				$sectionSettings = array_reduce($sectionSettings, [$this, 'getDelegatedSettings'], []);
				$settings = array_merge(
					$settings,
					array_map(function (IDelegatedSettings $setting) use ($section) {
						$sectionName = $section->getName() . ($setting->getName() !== null ? ' - ' . $setting->getName() : '');
						return [
							'class' => get_class($setting),
							'sectionName' => $sectionName,
							'id' => mb_strtolower(str_replace(' ', '-', $sectionName)),
							'priority' => $section->getPriority(),
						];
					}, $sectionSettings)
				);
			}
		}
		usort($settings, function (array $a, array $b) {
			if ($a['priority'] == $b['priority']) {
				return 0;
			}
			return ($a['priority'] < $b['priority']) ? -1 : 1;
		});
		$this->initialStateService->provideInitialState('available-settings', $settings);
	}

	public function initAvailableGroupState(): void {
		// Available groups initialization
		$groups = [];
		$groupsClass = $this->groupManager->search('');
		foreach ($groupsClass as $group) {
			if ($group->getGID() === 'admin') {
				continue; // Admin already have access to everything
			}
			$groups[] = [
				'displayName' => $group->getDisplayName(),
				'gid' => $group->getGID(),
			];
		}
		$this->initialStateService->provideInitialState('available-groups', $groups);
	}

	public function initAuthorizedGroupState(): void {
		// Already set authorized groups
		$this->initialStateService->provideInitialState('authorized-groups', $this->authorizedGroupService->findAll());
	}

	public function getForm(): TemplateResponse {
		$this->initSettingState();
		$this->initAvailableGroupState();
		$this->initAuthorizedGroupState();
		$this->initialStateService->provideInitialState('authorized-settings-doc-link', $this->urlGenerator->linkToDocs('admin-delegation'));

		return new TemplateResponse(Application::APP_ID, 'settings/admin/delegation', [], '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return 'admindelegation';
	}

	/*
	 * @inheritdoc
	 */
	public function getPriority() {
		return 75;
	}
}
