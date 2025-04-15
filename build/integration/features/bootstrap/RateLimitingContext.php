<?php
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use Behat\Behat\Context\Context;

class RateLimitingContext implements Context {
	use BasicStructure;
	use CommandLine;
	use Provisioning;

	/**
	 * @BeforeScenario @RateLimiting
	 */
	public function enableRateLimiting() {
		// Enable rate limiting for the tests.
		// Ratelimiting is disabled by default, so we need to enable it
		$this->runOcc(['config:system:set', 'ratelimit.protection.enabled', '--value', 'true', '--type', 'bool']);
	}

	/**
	 * @AfterScenario @RateLimiting
	 */
	public function disableRateLimiting() {
		// Restore the default rate limiting configuration.
		// Ratelimiting is disabled by default, so we need to disable it
		$this->runOcc(['config:system:set', 'ratelimit.protection.enabled', '--value', 'false', '--type', 'bool']);
	}
}
