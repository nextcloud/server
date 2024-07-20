<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Controller;

use OCA\Settings\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Group\ISubAdmin;
use OCP\IGroupManager;
use OCP\INavigationManager;
use OCP\IUserSession;
use OCP\Settings\IDeclarativeManager;
use OCP\Settings\IDeclarativeSettingsForm;
use OCP\Settings\IIconSection;
use OCP\Settings\IManager as ISettingsManager;
use OCP\Settings\ISettings;
use OCP\Util;

/**
 * @psalm-import-type DeclarativeSettingsFormField from IDeclarativeSettingsForm
 */
trait CommonSettingsTrait {

	/** @var ISettingsManager */
	private $settingsManager;

	/** @var INavigationManager */
	private $navigationManager;

	/** @var IUserSession */
	private $userSession;

	/** @var IGroupManager */
	private $groupManager;

	/** @var ISubAdmin */
	private $subAdmin;

	private IDeclarativeManager $declarativeSettingsManager;

	/** @var IInitialState */
	private $initialState;

	/**
	 * @return array{forms: array{personal: array, admin: array}}
	 */
	private function getNavigationParameters(string $currentType, string $currentSection): array {
		return [
			'forms' => [
				'personal' => $this->formatPersonalSections($currentType, $currentSection),
				'admin' => $this->formatAdminSections($currentType, $currentSection),
			],
		];
	}

	/**
	 * @param IIconSection[][] $sections
	 * @psalm-param 'admin'|'personal' $type
	 * @return list<array{anchor: string, section-name: string, active: bool, icon: string}>
	 */
	protected function formatSections(array $sections, string $currentSection, string $type, string $currentType): array {
		$templateParameters = [];
		foreach ($sections as $prioritizedSections) {
			foreach ($prioritizedSections as $section) {
				if ($type === 'admin') {
					$settings = $this->settingsManager->getAllowedAdminSettings($section->getID(), $this->userSession->getUser());
				} elseif ($type === 'personal') {
					$settings = $this->settingsManager->getPersonalSettings($section->getID());
				}

				/** @psalm-suppress PossiblyNullArgument */
				$declarativeFormIDs = $this->declarativeSettingsManager->getFormIDs($this->userSession->getUser(), $type, $section->getID());

				if (empty($settings) && empty($declarativeFormIDs) && !($section->getID() === 'additional' && count(\OC_App::getForms('admin')) > 0)) {
					continue;
				}

				$icon = $section->getIcon();

				$active = $section->getID() === $currentSection
					&& $type === $currentType;

				$templateParameters[] = [
					'anchor' => $section->getID(),
					'section-name' => $section->getName(),
					'active' => $active,
					'icon' => $icon,
				];
			}
		}
		return $templateParameters;
	}

	protected function formatPersonalSections(string $currentType, string $currentSection): array {
		$sections = $this->settingsManager->getPersonalSections();
		return $this->formatSections($sections, $currentSection, 'personal', $currentType);
	}

	protected function formatAdminSections(string $currentType, string $currentSection): array {
		$sections = $this->settingsManager->getAdminSections();
		return $this->formatSections($sections, $currentSection, 'admin', $currentType);
	}

	/**
	 * @param array<int, list<\OCP\Settings\ISettings>> $settings
	 * @return array{content: string}
	 */
	private function formatSettings(array $settings): array {
		$html = '';
		foreach ($settings as $prioritizedSettings) {
			foreach ($prioritizedSettings as $setting) {
				/** @var ISettings $setting */
				$form = $setting->getForm();
				$html .= $form->renderAs('')->render();
			}
		}
		return ['content' => $html];
	}

	/**
	 * @psalm-param 'admin'|'personal' $type
	 */
	private function getIndexResponse(string $type, string $section): TemplateResponse {
		if ($type === 'personal') {
			if ($section === 'theming') {
				$this->navigationManager->setActiveEntry('accessibility_settings');
			} else {
				$this->navigationManager->setActiveEntry('settings');
			}
		} elseif ($type === 'admin') {
			$this->navigationManager->setActiveEntry('admin_settings');
		}

		$this->declarativeSettingsManager->loadSchemas();

		$templateParams = [];
		$templateParams = array_merge($templateParams, $this->getNavigationParameters($type, $section));
		$templateParams = array_merge($templateParams, $this->getSettings($section));

		/** @psalm-suppress PossiblyNullArgument */
		$declarativeFormIDs = $this->declarativeSettingsManager->getFormIDs($this->userSession->getUser(), $type, $section);
		if (!empty($declarativeFormIDs)) {
			foreach ($declarativeFormIDs as $app => $ids) {
				/** @psalm-suppress PossiblyUndefinedArrayOffset */
				$templateParams['content'] .= join(array_map(fn (string $id) => '<div id="' . $app . '_' . $id . '"></div>', $ids));
			}
			Util::addScript(Application::APP_ID, 'declarative-settings-forms');
			/** @psalm-suppress PossiblyNullArgument */
			$this->initialState->provideInitialState('declarative-settings-forms', $this->declarativeSettingsManager->getFormsWithValues($this->userSession->getUser(), $type, $section));
		}

		$activeSection = $this->settingsManager->getSection($type, $section);
		if ($activeSection) {
			$templateParams['pageTitle'] = $activeSection->getName();
			$templateParams['activeSectionId'] = $activeSection->getID();
			$templateParams['activeSectionType'] = $type;
		}

		return new TemplateResponse('settings', 'settings/frame', $templateParams);
	}

	abstract protected function getSettings($section);
}
