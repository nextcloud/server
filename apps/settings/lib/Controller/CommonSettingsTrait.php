<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Controller;

use InvalidArgumentException;
use OC\AppFramework\Middleware\Security\Exceptions\NotAdminException;
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
 * @psalm-import-type DeclarativeSettingsFormSchemaWithValues from IDeclarativeSettingsForm
 * @psalm-import-type DeclarativeSettingsFormSchemaWithoutValues from IDeclarativeSettingsForm
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

				if (empty($settings) && empty($declarativeFormIDs)) {
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
	 * @param list<ISettings> $settings
	 * @param list<DeclarativeSettingsFormSchemaWithValues> $declarativeSettings
	 * @return array{content: string}
	 */
	private function formatSettings(array $settings, array $declarativeSettings): array {
		$settings = array_merge($settings, $declarativeSettings);

		usort($settings, function ($first, $second) {
			$priorityOne = $first instanceof ISettings ? $first->getPriority() : $first['priority'];
			$priorityTwo = $second instanceof ISettings ? $second->getPriority() : $second['priority'];
			return $priorityOne - $priorityTwo;
		});

		$html = '';
		foreach ($settings as $setting) {
			if ($setting instanceof ISettings) {
				$form = $setting->getForm();
				$html .= $form->renderAs('')->render();
			} else {
				$html .= '<div id="' . $setting['app'] . '_' . $setting['id'] . '"></div>';
			}
		}
		return ['content' => $html];
	}

	/**
	 * @psalm-param 'admin'|'personal' $type
	 */
	private function getIndexResponse(string $type, string $section): TemplateResponse {
		$user = $this->userSession->getUser();
		assert($user !== null, 'No user logged in for settings');

		$this->declarativeSettingsManager->loadSchemas();
		$declarativeSettings = $this->declarativeSettingsManager->getFormsWithValues($user, $type, $section);

		foreach ($declarativeSettings as &$form) {
			foreach ($form['fields'] as &$field) {
				if (isset($field['sensitive']) && $field['sensitive'] === true && !empty($field['value'])) {
					$field['value'] = 'dummySecret';
				}
			}
		}

		if ($type === 'personal') {
			$settings = array_values($this->settingsManager->getPersonalSettings($section));
			if ($section === 'theming') {
				$this->navigationManager->setActiveEntry('accessibility_settings');
			} else {
				$this->navigationManager->setActiveEntry('settings');
			}
		} elseif ($type === 'admin') {
			$settings = array_values($this->settingsManager->getAllowedAdminSettings($section, $user));
			if (empty($settings) && empty($declarativeSettings)) {
				throw new NotAdminException('Logged in user does not have permission to access these settings.');
			}
			$this->navigationManager->setActiveEntry('admin_settings');
		} else {
			throw new InvalidArgumentException('$type must be either "admin" or "personal"');
		}

		if (!empty($declarativeSettings)) {
			Util::addScript(Application::APP_ID, 'declarative-settings-forms');
			$this->initialState->provideInitialState('declarative-settings-forms', $declarativeSettings);
		}

		$settings = array_merge(...$settings);
		$templateParams = $this->formatSettings($settings, $declarativeSettings);
		$templateParams = array_merge($templateParams, $this->getNavigationParameters($type, $section));

		$activeSection = $this->settingsManager->getSection($type, $section);
		if ($activeSection) {
			$templateParams['pageTitle'] = $activeSection->getName();
			$templateParams['activeSectionId'] = $activeSection->getID();
			$templateParams['activeSectionType'] = $type;
		}

		return new TemplateResponse('settings', 'settings/frame', $templateParams);
	}
}
