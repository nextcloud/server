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
	/** @var string */
	private $name;

	/** @var mixed */
	private $value;

	public function __construct(string $appId,
		string $name,
		$value) {
		parent::__construct($appId);
		$this->name = $name;
		$this->value = $value;
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
