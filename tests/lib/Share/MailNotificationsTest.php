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

namespace Test\Share;

use OC\Share\MailNotifications;
use OCP\IL10N;
use OCP\IUser;
use OCP\Mail\IMailer;
use OCP\ILogger;
use OCP\Defaults;
use OCP\IURLGenerator;

/**
 * Class MailNotificationsTest
 */
class MailNotificationsTest extends \Test\TestCase {
	/** @var IL10N */
	private $l10n;
	/** @var IMailer | \PHPUnit_Framework_MockObject_MockObject */
	private $mailer;
	/** @var ILogger */
	private $logger;
	/** @var Defaults | \PHPUnit_Framework_MockObject_MockObject */
	private $defaults;
	/** @var IUser | \PHPUnit_Framework_MockObject_MockObject */
	private $user;
	/** @var IURLGenerator | \PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;


	public function setUp() {
		parent::setUp();

		$this->l10n = $this->getMockBuilder('\OCP\IL10N')
			->disableOriginalConstructor()->getMock();
		$this->mailer = $this->getMockBuilder('\OCP\Mail\IMailer')
			->disableOriginalConstructor()->getMock();
		$this->logger = $this->getMockBuilder('\OCP\ILogger')
			->disableOriginalConstructor()->getMock();
		$this->defaults = $this->getMockBuilder('\OCP\Defaults')
				->disableOriginalConstructor()->getMock();
		$this->user = $this->getMockBuilder('\OCP\IUser')
				->disableOriginalConstructor()->getMock();
		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		$this->l10n->expects($this->any())
			->method('t')
			->will($this->returnCallback(function($text, $parameters = array()) {
				return vsprintf($text, $parameters);
			}));

		$this->defaults
				->expects($this->once())
				->method('getName')
				->will($this->returnValue('UnitTestCloud'));

		$this->user
				->expects($this->once())
				->method('getEMailAddress')
				->willReturn('sharer@owncloud.com');
		$this->user
				->expects($this->once())
				->method('getDisplayName')
				->willReturn('TestUser');

	}

	/**
	 * @param string $subject
	 */
	protected function setupMailerMock($subject, $to, $exceptionOnSend = true) {
		$message = $this->getMockBuilder('\OC\Mail\Message')
				->disableOriginalConstructor()->getMock();

		$message
				->expects($this->once())
				->method('setSubject')
				->with($subject);
		$message
				->expects($this->once())
				->method('setTo')
				->with($to);
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
		if ($exceptionOnSend) {
			$this->mailer
					->expects($this->once())
					->method('send')
					->with($message)
					->will($this->throwException(new \Exception('Some Exception Message')));
		}
	}
}
