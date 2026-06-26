<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Notification;

use OCP\Notification\IAction;
use OCP\Notification\InvalidValueException;

class Action implements IAction {
	protected string $label = '';
	protected string $labelParsed = '';
	protected string $link = '';
	protected string $requestType = '';
	protected bool $primary = false;

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function setLabel(string $label): IAction {
		if ($label === '' || isset($label[32])) {
			throw new InvalidValueException('label');
		}
		$this->label = $label;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function getLabel(): string {
		return $this->label;
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function setParsedLabel(string $label): IAction {
		if ($label === '') {
			throw new InvalidValueException('parsedLabel');
		}
		$this->labelParsed = $label;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function getParsedLabel(): string {
		return $this->labelParsed;
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function setPrimary(bool $primary): IAction {
		$this->primary = $primary;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function isPrimary(): bool {
		return $this->primary;
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function setLink(string $link, string $requestType): IAction {
		if ($link === '' || isset($link[256])) {
			throw new InvalidValueException('link');
		}

		// Only allow absolute URLs for support of desktop and mobile clients
		if (!str_starts_with($link, 'http://') && !str_starts_with($link, 'https://')) {
			throw new InvalidValueException('link');
		}

		if (!in_array($requestType, [
			self::TYPE_GET,
			self::TYPE_POST,
			self::TYPE_PUT,
			self::TYPE_DELETE,
			self::TYPE_WEB,
		], true)) {
			throw new InvalidValueException('requestType');
		}
		$this->link = $link;
		$this->requestType = $requestType;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function getLink(): string {
		return $this->link;
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function getRequestType(): string {
		return $this->requestType;
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function isValid(): bool {
		return $this->label !== '' && $this->link !== '';
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function isValidParsed(): bool {
		return $this->labelParsed !== '' && $this->link !== '';
	}
}
