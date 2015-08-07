<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Connector\Sabre\RequestTest;

use OC\Connector\Sabre\Server;
use OC\Files\Mount\MountPoint;
use OC\Files\Storage\Temporary;
use OC\Files\View;
use OCP\IUser;
use Sabre\HTTP\Request;
use Test\TestCase;

abstract class RequestTest extends TestCase {
	/**
	 * @var \OC_User_Dummy
	 */
	protected $userBackend;

	/**
	 * @var \OCP\Files\Config\IMountProvider[]
	 */
	protected $mountProviders;

	protected function getStream($string) {
		$stream = fopen('php://temp', 'r+');
		fwrite($stream, $string);
		fseek($stream, 0);
		return $stream;
	}

	/**
	 * @param $userId
	 * @param $storages
	 * @return \OCP\Files\Config\IMountProvider
	 */
	protected function getMountProvider($userId, $storages) {
		$mounts = [];
		foreach ($storages as $mountPoint => $storage) {
			$mounts[] = new MountPoint($storage, $mountPoint);
		}
		$provider = $this->getMock('\OCP\Files\Config\IMountProvider');
		$provider->expects($this->any())
			->method('getMountsForUser')
			->will($this->returnCallback(function (IUser $user) use ($userId, $mounts) {
				if ($user->getUID() === $userId) {
					return $mounts;
				} else {
					return [];
				}
			}));
		return $provider;
	}

	protected function setUp() {
		$this->userBackend = new \OC_User_Dummy();
		\OC::$server->getUserManager()->registerBackend($this->userBackend);
	}

	protected function tearDown() {
		\OC::$server->getUserManager()->removeBackend($this->userBackend);
	}

	protected function setupUser($name, $password) {
		$this->userBackend->createUser($name, $password);
		\OC::$server->getMountProviderCollection()->registerProvider($this->getMountProvider($name, [
			'/' . $name => new Temporary()
		]));
		$this->loginAsUser($name);
		return new View('/' . $name . '/files');
	}

	/**
	 * @param \OC\Files\View $view the view to run the webdav server against
	 * @param string $user
	 * @param string $password
	 * @param string $method
	 * @param string $url
	 * @param resource|string|null $body
	 * @param array|null $headers
	 * @return \Sabre\HTTP\Response
	 */
	protected function request($view, $user, $password, $method, $url, $body = null, $headers = null) {
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
		$objectTree = new \OC\Connector\Sabre\ObjectTree();
		$server = new \OC\Connector\Sabre\Server($objectTree);

		$server->setBaseUri('/');

		// Load plugins
		$server->addPlugin(new \Sabre\DAV\Auth\Plugin($authBackend, 'oc-test'));
		$server->addPlugin(new \OC\Connector\Sabre\DummyGetResponsePlugin());
		$server->addPlugin(new \OC\Connector\Sabre\FilesPlugin($objectTree));
		$server->addPlugin($exceptionPlugin);

		// wait with registering these until auth is handled and the filesystem is setup
		$server->on('beforeMethod', function () use ($server, $objectTree, $view) {
			$rootInfo = $view->getFileInfo('');

			// Create ownCloud Dir
			$mountManager = \OC\Files\Filesystem::getMountManager();
			$rootDir = new \OC\Connector\Sabre\Directory($view, $rootInfo);
			$objectTree->init($rootDir, $view, $mountManager);

			$server->addPlugin(new \OC\Connector\Sabre\QuotaPlugin($view));

			// custom properties plugin must be the last one
			$server->addPlugin(
				new \Sabre\DAV\PropertyStorage\Plugin(
					new \OC\Connector\Sabre\CustomPropertiesBackend(
						$objectTree,
						\OC::$server->getDatabaseConnection(),
						\OC::$server->getUserSession()->getUser()
					)
				)
			);
			$server->addPlugin(new \OC\Connector\Sabre\CopyEtagHeaderPlugin());
		}, 30); // priority 30: after auth (10) and acl(20), before lock(50) and handling the request
		return $server;
	}
}
