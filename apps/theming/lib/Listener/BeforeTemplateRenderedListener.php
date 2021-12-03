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
use OCA\Theming\Service\JSDataService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\IInitialStateService;
use OCP\IServerContainer;
use OCP\IURLGenerator;

class BeforeTemplateRenderedListener implements IEventListener {

	/** @var IInitialStateService */
	private $initialStateService;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IConfig */
	private $config;
	/** @var IServerContainer */
	private $serverContainer;

	public function __construct(
		IInitialStateService $initialStateService,
		IURLGenerator $urlGenerator,
		IConfig $config,
		IServerContainer $serverContainer
	) {
		$this->initialStateService = $initialStateService;
		$this->urlGenerator = $urlGenerator;
		$this->config = $config;
		$this->serverContainer = $serverContainer;
	}

	public function handle(Event $event): void {
		$serverContainer = $this->serverContainer;
		$this->initialStateService->provideLazyInitialState(Application::APP_ID, 'data', function () use ($serverContainer) {
			return $serverContainer->query(JSDataService::class);
		});

		$linkToCSS = $this->urlGenerator->linkToRoute(
			'theming.Theming.getStylesheet',
			[
				'v' => $this->config->getAppValue('theming', 'cachebuster', '0'),
			]
		);
		\OCP\Util::addHeader(
			'link',
			[
				'rel' => 'stylesheet',
				'href' => $linkToCSS,
			]
		);

		// Making sure to inject just after core
		\OCP\Util::addScript('theming', 'theming', 'core');
	}
}
