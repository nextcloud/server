<?php
declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

namespace OCA\Settings\Tests\Settings\Personal;

use OC\Authentication\TwoFactorAuth\ProviderLoader;
use OCP\IInitialStateService;
use OCA\Settings\Personal\Security;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SecurityTest extends TestCase {

	/** @var IInitialStateService|MockObject */
	private $initialStateService;

	/** @var IUserManager|MockObject */
	private $userManager;

	/** @var ProviderLoader|MockObject */
	private $providerLoader;

	/** @var IUserSession|MockObject */
	private $userSession;

	/** @var IConfig|MockObject */
	private $config;

	/** @var string */
	private $uid;

	/** @var Security */
	private $section;

	public function setUp() {
		parent::setUp();

		$this->initialStateService = $this->createMock(IInitialStateService::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->providerLoader = $this->createMock(ProviderLoader::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->config = $this->createMock(IConfig::class);
		$this->uid = 'test123';

		$this->section = new Security(
			$this->initialStateService,
			$this->userManager,
			$this->providerLoader,
			$this->userSession,
			$this->config,
			$this->uid
		);
	}

	public function testGetForm() {
		$user = $this->createMock(IUser::class);
		$this->userManager->expects($this->once())
			->method('get')
			->with($this->uid)
			->willReturn($user);
		$user->expects($this->once())
			->method('canChangePassword')
			->willReturn(true);
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->providerLoader->expects($this->once())
			->method('getProviders')
			->with($user)
			->willReturn([]);
		$this->config->expects($this->once())
			->method('getUserValue')
			->with(
				$this->uid,
				'accessibility',
				'theme',
				false
			)
			->willReturn(false);

		$form = $this->section->getForm();

		$expected = new TemplateResponse('settings', 'settings/personal/security', [
			'passwordChangeSupported' => true,
			'twoFactorProviderData' => [
				'providers' => [],
			],
			'themedark' => false,
		]);
		$this->assertEquals($expected, $form);
	}

}
