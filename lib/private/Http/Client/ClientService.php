<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OC\Http\Client;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use OCP\Diagnostics\IEventLogger;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\ICertificateManager;
use OCP\IConfig;
use OCP\Security\IRemoteHostValidator;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ClientService
 *
 * @package OC\Http
 */
class ClientService implements IClientService {
	/** @var IConfig */
	private $config;
	/** @var ICertificateManager */
	private $certificateManager;
	/** @var DnsPinMiddleware */
	private $dnsPinMiddleware;
	private IRemoteHostValidator $remoteHostValidator;
	private IEventLogger $eventLogger;

	public function __construct(
		IConfig $config,
		ICertificateManager $certificateManager,
		DnsPinMiddleware $dnsPinMiddleware,
		IRemoteHostValidator $remoteHostValidator,
		IEventLogger $eventLogger,
		protected LoggerInterface $logger,
	) {
		$this->config = $config;
		$this->certificateManager = $certificateManager;
		$this->dnsPinMiddleware = $dnsPinMiddleware;
		$this->remoteHostValidator = $remoteHostValidator;
		$this->eventLogger = $eventLogger;
	}

	/**
	 * @return Client
	 */
	public function newClient(): IClient {
		$handler = new CurlHandler();
		$stack = HandlerStack::create($handler);
		if ($this->config->getSystemValueBool('dns_pinning', true)) {
			$stack->push($this->dnsPinMiddleware->addDnsPinning());
		}
		$stack->push(Middleware::tap(function (RequestInterface $request) {
			$this->eventLogger->start('http:request', $request->getMethod() . " request to " . $request->getRequestTarget());
		}, function () {
			$this->eventLogger->end('http:request');
		}), 'event logger');

		$client = new GuzzleClient(['handler' => $stack]);

		return new Client(
			$this->config,
			$this->certificateManager,
			$client,
			$this->remoteHostValidator,
			$this->logger,
		);
	}
}
