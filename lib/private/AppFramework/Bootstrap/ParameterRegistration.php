<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Bootstrap;

/**
 * @psalm-immutable
 */
final class ParameterRegistration extends ARegistration {
	/**
	 * @param mixed $value
	 */
	public function __construct(
		string $appId,
		private string $name,
		private $value,
	) {
		parent::__construct($appId);
	}

	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}
}
