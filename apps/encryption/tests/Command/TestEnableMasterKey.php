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
use OCP\AppFramework\Services\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class TestEnableMasterKey extends TestCase {
	public function __construct() {
		parent::__construct(static::class);
	}
	protected EnableMasterKey $enableMasterKey;
	protected Util&MockObject $util;
	protected IAppConfig&MockObject $config;
	protected QuestionHelper&MockObject $questionHelper;
	protected OutputInterface&MockObject $output;
	protected InputInterface&MockObject $input;

	protected function setUp(): void {
		parent::setUp();

		$this->util = $this->getMockBuilder(Util::class)
			->disableOriginalConstructor()->getMock();
		$this->config = $this->getMockBuilder(IAppConfig::class)
			->disableOriginalConstructor()->getMock();
		$this->questionHelper = $this->getMockBuilder(QuestionHelper::class)
			->disableOriginalConstructor()->getMock();
		$this->output = $this->getMockBuilder(OutputInterface::class)
			->disableOriginalConstructor()->getMock();
		$this->input = $this->getMockBuilder(InputInterface::class)
			->disableOriginalConstructor()->getMock();

		$this->enableMasterKey = new EnableMasterKey($this->util, $this->config, $this->questionHelper);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataTestExecute')]
	public function testExecute(bool $isAlreadyEnabled, string $answer): void {
		$this->util->expects($this->once())->method('isMasterKeyEnabled')
			->willReturn($isAlreadyEnabled);

		if ($isAlreadyEnabled) {
			$this->output->expects($this->once())->method('writeln')
				->with('Master key already enabled');
		} else {
			if ($answer === 'y') {
				$this->questionHelper->expects($this->once())->method('ask')->willReturn(true);
				$this->config->expects($this->once())->method('setAppValueBool')
					->with('useMasterKey', true);
			} else {
				$this->questionHelper->expects($this->once())->method('ask')->willReturn(false);
				$this->config->expects($this->never())->method('setAppValue');
			}
		}

		$this->invokePrivate($this->enableMasterKey, 'execute', [$this->input, $this->output]);
	}

	public static function dataTestExecute() {
		return [
			[true, ''],
			[false, 'y'],
			[false, 'n'],
			[false, '']
		];
	}
}
