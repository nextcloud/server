<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

use OC\Share\MailNotifications;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Mail\IMailer;
use OCP\ILogger;
use OCP\Defaults;

/**
 * Class MailNotificationsTest
 */
class MailNotificationsTest extends \Test\TestCase {
	/** @var IConfig */
	private $config;
	/** @var IL10N */
	private $l10n;
	/** @var IMailer */
	private $mailer;
	/** @var ILogger */
	private $logger;
	/** @var Defaults */
	private $defaults;


	public function setUp() {
		parent::setUp();

		$this->config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()->getMock();
		$this->l10n = $this->getMockBuilder('\OCP\IL10N')
			->disableOriginalConstructor()->getMock();
		$this->mailer = $this->getMockBuilder('\OCP\Mail\IMailer')
			->disableOriginalConstructor()->getMock();
		$this->logger = $this->getMockBuilder('\OCP\ILogger')
			->disableOriginalConstructor()->getMock();
		$this->defaults = $this->getMockBuilder('\OCP\Defaults')
			->disableOriginalConstructor()->getMock();

		$this->l10n->expects($this->any())
			->method('t')
			->will($this->returnCallback(function($text, $parameters = array()) {
				return vsprintf($text, $parameters);
			}));
	}

	public function testSendLinkShareMailWithoutReplyTo() {
		$message = $this->getMockBuilder('\OC\Mail\Message')
			->disableOriginalConstructor()->getMock();

		$message
			->expects($this->once())
			->method('setSubject')
			->with('TestUser shared »MyFile« with you');
		$message
			->expects($this->once())
			->method('setTo')
			->with(['lukas@owncloud.com']);
		$message
			->expects($this->once())
			->method('setHtmlBody');
		$message
			->expects($this->once())
			->method('setPlainBody');
		$message
			->expects($this->once())
			->method('setFrom')
			->with([\OCP\Util::getDefaultEmailAddress('sharing-noreply') => 'TestUser via UnitTestCloud']);

		$this->mailer
			->expects($this->once())
			->method('createMessage')
			->will($this->returnValue($message));
		$this->mailer
			->expects($this->once())
			->method('send')
			->with($message)
			->will($this->returnValue([]));

		$this->defaults
			->expects($this->once())
			->method('getName')
			->will($this->returnValue('UnitTestCloud'));

		$this->config
			->expects($this->at(0))
			->method('getUserValue')
			->with('TestUser', 'settings', 'email', null)
			->will($this->returnValue('sharer@owncloud.com'));

		$mailNotifications = new MailNotifications(
			'TestUser',
			$this->config,
			$this->l10n,
			$this->mailer,
			$this->logger,
			$this->defaults
		);

		$this->assertSame([], $mailNotifications->sendLinkShareMail('lukas@owncloud.com', 'MyFile', 'https://owncloud.com/file/?foo=bar', 3600));
	}

	public function testSendLinkShareMailWithReplyTo() {
		$message = $this->getMockBuilder('\OC\Mail\Message')
			->disableOriginalConstructor()->getMock();

		$message
			->expects($this->once())
			->method('setSubject')
			->with('TestUser shared »MyFile« with you');
		$message
			->expects($this->once())
			->method('setTo')
			->with(['lukas@owncloud.com']);
		$message
			->expects($this->once())
			->method('setHtmlBody');
		$message
			->expects($this->once())
			->method('setPlainBody');
		$message
			->expects($this->once())
			->method('setFrom')
			->with([\OCP\Util::getDefaultEmailAddress('sharing-noreply') => 'TestUser via UnitTestCloud']);
		$message
			->expects($this->once())
			->method('setReplyTo')
			->with(['sharer@owncloud.com']);

		$this->mailer
			->expects($this->once())
			->method('createMessage')
			->will($this->returnValue($message));
		$this->mailer
			->expects($this->once())
			->method('send')
			->with($message)
			->will($this->returnValue([]));

		$this->defaults
			->expects($this->once())
			->method('getName')
			->will($this->returnValue('UnitTestCloud'));

		$this->config
			->expects($this->at(0))
			->method('getUserValue')
			->with('TestUser', 'settings', 'email', null)
			->will($this->returnValue('sharer@owncloud.com'));

		$mailNotifications = new MailNotifications(
			'TestUser',
			$this->config,
			$this->l10n,
			$this->mailer,
			$this->logger,
			$this->defaults
		);
		$this->assertSame([], $mailNotifications->sendLinkShareMail('lukas@owncloud.com', 'MyFile', 'https://owncloud.com/file/?foo=bar', 3600));
	}

	public function testSendLinkShareMailException() {
		$message = $this->getMockBuilder('\OC\Mail\Message')
			->disableOriginalConstructor()->getMock();

		$message
			->expects($this->once())
			->method('setSubject')
			->with('TestUser shared »MyFile« with you');
		$message
			->expects($this->once())
			->method('setTo')
			->with(['lukas@owncloud.com']);
		$message
			->expects($this->once())
			->method('setHtmlBody');
		$message
			->expects($this->once())
			->method('setPlainBody');
		$message
			->expects($this->once())
			->method('setFrom')
			->with([\OCP\Util::getDefaultEmailAddress('sharing-noreply') => 'TestUser via UnitTestCloud']);

		$this->mailer
			->expects($this->once())
			->method('createMessage')
			->will($this->returnValue($message));
		$this->mailer
			->expects($this->once())
			->method('send')
			->with($message)
			->will($this->throwException(new Exception('Some Exception Message')));

		$this->defaults
			->expects($this->once())
			->method('getName')
			->will($this->returnValue('UnitTestCloud'));

		$this->config
			->expects($this->at(0))
			->method('getUserValue')
			->with('TestUser', 'settings', 'email', null)
			->will($this->returnValue('sharer@owncloud.com'));

		$mailNotifications = new MailNotifications(
			'TestUser',
			$this->config,
			$this->l10n,
			$this->mailer,
			$this->logger,
			$this->defaults
		);

		$this->assertSame(['lukas@owncloud.com'], $mailNotifications->sendLinkShareMail('lukas@owncloud.com', 'MyFile', 'https://owncloud.com/file/?foo=bar', 3600));
	}

}
