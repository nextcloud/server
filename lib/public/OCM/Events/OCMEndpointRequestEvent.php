<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OCM\Events;

use OCP\AppFramework\Attribute\Listenable;
use OCP\AppFramework\Http\Response;
use OCP\EventDispatcher\Event;
use OCP\OCM\Enum\ParamType;

/**
 * Use this event to catch and manage incoming OCM request
 *
 * @since 33.0.0
 */
#[Listenable(since: '33.0.0')]
final class OCMEndpointRequestEvent extends Event {
	private ?Response $response = null;
	private string $capability = '';
	private string $path; // does not start with a slash '/'

	/**
	 * @since 33.0.0
	 */
	public function __construct(
		private readonly string $method,
		string $path,
		private readonly ?array $payload = null,
		private readonly ?string $remote = null,
	) {
		parent::__construct();

		$path = trim($path, '/');
		if (!str_contains($path, '/')) {
			$this->capability = $path;
			$path = '';
		} else {
			[$this->capability, $path] = explode('/', trim($path, '/'), 2);
		}
		$this->path = $path ?? '';
	}

	/**
	 * returns the first parameter of the sub-path (post-/ocm/) from the request
	 *
	 * @since 33.0.0
	 */
	public function getRequestedCapability(): string {
		return $this->capability;
	}

	/**
	 * returns the method used
	 *
	 * @since 33.0.0
	 */
	public function getUsedMethod(): string {
		return $this->method;
	}

	/**
	 * returns the sub-path (post-/ocm/) of the request
	 * will start with a slash ('/')
	 *
	 * @since 33.0.0
	 */
	public function getPath(): string {
		return '/' . $this->path;
	}

	/**
	 * Returns the list of parameters from the request, post-'capability'
	 *
	 * If no ParamType is specified as parameter of the method, the returned array
	 * will contain all entries (all string).
	 *
	 * If one or multiple ParamType are set:
	 *  - the returned array will contain as many entries as the number of ParamType,
	 *  - each value from the returned array will be typed based on set ParamType,
	 *  - if ParamType cannot be applied (i.e., only alphabetic chars while expecting
	 *    integer), value will be NULL,
	 *  - if missing elements to the request path, missing entries will be NULL,
	 *
	 * @since 33.0.0
	 */
	public function getArgs(ParamType ...$params): array {
		if ($this->path === '') {
			return [];
		}

		$args = explode('/', $this->path);
		if (empty($params)) {
			return $args;
		}

		$typedArgs = [];
		$i = 0;
		foreach ($params as $param) {
			if (($args[$i] ?? null) === null) {
				break;
			}
			$typedArgs[] = match($param) {
				ParamType::STRING => $args[$i],
				ParamType::INT => (is_numeric($args[$i])) ? (int)$args[$i] : null,
				ParamType::FLOAT => (is_numeric($args[$i])) ? (float)$args[$i] : null,
				ParamType::BOOL => in_array(strtolower($args[$i]), ['1', 'true', 'yes', 'on'], true),
			};
			$i++;
		}

		return $typedArgs;
	}

	/**
	 * return the number of parameters found in the subpath
	 *
	 * @since 33.0.0
	 */
	public function getArgsCount(): int {
		return count($this->getArgs());
	}

	/**
	 * returns the payload attached to the request
	 *
	 * @since 33.0.0
	 */
	public function getPayload(): array {
		return $this->payload ?? [];
	}

	/**
	 * @return bool TRUE if request is signed
	 * @since 33.0.0
	 */
	public function isSigned(): bool {
		return ($this->getRemote() !== null);
	}

	/**
	 * returns the origin of the request, if signed.
	 *
	 * @return string|null NULL if request is not authed
	 * @since 33.0.0
	 */
	public function getRemote(): ?string {
		return $this->remote;
	}

	/**
	 * set the Response to the Request to be sent to requester
	 *
	 * @since 33.0.0
	 */
	public function setResponse(Response $response): void {
		$this->response = $response;
	}

	/**
	 * @since 33.0.0
	 */
	public function getResponse(): ?Response {
		return $this->response;
	}
}
