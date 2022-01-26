<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre\RequestTest;

use Exception;
use OC;
use OC\Files\Storage\Local;
use OC\Files\View;
use OCA\DAV\Connector\Sabre\Server;
use OCA\DAV\Connector\Sabre\ServerFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Mount\IMountManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IPreview;
use OCP\IRequest;
use OCP\ITagManager;
use OCP\ITempManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Auth\Plugin;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;
use Test\TestCase;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;
use Throwable;

abstract class RequestTestCase extends TestCase {
	use UserTrait;
	use MountProviderTrait;

	/**
	 * @var ServerFactory
	 */
	protected $serverFactory;

	protected function getStream($string) {
		$stream = fopen('php://temp', 'r+');
		fwrite($stream, $string);
		fseek($stream, 0);
		return $stream;
	}

	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	protected function setUp(): void {
		parent::setUp();

		unset($_SERVER['HTTP_OC_CHUNKED']);

		$this->serverFactory = new ServerFactory(
			OC::$server->get(IConfig::class),
			OC::$server->get(LoggerInterface::class),
			OC::$server->get(IDBConnection::class),
			OC::$server->get(IUserSession::class),
			OC::$server->get(IMountManager::class),
			OC::$server->get(ITagManager::class),
			$this->createMock(IRequest::class),
			OC::$server->get(IPreview::class),
			OC::$server->get(IEventDispatcher::class),
			OC::$server->get(IFactory::class)->get('dav')
		);
	}

	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws Exception
	 */
	protected function setupUser($name, $password): View {
		$this->createUser($name, $password);
		$tmpFolder = OC::$server->get(ITempManager::class)->getTemporaryFolder();
		$this->registerMount($name, Local::class, '/' . $name, ['datadir' => $tmpFolder]);
		$this->loginAsUser($name);
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
	 * @return Response
	 * @throws Exception|Throwable
	 */
	protected function request(View $view, string $user, string $password, string $method, string $url, string $body = null, ?array $headers = []): Response {
		if (is_string($body)) {
			$body = $this->getStream($body);
		}
		$this->logout();
		$exceptionPlugin = new ExceptionPlugin('webdav', null);
		$server = $this->getSabreServer($view, $user, $password, $exceptionPlugin);
		$request = new Request($method, $url, $headers, $body);

		// since sabre catches all exceptions we need to save them and throw them from outside the sabre server

		$originalServer = $_SERVER;

		if (is_array($headers)) {
			foreach ($headers as $header => $value) {
				$_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $header))] = $value;
			}
		}

		$result = $this->makeRequest($server, $request);

		foreach ($exceptionPlugin->getExceptions() as $exception) {
			throw $exception;
		}
		$_SERVER = $originalServer;
		return $result;
	}

	protected function makeRequest(Server $server, Request $request): Response {
		$sapi = new Sapi($request);
		$server->sapi = $sapi;
		$server->httpRequest = $request;
		$server->start();
		return $sapi->getResponse();
	}

	/**
	 * @throws \Sabre\DAV\Exception
	 */
	protected function getSabreServer(View $view, string $user, string $password, ExceptionPlugin $exceptionPlugin): Server {
		$authBackend = new Auth($user, $password);
		$authPlugin = new Plugin($authBackend);

		$server = $this->serverFactory->createServer('/', 'dummy', $authPlugin, function () use ($view) {
			return $view;
		});
		$server->addPlugin($exceptionPlugin);

		return $server;
	}
}
