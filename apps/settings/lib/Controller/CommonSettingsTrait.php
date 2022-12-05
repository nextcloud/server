<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\Settings\Controller;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Group\ISubAdmin;
use OCP\IGroupManager;
use OCP\INavigationManager;
use OCP\IUserSession;
use OCP\Settings\IIconSection;
use OCP\Settings\IManager as ISettingsManager;
use OCP\Settings\ISettings;

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

	/**
	 * @return array{forms: array{personal: array, admin: array}}
	 */
	private function getNavigationParameters(string $currentType, string $currentSection): array {
		$templateParameters = [
			'personal' => $this->formatPersonalSections($currentType, $currentSection),
			'admin' => []
		];

		$templateParameters['admin'] = $this->formatAdminSections(
			$currentType,
			$currentSection
		);

		return [
			'forms' => $templateParameters
		];
	}

	/**
	 * @param IIconSection[][] $sections
	 * @psam-param 'admin'|'personal' $type
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
				if (empty($settings) && !($section->getID() === 'additional' && count(\OC_App::getForms('admin')) > 0)) {
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

	protected function formatPersonalSections(string $currentType, string $currentSections): array {
		$sections = $this->settingsManager->getPersonalSections();
		return $this->formatSections($sections, $currentSections, 'personal', $currentType);
	}

	protected function formatAdminSections(string $currentType, string $currentSections): array {
		$sections = $this->settingsManager->getAdminSections();
		return $this->formatSections($sections, $currentSections, 'admin', $currentType);
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

		$templateParams = [];
		$templateParams = array_merge($templateParams, $this->getNavigationParameters($type, $section));
		$templateParams = array_merge($templateParams, $this->getSettings($section));
		$activeSection = $this->settingsManager->getSection($type, $section);
		if ($activeSection) {
			$templateParams['pageTitle'] = $activeSection->getName();
			$templateParams['activeSectionId'] = $activeSection->getID();
		}

		return new TemplateResponse('settings', 'settings/frame', $templateParams);
	}

	abstract protected function getSettings($section);
}
