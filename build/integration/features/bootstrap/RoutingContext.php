<?php
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;

require __DIR__ . '/../../vendor/autoload.php';

class RoutingContext implements Context, SnippetAcceptingContext {
	use Provisioning;
	use AppConfiguration;
	use CommandLine;

	protected function resetAppConfigs(): void {
	}
}
