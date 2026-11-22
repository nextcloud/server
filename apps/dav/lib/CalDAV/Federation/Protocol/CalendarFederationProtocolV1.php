<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation\Protocol;

class CalendarFederationProtocolV1 implements ICalendarFederationProtocol {
	public const VERSION = 'v1';

	public const PROP_URL = 'url';
	public const PROP_DISPLAY_NAME = 'displayName';
	public const PROP_COLOR = 'color';
	public const PROP_ACCESS = 'access';
	public const PROP_COMPONENTS = 'components';

	private string $url = '';
	private string $displayName = '';
	private ?string $color = null;
	private int $access = 0;
	private string $components = '';

	/**
	 * @throws CalendarProtocolParseException If parsing the raw protocol array fails.
	 */
	public static function parse(array $rawProtocol): self {
		if ($rawProtocol[self::PROP_VERSION] !== self::VERSION) {
			throw new CalendarProtocolParseException('Unknown protocol version');
		}

		$url = $rawProtocol[self::PROP_URL] ?? null;
		if (!is_string($url)) {
			throw new CalendarProtocolParseException('URL is missing or not a string');
		}

		$displayName = $rawProtocol[self::PROP_DISPLAY_NAME] ?? null;
		if (!is_string($displayName)) {
			throw new CalendarProtocolParseException('Display name is missing or not a string');
		}

		$color = $rawProtocol[self::PROP_COLOR] ?? null;
		if (!is_string($color) && $color !== null) {
			throw new CalendarProtocolParseException('Color is set but not a string');
		}

		$access = $rawProtocol[self::PROP_ACCESS] ?? null;
		if (!is_int($access)) {
			throw new CalendarProtocolParseException('Access is missing or not an integer');
		}

		$components = $rawProtocol[self::PROP_COMPONENTS] ?? null;
		if (!is_string($components)) {
			throw new CalendarProtocolParseException('Supported calendar components are missing or not a string');
		}

		$protocol = new self();
		$protocol->setUrl($url);
		$protocol->setDisplayName($displayName);
		$protocol->setColor($color);
		$protocol->setAccess($access);
		$protocol->setComponents($components);
		return $protocol;
	}

	#[\Override]
	public function toProtocol(): array {
		return [
			self::PROP_VERSION => $this->getVersion(),
			self::PROP_URL => $this->getUrl(),
			self::PROP_DISPLAY_NAME => $this->getDisplayName(),
			self::PROP_COLOR => $this->getColor(),
			self::PROP_ACCESS => $this->getAccess(),
			self::PROP_COMPONENTS => $this->getComponents(),
		];
	}

	#[\Override]
	public function getVersion(): string {
		return self::VERSION;
	}

	public function getUrl(): string {
		return $this->url;
	}

	public function setUrl(string $url): void {
		$this->url = $url;
	}

	public function getDisplayName(): string {
		return $this->displayName;
	}

	public function setDisplayName(string $displayName): void {
		$this->displayName = $displayName;
	}

	public function getColor(): ?string {
		return $this->color;
	}

	public function setColor(?string $color): void {
		$this->color = $color;
	}

	public function getAccess(): int {
		return $this->access;
	}

	public function setAccess(int $access): void {
		$this->access = $access;
	}

	public function getComponents(): string {
		return $this->components;
	}

	public function setComponents(string $components): void {
		$this->components = $components;
	}
}
