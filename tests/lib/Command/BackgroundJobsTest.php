<?php
/**
 * SPDX-FileCopyrightText: 2016-2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015 Christian Kampka <christian@kampka.net>
 * SPDX-License-Identifier: MIT
 */
namespace Test\Command;

use OC\Core\Command\Background\Ajax;
use OC\Core\Command\Background\Cron;
use OC\Core\Command\Background\WebCron;
use OCP\IAppConfig;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Test\TestCase;

class BackgroundJobsTest extends TestCase {
	public function testCronCommand() {
		$appConfig = \OCP\Server::get(IAppConfig::class);
		$job = new Cron($appConfig);
		$job->run(new StringInput(''), new NullOutput());
		$this->assertEquals('cron', $appConfig->getValueString('core', 'backgroundjobs_mode'));
	}

	public function testAjaxCommand() {
		$appConfig = \OCP\Server::get(IAppConfig::class);
		$job = new Ajax($appConfig);
		$job->run(new StringInput(''), new NullOutput());
		$this->assertEquals('ajax', $appConfig->getValueString('core', 'backgroundjobs_mode'));
	}

	public function testWebCronCommand() {
		$appConfig = \OCP\Server::get(IAppConfig::class);
		$job = new WebCron($appConfig);
		$job->run(new StringInput(''), new NullOutput());
		$this->assertEquals('webcron', $appConfig->getValueString('core', 'backgroundjobs_mode'));
	}
}
