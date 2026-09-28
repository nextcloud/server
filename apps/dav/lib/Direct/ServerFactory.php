<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Direct;

use OCA\DAV\Connector\Sabre\MaintenancePlugin;
use OCA\DAV\Db\DirectMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\L10N\IFactory;
use OCP\Security\Bruteforce\IThrottler;

class ServerFactory {
	/** @var IL10N */
	private $l10n;

	public function __construct(
		private IConfig $config,
		IFactory $l10nFactory,
		private IEventDispatcher $eventDispatcher,
	) {
		$this->l10n = $l10nFactory->get('dav');
	}

	public function createServer(string $baseURI,
		string $requestURI,
		IRootFolder $rootFolder,
		DirectMapper $mapper,
		ITimeFactory $timeFactory,
		IThrottler $throttler,
		IRequest $request): Server {
		$home = new DirectHome($rootFolder, $mapper, $timeFactory, $throttler, $request, $this->eventDispatcher);
		$server = new Server($home);

		$server->httpRequest->setUrl($requestURI);
		$server->setBaseUri($baseURI);

		$server->addPlugin(new MaintenancePlugin($this->config, $this->l10n));

		return $server;
	}
}
