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
	public function getLabel(): string {
		return $this->label;
	}

	/**
	 * {@inheritDoc}
	 */
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
	public function getParsedLabel(): string {
		return $this->labelParsed;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setPrimary(bool $primary): IAction {
		$this->primary = $primary;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isPrimary(): bool {
		return $this->primary;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setLink(string $link, string $requestType): IAction {
		if ($link === '' || isset($link[256])) {
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
	public function getLink(): string {
		return $this->link;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRequestType(): string {
		return $this->requestType;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isValid(): bool {
		return $this->label !== '' && $this->link !== '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function isValidParsed(): bool {
		return $this->labelParsed !== '' && $this->link !== '';
	}
}
