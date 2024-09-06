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

	public function setLabel(string $label): IAction {
		if ($label === '' || isset($label[32])) {
			throw new InvalidValueException('label');
		}
		$this->label = $label;
		return $this;
	}

	public function getLabel(): string {
		return $this->label;
	}

	public function setParsedLabel(string $label): IAction {
		if ($label === '') {
			throw new InvalidValueException('parsedLabel');
		}
		$this->labelParsed = $label;
		return $this;
	}

	public function getParsedLabel(): string {
		return $this->labelParsed;
	}

	public function setPrimary(bool $primary): IAction {
		$this->primary = $primary;
		return $this;
	}

	public function isPrimary(): bool {
		return $this->primary;
	}

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

	public function getLink(): string {
		return $this->link;
	}

	public function getRequestType(): string {
		return $this->requestType;
	}

	public function isValid(): bool {
		return $this->label !== '' && $this->link !== '';
	}

	public function isValidParsed(): bool {
		return $this->labelParsed !== '' && $this->link !== '';
	}
}
