<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Listener;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\Service\JSDataService;
use OCA\Theming\Service\ThemeInjectionService;
use OCP\AppFramework\Http\Events\BeforeLoginTemplateRenderedEvent;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Util;
use Psr\Container\ContainerInterface;

/** @template-implements IEventListener<BeforeTemplateRenderedEvent|BeforeLoginTemplateRenderedEvent> */
class BeforeTemplateRenderedListener implements IEventListener {

	public function __construct(
		private IInitialState $initialState,
		private ContainerInterface $container,
		private ThemeInjectionService $themeInjectionService,
		private IUserSession $userSession,
		private IConfig $config,
	) {
	}

	public function handle(Event $event): void {
		$this->initialState->provideLazyInitialState(
			'data',
			fn () => $this->container->get(JSDataService::class),
		);

		/** @var BeforeTemplateRenderedEvent|BeforeLoginTemplateRenderedEvent $event */
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

		// Making sure to inject just after core
		Util::addScript('theming', 'theming', 'core');
	}
}
