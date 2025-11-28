<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Listener;

use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\CircleProbe;

class GroupListenerBase {
	private function getGroupCircle(CircleRequest $circleRequest, string $groupId): Circle {
		$circle = new Circle();
		$circle->setName('group:' . $groupId)
			->setConfig(Circle::CFG_SYSTEM | Circle::CFG_NO_OWNER | Circle::CFG_HIDDEN)
			->setSource(Member::TYPE_GROUP);
		return $circleRequest->searchCircle($circle);
	}

	/**
	 * @return Circle[]
	 */
	protected function getCirclesForGroup(CircleRequest $circleRequest, string $groupId): array {
		$groupCircle = $this->getGroupCircle($circleRequest, $groupId);

		$filterMember = new Member();
		$filterMember->setSingleId($groupCircle->getSingleId());
		$probe = new CircleProbe();
		$probe->filterHiddenCircles()
			->filterBackendCircles()
			->setFilterMember($filterMember);
		return $circleRequest->getCircles(null, $probe);
	}
}
