<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\Settings\Tests\Settings\Admin;

use OCA\Settings\Settings\Admin\Mail;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use Test\TestCase;

class MailTest extends TestCase {
	/** @var Mail */
	private $admin;
	/** @var IConfig */
	private $config;
	/** @var IL10N */
	private $l10n;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
		$this->l10n = $this->getMockBuilder(IL10N::class)->getMock();

		$this->admin = new Mail(
			$this->config,
			$this->l10n
		);
	}

	public function testGetForm() {
		$this->config
			->expects($this->any())
			->method('getSystemValue')
			->willReturnMap([
				['mail_domain', '', 'mx.nextcloud.com'],
				['mail_from_address', '', 'no-reply@nextcloud.com'],
				['mail_smtpmode', '', 'smtp'],
				['mail_smtpsecure', '', true],
				['mail_smtphost', '', 'smtp.nextcloud.com'],
				['mail_smtpport', '', 25],
				['mail_smtpauth', false, true],
				['mail_smtpname', '', 'smtp.sender.com'],
				['mail_smtppassword', '', 'mypassword'],
				['mail_sendmailmode', 'smtp', 'smtp'],
			]);

		$expected = new TemplateResponse(
			'settings',
			'settings/admin/additional-mail',
			[
				'sendmail_is_available' => (bool) \OC_Helper::findBinaryPath('sendmail'),
				'mail_domain' => 'mx.nextcloud.com',
				'mail_from_address' => 'no-reply@nextcloud.com',
				'mail_smtpmode' => 'smtp',
				'mail_smtpsecure' => true,
				'mail_smtphost' => 'smtp.nextcloud.com',
				'mail_smtpport' => 25,
				'mail_smtpauth' => true,
				'mail_smtpname' => 'smtp.sender.com',
				'mail_smtppassword' => '********',
				'mail_sendmailmode' => 'smtp',
			],
			''
		);

		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection() {
		$this->assertSame('server', $this->admin->getSection());
	}

	public function testGetPriority() {
		$this->assertSame(10, $this->admin->getPriority());
	}
}
