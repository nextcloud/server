<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\UserStatus\Connector;

use OCA\UserStatus\Service\StatusService;
use OCP\UserStatus\IProvider;

class UserStatusProvider implements IProvider {

	/** @var StatusService */
	private $service;

	/**
	 * UserStatusProvider constructor.
	 *
	 * @param StatusService $service
	 */
	public function __construct(StatusService $service) {
		$this->service = $service;
	}

	/**
	 * @inheritDoc
	 */
	public function getUserStatuses(array $userIds): array {
		$statuses = $this->service->findByUserIds($userIds);

		$userStatuses = [];
		foreach ($statuses as $status) {
			$userStatuses[$status->getUserId()] = new UserStatus($status);
		}

		return $userStatuses;
	}
}
