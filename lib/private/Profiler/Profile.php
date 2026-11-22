<?php

declare(strict_types = 1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Profiler;

use OCP\DataCollector\IDataCollector;
use OCP\Profiler\IProfile;

class Profile implements \JsonSerializable, IProfile {
	private ?int $time = null;

	private ?string $url = null;

	private ?string $method = null;

	private ?int $statusCode = null;

	/** @var array<string, IDataCollector> */
	private array $collectors = [];

	private ?IProfile $parent = null;

	/** @var IProfile[] */
	private array $children = [];

	public function __construct(
		private string $token,
	) {
	}

	#[\Override]
	public function getToken(): string {
		return $this->token;
	}

	#[\Override]
	public function setToken(string $token): void {
		$this->token = $token;
	}

	#[\Override]
	public function getTime(): ?int {
		return $this->time;
	}

	#[\Override]
	public function setTime(int $time): void {
		$this->time = $time;
	}

	#[\Override]
	public function getUrl(): ?string {
		return $this->url;
	}

	#[\Override]
	public function setUrl(string $url): void {
		$this->url = $url;
	}

	#[\Override]
	public function getMethod(): ?string {
		return $this->method;
	}

	#[\Override]
	public function setMethod(string $method): void {
		$this->method = $method;
	}

	#[\Override]
	public function getStatusCode(): ?int {
		return $this->statusCode;
	}

	#[\Override]
	public function setStatusCode(int $statusCode): void {
		$this->statusCode = $statusCode;
	}

	#[\Override]
	public function addCollector(IDataCollector $collector) {
		$this->collectors[$collector->getName()] = $collector;
	}

	#[\Override]
	public function getParent(): ?IProfile {
		return $this->parent;
	}

	#[\Override]
	public function setParent(?IProfile $parent): void {
		$this->parent = $parent;
	}

	#[\Override]
	public function getParentToken(): ?string {
		return $this->parent ? $this->parent->getToken() : null;
	}

	/** @return IProfile[] */
	#[\Override]
	public function getChildren(): array {
		return $this->children;
	}

	/**
	 * @param IProfile[] $children
	 */
	#[\Override]
	public function setChildren(array $children): void {
		$this->children = [];
		foreach ($children as $child) {
			$this->addChild($child);
		}
	}

	#[\Override]
	public function addChild(IProfile $profile): void {
		$this->children[] = $profile;
		$profile->setParent($this);
	}

	/**
	 * @return IDataCollector[]
	 */
	#[\Override]
	public function getCollectors(): array {
		return $this->collectors;
	}

	/**
	 * @param IDataCollector[] $collectors
	 */
	#[\Override]
	public function setCollectors(array $collectors): void {
		$this->collectors = $collectors;
	}

	public function __sleep(): array {
		return ['token', 'parent', 'children', 'collectors', 'method', 'url', 'time', 'statusCode'];
	}

	#[\Override]
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		// Everything but parent
		return [
			'token' => $this->token,
			'method' => $this->method,
			'children' => $this->children,
			'url' => $this->url,
			'statusCode' => $this->statusCode,
			'time' => $this->time,
			'collectors' => $this->collectors,
		];
	}

	#[\Override]
	public function getCollector(string $collectorName): ?IDataCollector {
		if (!array_key_exists($collectorName, $this->collectors)) {
			return null;
		}
		return $this->collectors[$collectorName];
	}
}
