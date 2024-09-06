<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Http\Client;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use OCP\Http\Client\IPromise;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * A wrapper around Guzzle's PromiseInterface
 *
 * @see \GuzzleHttp\Promise\PromiseInterface
 * @since 28.0.0
 */
class GuzzlePromiseAdapter implements IPromise {
	public function __construct(
		protected PromiseInterface $promise,
		protected LoggerInterface $logger,
	) {
	}

	public function then(
		?callable $onFulfilled = null,
		?callable $onRejected = null,
	): IPromise {
		if ($onFulfilled !== null) {
			$wrappedOnFulfilled = static function (ResponseInterface $response) use ($onFulfilled) {
				$onFulfilled(new Response($response));
			};
		} else {
			$wrappedOnFulfilled = null;
		}

		if ($onRejected !== null) {
			$wrappedOnRejected = static function (RequestException $e) use ($onRejected) {
				$onRejected($e);
			};
		} else {
			$wrappedOnRejected = null;
		}

		$this->promise->then($wrappedOnFulfilled, $wrappedOnRejected);
		return $this;
	}

	public function getState(): string {
		$state = $this->promise->getState();
		if ($state === PromiseInterface::FULFILLED) {
			return self::STATE_FULFILLED;
		}
		if ($state === PromiseInterface::REJECTED) {
			return self::STATE_REJECTED;
		}
		if ($state === PromiseInterface::PENDING) {
			return self::STATE_PENDING;
		}

		$this->logger->error('Unexpected promise state "{state}" returned by Guzzle', [
			'state' => $state,
		]);
		return self::STATE_PENDING;
	}

	public function cancel(): void {
		$this->promise->cancel();
	}

	public function wait(bool $unwrap = true): mixed {
		return $this->promise->wait($unwrap);
	}
}
