<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use PHPUnit\Framework\Assert;

require __DIR__ . '/autoload.php';

class RoutingContext implements Context, SnippetAcceptingContext {
	use Provisioning;
	use AppConfiguration;
	use CommandLine;

	protected function resetAppConfigs(): void {
	}

	/**
	 * @AfterScenario
	 */
	public function deleteMemcacheSetting(): void {
		$this->invokingTheCommand('config:system:delete memcache.local');
	}

	/**
	 * @Given /^route "([^"]*)" of app "([^"]*)" is defined in routes.php$/
	 */
	public function routeOfAppIsDefinedInRoutesPhP(string $route, string $app): void {
		$previousUser = $this->currentUser;
		$this->currentUser = 'admin';

		$this->sendingTo('GET', "/apps/testing/api/v1/routes/routesphp/{$app}");
		$this->theHTTPStatusCodeShouldBe('200');

		Assert::assertStringContainsString($route, $this->response->getBody()->getContents());

		$this->currentUser = $previousUser;
	}
}
