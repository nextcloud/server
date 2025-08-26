<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre\RequestTest;

use OC\Files\View;
use OCA\DAV\Connector\Sabre\Server;
use OCA\DAV\Connector\Sabre\ServerFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Mount\IMountManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IPreview;
use OCP\IRequest;
use OCP\IRequestId;
use OCP\ITagManager;
use OCP\ITempManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use Psr\Log\LoggerInterface;
use Sabre\HTTP\Request;
use Test\TestCase;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

abstract class RequestTestCase extends TestCase {
	use UserTrait;
	use MountProviderTrait;
	protected ServerFactory $serverFactory;

	protected function getStream($string) {
		$stream = fopen('php://temp', 'r+');
		fwrite($stream, $string);
		fseek($stream, 0);
		return $stream;
	}

	protected function setUp(): void {
		parent::setUp();

		$this->serverFactory = new ServerFactory(
			\OCP\Server::get(IConfig::class),
			\OCP\Server::get(LoggerInterface::class),
			\OCP\Server::get(IDBConnection::class),
			\OCP\Server::get(IUserSession::class),
			\OCP\Server::get(IMountManager::class),
			\OCP\Server::get(ITagManager::class),
			$this->createMock(IRequest::class),
			\OCP\Server::get(IPreview::class),
			\OCP\Server::get(IEventDispatcher::class),
			\OCP\Server::get(IFactory::class)->get('dav'),
		);
	}

	protected function setupUser($name, $password): View {
		$this->createUser($name, $password);
		$tmpFolder = \OCP\Server::get(ITempManager::class)->getTemporaryFolder();
		$this->registerMount($name, '\OC\Files\Storage\Local', '/' . $name, ['datadir' => $tmpFolder]);
		self::loginAsUser($name);
		return new View('/' . $name . '/files');
	}

	/**
	 * @param View $view the view to run the webdav server against
	 * @param string $user
	 * @param string $password
	 * @param string $method
	 * @param string $url
	 * @param resource|string|null $body
	 * @param array|null $headers
	 * @return \Sabre\HTTP\Response
	 * @throws \Exception
	 */
	protected function request($view, $user, $password, $method, $url, $body = null, $headers = []) {
		if (is_string($body)) {
			$body = $this->getStream($body);
		}
		$this->logout();
		$exceptionPlugin = new ExceptionPlugin('webdav', \OCP\Server::get(LoggerInterface::class));
		$server = $this->getSabreServer($view, $user, $password, $exceptionPlugin);
		$request = new Request($method, $url, $headers, $body);

		// since sabre catches all exceptions we need to save them and throw them from outside the sabre server

		$serverParams = [];
		if (is_array($headers)) {
			foreach ($headers as $header => $value) {
				$serverParams['HTTP_' . strtoupper(str_replace('-', '_', $header))] = $value;
			}
		}
		$ncRequest = new \OC\AppFramework\Http\Request([
			'server' => $serverParams
		], $this->createMock(IRequestId::class), $this->createMock(IConfig::class), null);

		$this->overwriteService(IRequest::class, $ncRequest);

		$result = $this->makeRequest($server, $request);

		$this->restoreService(IRequest::class);

		foreach ($exceptionPlugin->getExceptions() as $exception) {
			throw $exception;
		}
		return $result;
	}

	/**
	 * @param Server $server
	 * @param Request $request
	 * @return \Sabre\HTTP\Response
	 */
	protected function makeRequest(Server $server, Request $request) {
		$sapi = new Sapi($request);
		$server->sapi = $sapi;
		$server->httpRequest = $request;
		$server->exec();
		return $sapi->getResponse();
	}

	/**
	 * @param View $view
	 * @param string $user
	 * @param string $password
	 * @param ExceptionPlugin $exceptionPlugin
	 * @return Server
	 */
	protected function getSabreServer(View $view, $user, $password, ExceptionPlugin $exceptionPlugin) {
		$authBackend = new Auth($user, $password);
		$authPlugin = new \Sabre\DAV\Auth\Plugin($authBackend);

		$server = $this->serverFactory->createServer(false, '/', 'dummy', $authPlugin, function () use ($view) {
			return $view;
		});
		$server->addPlugin($exceptionPlugin);

		return $server;
	}
}
