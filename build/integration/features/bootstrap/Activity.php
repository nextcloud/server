<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\Assert;

trait Activity {
	use BasicStructure;

	/**
	 * @Then last activity should be
	 * @param TableNode $activity
	 */
	public function lastActivityIs(TableNode $activity): void {
		$this->sendRequestForJSON('GET', '/apps/activity/api/v2/activity');
		$this->theHTTPStatusCodeShouldBe('200');
		$data = json_decode($this->response->getBody()->getContents(), true);
		$activities = $data['ocs']['data'];
		/* Sort by id */
		uasort($activities, fn ($a, $b) => $a['activity_id'] <=> $b['activity_id']);
		$lastActivity = array_pop($activities);
		foreach ($activity->getRowsHash() as $key => $value) {
			Assert::assertEquals($value, $lastActivity[$key]);
		}
	}
}
