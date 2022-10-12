<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022, Roeland Jago Douma <roeland@famdouma.nl>
 *
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
namespace OC\Http\Client;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class HSTSMiddleware {

    private HSTSStore $hstsStore;
    private LoggerInterface $logger;

	public function __construct(
        HSTSStore $hstsStore,
        LoggerInterface $logger
	) {
        $this->hstsStore = $hstsStore;
        $this->logger = $logger;
	}

    private function isIpaAddr(string $host): bool {
        return filter_var($host, FILTER_VALIDATE_IP) !== false;
    }

    private function handleHSTSRewrite(RequestInterface $request): RequestInterface {

        $uri = $request->getUri();

        if ($uri->getScheme() === 'http'
            && !$this->isIpaAddr($uri->getHost())
            && $this->hstsStore->hasHSTS($uri->getHost())) {
            
            $uri = $uri->withScheme('https');
        }

        return $request->withUri($uri);
    }

    private function handleHSTSResponse(ResponseInterface $response, RequestInterface $request): ResponseInterface {
        $uri = $request->getUri();

        $this->logger->error($uri->getScheme());

        if ($uri->getScheme() === 'https'
            && !$this->isIpaAddr($uri->getHost())
            && $response->hasHeader('Strict-Transport-Security')) {
            

                $this->logger->error("LETS GO");

            // Get the header and pass it to the store to parse and store this info
            $header = $response->getHeader('Strict-Transport-Security')[0];
            $this->hstsStore->setHSTS($uri->getHost(), $header);
        }

        return $response;
    }

	public function addHSTS() {
		return function (callable $handler) {
			return function (
				RequestInterface $request,
				array $options
			) use ($handler) {

                $request = $this->handleHSTSRewrite($request);

                $this->logger->warning("GONNA REQUEST");
                $this->logger->warning($request->getUri()->getScheme());
                $this->logger->warning($request->getUri()->getHost());


				return $handler($request, $options)
                    ->then(function (ResponseInterface $response) use ($request) {
                        $this->logger->error("GOT RESPONSE");
                        $this->handleHSTSResponse($response, $request);
                        return $response;
                    });
			};
		};
	}
}
