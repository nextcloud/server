<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Sharing;

use OCP\IDBConnection;

final class InstanceMapper extends AbstractStringMapper {

	public function __construct(
		IDBConnection $connection,
	) {
		parent::__construct($connection, 'sharing_instance_map', 'instance_name', 'instance_id');
	}

	public function getInstanceId(?string $value): ?int {
		if ($value === '') {
			throw new \ValueError('Instance cannot be an empty string');
		}

		return ($value === null) ? null : parent::getId($value);
	}

	/**
	 * @return non-empty-string|null
	 */
	public function getInstance(mixed $id): ?string {
		if ($id === null) {
			return null;
		}

		/** @var non-empty-string $instance */
		$instance = parent::getValue((int)$id);
		return $instance;
	}

}
