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
use OCP\IConfig;
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
	/**
	 * @dataProvider addEmailDataProvider
	 */
	public function testAddEmail(?string $email, bool $isValid, bool $shouldSendMail): void {
		$userManager = static::createMock(IUserManager::class);
		$groupManager = static::createStub(IGroupManager::class);
		$mailer = static::createMock(IMailer::class);
		$user = static::createMock(IUser::class);
		$config = static::createMock(IConfig::class);
		$mailHelper = static::createMock(NewUserMailHelper::class);
		$eventDispatcher = static::createStub(IEventDispatcher::class);
		$secureRandom = static::createStub(ISecureRandom::class);

		$consoleInput = static::createMock(InputInterface::class);
		$consoleOutput = static::createMock(OutputInterface::class);

		$user->expects($isValid ? static::once() : static::never())
			->method('setSystemEMailAddress')
			->with(static::equalTo($email));

		$userManager->method('createUser')
			->willReturn($user);

		$config->method('getAppValue')
			->willReturn($shouldSendMail ? 'yes' : 'no');

		$mailer->method('validateMailAddress')
			->willReturn($isValid);

		$mailHelper->method('generateTemplate')
			->willReturn(static::createMock(IEMailTemplate::class));

		$mailHelper->expects($isValid && $shouldSendMail ? static::once() : static::never())
			->method('sendMail');

		$consoleInput->method('getOption')
			->will(static::returnValueMap([
				['password-from-env', ''],
				['email', $email],
				['group', []],
			]));

		$addCommand = new Add(
			$userManager,
			$groupManager,
			$mailer,
			$config,
			$mailHelper,
			$eventDispatcher,
			$secureRandom
		);

		$this->invokePrivate($addCommand, 'execute', [
			$consoleInput,
			$consoleOutput
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
				true,
			],
			'No E-Mail' => [
				'',
				false,
				true,
			],
			'Valid E-Mail, but no mail should be sent' => [
				'info@example.com',
				true,
				false,
			],
		];
	}
}
