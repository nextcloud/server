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

namespace OCA\Files\Tests\Settings;

use bantu\IniGetWrapper\IniGetWrapper;
use OCA\Files\Settings\Admin;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;
use Test\TestCase;

class AdminTest extends TestCase {
	/** @var Admin */
	private $admin;
	/** @var IniGetWrapper */
	private $iniGetWrapper;
	/** @var IRequest */
	private $request;

	public function setUp() {
		parent::setUp();
		$this->iniGetWrapper = $this->getMockBuilder('\bantu\IniGetWrapper\IniGetWrapper')->disableOriginalConstructor()->getMock();
		$this->request = $this->getMockBuilder('\OCP\IRequest')->getMock();
		$this->admin = new Admin(
			$this->iniGetWrapper,
			$this->request
		);
	}

	public function testGetForm() {
		$htaccessWorking  = (getenv('htaccessWorking') == 'true');
		$htaccessWritable = is_writable(\OC::$SERVERROOT.'/.htaccess');
		$userIniWritable  = is_writable(\OC::$SERVERROOT.'/.user.ini');

		$this->iniGetWrapper
			->expects($this->at(0))
			->method('getBytes')
			->with('upload_max_filesize')
			->willReturn(1234);
		$this->iniGetWrapper
			->expects($this->at(1))
			->method('getBytes')
			->with('post_max_size')
			->willReturn(1234);
		$params = [
			'uploadChangable'              => (($htaccessWorking and $htaccessWritable) or $userIniWritable ),
			'uploadMaxFilesize'            => '1 KB',
			'displayMaxPossibleUploadSize' => PHP_INT_SIZE === 4,
			'maxPossibleUploadSize'        => Util::humanFileSize(PHP_INT_MAX),
		];
		$expected = new TemplateResponse('files', 'admin', $params, '');
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection() {
		$this->assertSame('additional', $this->admin->getSection());
	}

	public function testGetPriority() {
		$this->assertSame(5, $this->admin->getPriority());
	}
}
