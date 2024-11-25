<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption\Tests\Command;

use OCA\Encryption\Command\EnableMasterKey;
use OCA\Encryption\Util;
use OCP\IConfig;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class TestEnableMasterKey extends TestCase {

	/** @var EnableMasterKey */
	protected $enableMasterKey;

	/** @var Util | \PHPUnit\Framework\MockObject\MockObject */
	protected $util;

	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;

	/** @var \Symfony\Component\Console\Helper\QuestionHelper | \PHPUnit\Framework\MockObject\MockObject */
	protected $questionHelper;

	/** @var \Symfony\Component\Console\Output\OutputInterface | \PHPUnit\Framework\MockObject\MockObject */
	protected $output;

	/** @var \Symfony\Component\Console\Input\InputInterface | \PHPUnit\Framework\MockObject\MockObject */
	protected $input;

	protected function setUp(): void {
		parent::setUp();

		$this->util = $this->getMockBuilder(Util::class)
			->disableOriginalConstructor()->getMock();
		$this->config = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()->getMock();
		$this->questionHelper = $this->getMockBuilder(QuestionHelper::class)
			->disableOriginalConstructor()->getMock();
		$this->output = $this->getMockBuilder(OutputInterface::class)
			->disableOriginalConstructor()->getMock();
		$this->input = $this->getMockBuilder(InputInterface::class)
			->disableOriginalConstructor()->getMock();

		$this->enableMasterKey = new EnableMasterKey($this->util, $this->config, $this->questionHelper);
	}

	/**
	 * @dataProvider dataTestExecute
	 *
	 * @param bool $isAlreadyEnabled
	 * @param string $answer
	 */
	public function testExecute($isAlreadyEnabled, $answer): void {
		$this->util->expects($this->once())->method('isMasterKeyEnabled')
			->willReturn($isAlreadyEnabled);

		if ($isAlreadyEnabled) {
			$this->output->expects($this->once())->method('writeln')
				->with('Master key already enabled');
		} else {
			if ($answer === 'y') {
				$this->questionHelper->expects($this->once())->method('ask')->willReturn(true);
				$this->config->expects($this->once())->method('setAppValue')
					->with('encryption', 'useMasterKey', '1');
			} else {
				$this->questionHelper->expects($this->once())->method('ask')->willReturn(false);
				$this->config->expects($this->never())->method('setAppValue');
			}
		}

		$this->invokePrivate($this->enableMasterKey, 'execute', [$this->input, $this->output]);
	}

	public function dataTestExecute() {
		return [
			[true, ''],
			[false, 'y'],
			[false, 'n'],
			[false, '']
		];
	}
}
