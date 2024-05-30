<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use Behat\Behat\Context\Context;

require __DIR__ . '/../../vendor/autoload.php';


/**
 * Setup context.
 */
class SetupContext implements Context {
	use BasicStructure;
}
