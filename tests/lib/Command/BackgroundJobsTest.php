<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Christian Kampka <christian@kampka.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
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
