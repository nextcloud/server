<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Sharing;

use OCP\IDBConnection;

final class ClassMapper extends AbstractStringMapper {

	public function __construct(
		IDBConnection $connection,
	) {
		parent::__construct($connection, 'sharing_classmap', 'class_name', 'class_id');
	}

	/**
	 * @param class-string $class
	 */
	public function getClassId(string $class): int {
		return $this->getId($class);
	}

	/**
	 * @return class-string
	 */
	public function getClassName(int $id): string {
		/** @var class-string $class */
		$class = $this->getValue($id);
		return $class;
	}
}
