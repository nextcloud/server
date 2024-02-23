<?php
/**
 * @copyright Copyright (c) 2021, Philip Gatzka (philip.gatzka@mailbox.org)
 *
 * @author Philip Gatzka <philip.gatzka@mailbox.org>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Core\Command\User;

use OC\Core\Command\User\Add;
use OCA\Settings\Mailer\NewUserMailHelper;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IEMailTemplate;
use OCP\mail\IMailer;
use OCP\Security\ISecureRandom;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class AddTest extends TestCase {
	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	private $userManager;

	/** @var IGroupManager|\PHPUnit\Framework\MockObject\MockObject */
	private $groupManager;

	/** @var IMailer|\PHPUnit\Framework\MockObject\MockObject */
	private $mailer;

	/** @var IAppConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $appConfig;

	/** @var NewUserMailHelper|\PHPUnit\Framework\MockObject\MockObject */
	private $mailHelper;

	/** @var IEventDispatcher|\PHPUnit\Framework\MockObject\MockObject */
	private $eventDispatcher;

	/** @var ISecureRandom|\PHPUnit\Framework\MockObject\MockObject */
	private $secureRandom;

	/** @var IUser|\PHPUnit\Framework\MockObject\MockObject */
	private $user;

	/** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $consoleInput;

	/** @var OutputInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $consoleOutput;

	/** @var Add */
	private $addCommand;

	public function setUp(): void {
		parent::setUp();

		$this->userManager = static::createMock(IUserManager::class);
		$this->groupManager = static::createStub(IGroupManager::class);
		$this->mailer = static::createMock(IMailer::class);
		$this->appConfig = static::createMock(IAppConfig::class);
		$this->mailHelper = static::createMock(NewUserMailHelper::class);
		$this->eventDispatcher = static::createStub(IEventDispatcher::class);
		$this->secureRandom = static::createStub(ISecureRandom::class);

		$this->user = static::createMock(IUser::class);

		$this->consoleInput = static::createMock(InputInterface::class);
		$this->consoleOutput = static::createMock(OutputInterface::class);

		$this->addCommand = new Add(
			$this->userManager,
			$this->groupManager,
			$this->mailer,
			$this->appConfig,
			$this->mailHelper,
			$this->eventDispatcher,
			$this->secureRandom
		);
	}

	/**
	 * @dataProvider addEmailDataProvider
	 */
	public function testAddEmail(
		?string $email,
		bool $isEmailValid,
		bool $shouldSendEmail,
	): void {
		$this->user->expects($isEmailValid ? static::once() : static::never())
			->method('setSystemEMailAddress')
			->with(static::equalTo($email));

		$this->userManager->method('createUser')
			->willReturn($this->user);

		$this->appConfig->method('getValueString')
			->willReturn($shouldSendEmail ? 'yes' : 'no');

		$this->mailer->method('validateMailAddress')
			->willReturn($isEmailValid);

		$this->mailHelper->method('generateTemplate')
			->willReturn(static::createMock(IEMailTemplate::class));

		$this->mailHelper->expects($isEmailValid && $shouldSendEmail ? static::once() : static::never())
			->method('sendMail');

		$this->consoleInput->method('getOption')
			->will(static::returnValueMap([
				['generate-password', 'true'],
				['email', $email],
				['group', []],
			]));

		$this->invokePrivate($this->addCommand, 'execute', [
			$this->consoleInput,
			$this->consoleOutput
		]);
	}

	/**
	 * @return array
	 */
	public function addEmailDataProvider(): array {
		return [
			'Valid E-Mail' => [
				'info@example.com',
				true,
				true,
			],
			'Invalid E-Mail' => [
				'info@@example.com',
				false,
				false,
			],
			'No E-Mail' => [
				'',
				false,
				false,
			],
			'Valid E-Mail, but no mail should be sent' => [
				'info@example.com',
				true,
				false,
			],
		];
	}
}
