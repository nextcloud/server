<?php

declare(strict_types = 1);
/**
 * @copyright 2022 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
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

namespace OC\Profiler;

use OCP\DataCollector\IDataCollector;
use OCP\Profiler\IProfile;

class Profile implements \JsonSerializable, IProfile {
	private string $token;

	private ?int $time = null;

	private ?string $url = null;

	private ?string $method = null;

	private ?int $statusCode = null;

	/** @var array<string, IDataCollector> */
	private array $collectors = [];

	private ?IProfile $parent = null;

	/** @var IProfile[] */
	private array $children = [];

	public function __construct(string $token) {
		$this->token = $token;
	}

	public function getToken(): string {
		return $this->token;
	}

	public function setToken(string $token): void {
		$this->token = $token;
	}

	public function getTime(): ?int {
		return $this->time;
	}

	public function setTime(int $time): void {
		$this->time = $time;
	}

	public function getUrl(): ?string {
		return $this->url;
	}

	public function setUrl(string $url): void {
		$this->url = $url;
	}

	public function getMethod(): ?string {
		return $this->method;
	}

	public function setMethod(string $method): void {
		$this->method = $method;
	}

	public function getStatusCode(): ?int {
		return $this->statusCode;
	}

	public function setStatusCode(int $statusCode): void {
		$this->statusCode = $statusCode;
	}

	public function addCollector(IDataCollector $collector) {
		$this->collectors[$collector->getName()] = $collector;
	}

	public function getParent(): ?IProfile {
		return $this->parent;
	}

	public function setParent(?IProfile $parent): void {
		$this->parent = $parent;
	}

	public function getParentToken(): ?string {
		return $this->parent ? $this->parent->getToken() : null;
	}

	/** @return IProfile[] */
	public function getChildren(): array {
		return $this->children;
	}

	/**
	 * @param IProfile[] $children
	 */
	public function setChildren(array $children): void {
		$this->children = [];
		foreach ($children as $child) {
			$this->addChild($child);
		}
	}

	public function addChild(IProfile $profile): void {
		$this->children[] = $profile;
		$profile->setParent($this);
	}

	/**
	 * @return IDataCollector[]
	 */
	public function getCollectors(): array {
		return $this->collectors;
	}

	/**
	 * @param IDataCollector[] $collectors
	 */
	public function setCollectors(array $collectors): void {
		$this->collectors = $collectors;
	}

	public function __sleep(): array {
		return ['token', 'parent', 'children', 'collectors', 'method', 'url', 'time', 'statusCode'];
	}

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

	public function getCollector(string $collectorName): ?IDataCollector {
		if (!array_key_exists($collectorName, $this->collectors)) {
			return null;
		}
		return $this->collectors[$collectorName];
	}
}
