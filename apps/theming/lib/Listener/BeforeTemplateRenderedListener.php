<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Morris Jobke <hey@morrisjobke.de>
 *
 * @author Morris Jobke <hey@morrisjobke.de>
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
namespace OCA\Theming\Listener;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\Service\BackgroundService;
use OCA\Theming\Service\JSDataService;
use OCA\Theming\Service\ThemeInjectionService;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;

class BeforeTemplateRenderedListener implements IEventListener {

	private IInitialState $initialState;
	private ContainerInterface $container;
	private ThemeInjectionService $themeInjectionService;
	private IUserSession $userSession;
	private IConfig $config;

	public function __construct(
		IInitialState $initialState,
		ContainerInterface $container,
		ThemeInjectionService $themeInjectionService,
		IUserSession $userSession,
		IConfig $config
	) {
		$this->initialState = $initialState;
		$this->container = $container;
		$this->themeInjectionService = $themeInjectionService;
		$this->userSession = $userSession;
		$this->config = $config;
	}

	public function handle(Event $event): void {
		$this->initialState->provideLazyInitialState(
			'data',
			fn () => $this->container->get(JSDataService::class),
		);

		/** @var BeforeTemplateRenderedEvent $event */
		if ($event->getResponse()->getRenderAs() === TemplateResponse::RENDER_AS_USER) {
			$this->initialState->provideLazyInitialState('shortcutsDisabled', function () {
				if ($this->userSession->getUser()) {
					$uid = $this->userSession->getUser()->getUID();
					return $this->config->getUserValue($uid, Application::APP_ID, 'shortcuts_disabled', 'no') === 'yes';
				}
				return false;
			});
		}

		$this->themeInjectionService->injectHeaders();

		$user = $this->userSession->getUser();

		if (!empty($user)) {
			$userId = $user->getUID();

			/** User background */
			$this->initialState->provideInitialState(
				'backgroundImage',
				$this->config->getUserValue($userId, Application::APP_ID, 'background_image', BackgroundService::BACKGROUND_DEFAULT),
			);

			/** User color */
			$this->initialState->provideInitialState(
				'backgroundColor',
				$this->config->getUserValue($userId, Application::APP_ID, 'background_color', BackgroundService::DEFAULT_COLOR),
			);

			/**
			 * Admin background. `backgroundColor` if disabled,
			 * mime type if defined and empty by default
			 */
			$this->initialState->provideInitialState(
				'themingDefaultBackground',
				$this->config->getAppValue('theming', 'backgroundMime', ''),
			);
			$this->initialState->provideInitialState(
				'defaultShippedBackground',
				BackgroundService::DEFAULT_BACKGROUND_IMAGE,
			);

			/** List of all shipped backgrounds */
			$this->initialState->provideInitialState(
				'shippedBackgrounds',
				BackgroundService::SHIPPED_BACKGROUNDS,
			);
		}

		// Making sure to inject just after core
		\OCP\Util::addScript('theming', 'theming', 'core');
	}
}
