<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
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

namespace Test\Settings\Admin;

use OC\Settings\Admin\Additional;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use Test\TestCase;

class AdditionalTest extends TestCase {
	/** @var Additional */
	private $admin;
	/** @var IConfig */
	private $config;

	public function setUp() {
		parent::setUp();
		$this->config = $this->getMockBuilder('\OCP\IConfig')->getMock();

		$this->admin = new Additional(
			$this->config
		);
	}

	public function testGetForm() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('mail_domain', '')
			->willReturn('mx.nextcloud.com');
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('mail_from_address', '')
			->willReturn('no-reply@nextcloud.com');
		$this->config
			->expects($this->at(2))
			->method('getSystemValue')
			->with('mail_smtpmode', '')
			->willReturn('php');
		$this->config
			->expects($this->at(3))
			->method('getSystemValue')
			->with('mail_smtpsecure', '')
			->willReturn(true);
		$this->config
			->expects($this->at(4))
			->method('getSystemValue')
			->with('mail_smtphost', '')
			->willReturn('smtp.nextcloud.com');
		$this->config
			->expects($this->at(5))
			->method('getSystemValue')
			->with('mail_smtpport', '')
			->willReturn(25);
		$this->config
			->expects($this->at(6))
			->method('getSystemValue')
			->with('mail_smtpauthtype', '')
			->willReturn('login');
		$this->config
			->expects($this->at(7))
			->method('getSystemValue')
			->with('mail_smtpauth', false)
			->willReturn(true);
		$this->config
			->expects($this->at(8))
			->method('getSystemValue')
			->with('mail_smtpname', '')
			->willReturn('smtp.sender.com');
		$this->config
			->expects($this->at(9))
			->method('getSystemValue')
			->with('mail_smtppassword', '')
			->willReturn('mypassword');

		$expected = new TemplateResponse(
			'settings',
			'admin/additional-mail',
			[
				'sendmail_is_available' => (bool) \OC_Helper::findBinaryPath('sendmail'),
				'mail_domain'           => 'mx.nextcloud.com',
				'mail_from_address'     => 'no-reply@nextcloud.com',
				'mail_smtpmode'         => 'php',
				'mail_smtpsecure'       => true,
				'mail_smtphost'         => 'smtp.nextcloud.com',
				'mail_smtpport'         => 25,
				'mail_smtpauthtype'     => 'login',
				'mail_smtpauth'         => true,
				'mail_smtpname'         => 'smtp.sender.com',
				'mail_smtppassword'     => 'mypassword',
			],
			''
		);

		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection() {
		$this->assertSame('additional', $this->admin->getSection());
	}

	public function testGetPriority() {
		$this->assertSame(0, $this->admin->getPriority());
	}
}
