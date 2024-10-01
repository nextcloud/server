<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Config\App;

use OCP\IConfig;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;

abstract class Base extends \OC\Core\Command\Base {
	protected IConfig $config;

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context) {
		if ($argumentName === 'app') {
			return \OC_App::getAllApps();
		}

		if ($argumentName === 'name') {
			$appName = $context->getWordAtIndex($context->getWordIndex() - 1);
			return $this->config->getAppKeys($appName);
		}
		return [];
	}
}
