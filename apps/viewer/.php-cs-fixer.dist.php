<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

require_once './vendor/autoload.php';

use Nextcloud\CodingStandard\Config;

$config = new Config();
$config
	->getFinder()
	->notPath('build')
	->notPath('l10n')
	->notPath('src')
	->notPath('vendor')
	->notPath('composer')
	->in(__DIR__);
return $config;
