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

use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Test\TestCase;

class BackgroundJobsTest extends TestCase {
	public function testCronCommand() {
		$config = \OC::$server->getConfig();
		$job = new Cron($config);
		$job->run(new StringInput(''), new NullOutput());
		$this->assertEquals('cron', $config->getAppValue('core', 'backgroundjobs_mode'));
	}

	public function testAjaxCommand() {
		$config = \OC::$server->getConfig();
		$job = new Ajax($config);
		$job->run(new StringInput(''), new NullOutput());
		$this->assertEquals('ajax', $config->getAppValue('core', 'backgroundjobs_mode'));
	}

	public function testWebCronCommand() {
		$config = \OC::$server->getConfig();
		$job = new WebCron($config);
		$job->run(new StringInput(''), new NullOutput());
		$this->assertEquals('webcron', $config->getAppValue('core', 'backgroundjobs_mode'));
	}
}
