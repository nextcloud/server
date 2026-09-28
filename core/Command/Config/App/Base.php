<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Config\App;

use OC\Config\ConfigManager;
use OCP\IAppConfig;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;

abstract class Base extends \OC\Core\Command\Base {
	public function __construct(
		protected IAppConfig $appConfig,
		protected readonly ConfigManager $configManager,
	) {
		parent::__construct();
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context) {
		if ($argumentName === 'app') {
			return $this->appConfig->getApps();
		}

		if ($argumentName === 'name') {
			$appName = $context->getWordAtIndex($context->getWordIndex() - 1);
			return $this->appConfig->getKeys($appName);
		}
		return [];
	}
}
