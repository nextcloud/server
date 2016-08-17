<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Settings\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\Settings\IManager as ISettingsManager;
use OCP\Template;

/**
 * @package OC\Settings\Controller
 */
class AdminSettingsController extends Controller {
	/** @var INavigationManager */
	private $navigationManager;
	/** @var ISettingsManager */
	private $settingsManager;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param INavigationManager $navigationManager
	 * @param ISettingsManager $settingsManager
	 */
	public function __construct(
		$appName,
		IRequest $request,
		INavigationManager $navigationManager,
		ISettingsManager $settingsManager
	) {
		parent::__construct($appName, $request);
		$this->navigationManager = $navigationManager;
		$this->settingsManager = $settingsManager;
	}

	/**
	 * @param string $section
	 * @return TemplateResponse
	 *
	 * @NoCSRFRequired
	 */
	public function index($section) {
		$this->navigationManager->setActiveEntry('admin');

		$templateParams = [];
		$templateParams = array_merge($templateParams, $this->getNavigationParameters($section));
		$templateParams = array_merge($templateParams, $this->getSettings($section));

		return new TemplateResponse('settings', 'admin/frame', $templateParams);
	}

	/**
	 * @param string $section
	 * @return array
	 */
	private function getSettings($section) {
		$html = '';
		$settings = $this->settingsManager->getAdminSettings($section);
		foreach ($settings as $prioritizedSettings) {
			foreach ($prioritizedSettings as $setting) {
				/** @var \OCP\Settings\ISettings $setting */
				$form = $setting->getForm();
				$html .= $form->renderAs('')->render();
			}
		}
		if($section === 'additional') {
			$html .= $this->getLegacyForms();
		}
		return ['content' => $html];
	}

	/**
	 * @return bool|string
	 */
	private function getLegacyForms() {
		$forms = \OC_App::getForms('admin');

		$forms = array_map(function ($form) {
			if (preg_match('%(<h2(?P<class>[^>]*)>.*?</h2>)%i', $form, $regs)) {
				$sectionName = str_replace('<h2' . $regs['class'] . '>', '', $regs[0]);
				$sectionName = str_replace('</h2>', '', $sectionName);
				$anchor = strtolower($sectionName);
				$anchor = str_replace(' ', '-', $anchor);

				return array(
					'anchor' => $anchor,
					'section-name' => $sectionName,
					'form' => $form
				);
			}
			return array(
				'form' => $form
			);
		}, $forms);

		$out = new Template('settings', 'admin/additional');
		$out->assign('forms', $forms);

		return $out->fetchPage();
	}

	/**
	 * @param string $currentSection
	 * @return array
	 */
	private function getNavigationParameters($currentSection) {
		$sections = $this->settingsManager->getAdminSections();
		$templateParameters = [];
		/** @var \OC\Settings\Section[] $prioritizedSections */
		foreach($sections as $prioritizedSections) {
			foreach ($prioritizedSections as $section) {
				$templateParameters[] = [
					'anchor'       => $section->getID(),
					'section-name' => $section->getName(),
					'active'       => $section->getID() === $currentSection,
				];
			}
		}

		return [
			'forms' => $templateParameters
		];
	}
}
