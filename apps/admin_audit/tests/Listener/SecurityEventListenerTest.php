<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\AdminAudit\Tests\Actions;

use OCA\AdminAudit\Listener\SecurityEventListener;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserDisabled;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserEnabled;
use OCP\IUser;
use OCA\AdminAudit\AuditLogger;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SecurityEventListenerTest extends TestCase {
	/** @var AuditLogger|MockObject */
	private $logger;

	private SecurityEventListener $security;

	/** @var IUser|MockObject */
	private $user;

	/** @var IProvider|(IProvider&MockObject)|MockObject */
	private $provider;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(AuditLogger::class);
		$this->security = new SecurityEventListener($this->logger);

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn('myuid');
		$this->user->method('getDisplayName')->willReturn('mydisplayname');
		$this->provider = $this->createMock(IProvider::class);
		$this->provider->method('getDisplayName')->willReturn('myprovider');
	}

	public function testTwofactorFailed() {
		$this->logger->expects($this->once())
			->method('info')
			->with(
				$this->equalTo('Failed two factor attempt by user mydisplayname (myuid) with provider myprovider'),
				['app' => 'admin_audit']
			);

		$this->security->handle(new TwoFactorProviderForUserEnabled($this->user, $this->provider));
	}

	public function testTwofactorSuccess() {
		$this->logger->expects($this->once())
			->method('info')
			->with(
				$this->equalTo('Successful two factor attempt by user mydisplayname (myuid) with provider myprovider'),
				['app' => 'admin_audit']
			);

		$this->security->handle(new TwoFactorProviderForUserDisabled($this->user, $this->provider));
	}
}
