<?php
/**
 * @copyright 2017, Morris Jobke <hey@morrisjobke.de>
 *
 * @author Morris Jobke <hey@morrisjobke.de>
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

namespace Test\Mail;

use OC\Mail\EMailTemplate;
use OC_Defaults;
use Test\TestCase;

class EMailTemplateTest extends TestCase {
	/** @var OC_Defaults */
	private $defaults;

	function setUp() {
		parent::setUp();

		$this->defaults = $this->getMockBuilder('\OC_Defaults')
			->disableOriginalConstructor()->getMock();

		$this->defaults
			->expects($this->any())
			->method('getColorPrimary')
			->willReturn('#0082c9');
	}

	public function testEMailTemplate() {
		$emailTemplate = new EMailTemplate($this->defaults);

		$emailTemplate->addHeader('https://example.org/img/logo-mail-header.png');

		$emailTemplate->addHeading('Welcome aboard');
		$emailTemplate->addBodyText('You have now an Nextcloud account, you can add, protect, and share your data.');
		$emailTemplate->addBodyText('Your username is: abc');


		$emailTemplate->addBodyButtonGroup(
			'Set your password', 'https://example.org/resetPassword/123',
			'Install Client', 'https://nextcloud.com/install/#install-clients'
		);

		$emailTemplate->addFooter(
			'https://example.org/img/logo-mail-footer.png',
			'TestCloud - A safe home for your data<br>This is an automatically generated email, please do not reply.'
		);

		$expectedHTML = file_get_contents(\OC::$SERVERROOT . '/tests/data/emails/new-account-email.html');
		$this->assertSame($expectedHTML, $emailTemplate->renderHTML());


		$expectedTXT = file_get_contents(\OC::$SERVERROOT . '/tests/data/emails/new-account-email.txt');
		$this->assertSame($expectedTXT, $emailTemplate->renderText());
	}


}
