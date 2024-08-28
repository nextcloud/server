<?php

declare(strict_types = 1);
/**
 * This file is part of the Symfony package.
 *
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2022 Fabien Potencier <fabien@symfony.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later AND MIT
 */
namespace OC\DB;

final class ObjectParameter {
	private $object;
	private $error;
	private $stringable;
	private $class;

	/**
	 * @param object $object
	 */
	public function __construct($object, ?\Throwable $error) {
		$this->object = $object;
		$this->error = $error;
		$this->stringable = \is_callable([$object, '__toString']);
		$this->class = \get_class($object);
	}

	/**
	 * @return object
	 */
	public function getObject() {
		return $this->object;
	}

	public function getError(): ?\Throwable {
		return $this->error;
	}

	public function isStringable(): bool {
		return $this->stringable;
	}

	public function getClass(): string {
		return $this->class;
	}
}
