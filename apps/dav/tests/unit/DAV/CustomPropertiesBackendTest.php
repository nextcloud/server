<?php
/**
 * @copyright Copyright (c) 2017, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
namespace OCA\DAV\Tests\DAV;

use OCA\DAV\DAV\CustomPropertiesBackend;
use OCP\IDBConnection;
use OCP\IUser;
use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Tree;
use Test\TestCase;

class CustomPropertiesBackendTest extends TestCase {

	/** @var Tree | \PHPUnit_Framework_MockObject_MockObject */
	private $tree;

	/** @var  IDBConnection | \PHPUnit_Framework_MockObject_MockObject */
	private $dbConnection;

	/** @var IUser | \PHPUnit_Framework_MockObject_MockObject */
	private $user;

	/** @var CustomPropertiesBackend | \PHPUnit_Framework_MockObject_MockObject */
	private $backend;

	public function setUp() {
		parent::setUp();

		$this->tree = $this->createMock(Tree::class);
		$this->dbConnection = $this->createMock(IDBConnection::class);
		$this->user = $this->createMock(IUser::class);
		$this->user->expects($this->once())
			->method('getUID')
			->with()
			->will($this->returnValue('dummy_user_42'));

		$this->backend = new CustomPropertiesBackend($this->tree,
			$this->dbConnection, $this->user);
	}

	public function testPropFindNoDbCalls() {
		$propFind = $this->createMock(PropFind::class);
		$propFind->expects($this->at(0))
			->method('get404Properties')
			->with()
			->will($this->returnValue([
				'{http://owncloud.org/ns}permissions',
				'{http://owncloud.org/ns}downloadURL',
				'{http://owncloud.org/ns}dDC',
				'{http://owncloud.org/ns}size',
			]));

		$this->dbConnection->expects($this->never())
			->method($this->anything());

		$this->backend->propFind('foo_bar_path_1337_0', $propFind);
	}

	public function testPropFindCalendarCall() {
		$propFind = $this->createMock(PropFind::class);
		$propFind->expects($this->at(0))
			->method('get404Properties')
			->with()
			->will($this->returnValue([
				'{DAV:}getcontentlength',
				'{DAV:}getcontenttype',
				'{DAV:}getetag',
				'{abc}def'
			]));

		$propFind->expects($this->at(1))
			->method('getRequestedProperties')
			->with()
			->will($this->returnValue([
				'{DAV:}getcontentlength',
				'{DAV:}getcontenttype',
				'{DAV:}getetag',
				'{DAV:}displayname',
				'{urn:ietf:params:xml:ns:caldav}calendar-description',
				'{urn:ietf:params:xml:ns:caldav}calendar-timezone',
				'{abc}def'
			]));

		$statement = $this->createMock('\Doctrine\DBAL\Driver\Statement');
		$this->dbConnection->expects($this->once())
			->method('executeQuery')
			->with('SELECT * FROM `*PREFIX*properties` WHERE `userid` = ? AND `propertypath` = ? AND `propertyname` in (?)',
				['dummy_user_42', 'calendars/foo/bar_path_1337_0', [
					3 => '{abc}def',
					4 => '{DAV:}displayname',
					5 => '{urn:ietf:params:xml:ns:caldav}calendar-description',
					6 => '{urn:ietf:params:xml:ns:caldav}calendar-timezone']],
				[null, null, 102])
			->will($this->returnValue($statement));

		$this->backend->propFind('calendars/foo/bar_path_1337_0', $propFind);
	}

	/**
	 * @dataProvider propPatchProvider
	 */
	public function testPropPatch($path, $propPatch) {
		$propPatch->expects($this->once())
			->method('handleRemaining');

		$this->backend->propPatch($path, $propPatch);
	}

	public function propPatchProvider() {
		$propPatchMock = $this->createMock(PropPatch::class);
		return [
			['foo_bar_path_1337', $propPatchMock],
		];
	}

	public function testDelete() {
		$statement = $this->createMock('\Doctrine\DBAL\Driver\Statement');
		$statement->expects($this->at(0))
			->method('execute')
			->with(['dummy_user_42', 'foo_bar_path_1337']);
		$statement->expects($this->at(1))
			->method('closeCursor')
			->with();

		$this->dbConnection->expects($this->at(0))
			->method('prepare')
			->with('DELETE FROM `*PREFIX*properties` WHERE `userid` = ? AND `propertypath` = ?')
			->will($this->returnValue($statement));

		$this->backend->delete('foo_bar_path_1337');
	}

	public function testMove() {
		$statement = $this->createMock('\Doctrine\DBAL\Driver\Statement');
		$statement->expects($this->at(0))
			->method('execute')
			->with(['bar_foo_path_7331', 'dummy_user_42', 'foo_bar_path_1337']);
		$statement->expects($this->at(1))
			->method('closeCursor')
			->with();

		$this->dbConnection->expects($this->at(0))
			->method('prepare')
			->with('UPDATE `*PREFIX*properties` SET `propertypath` = ? WHERE `userid` = ? AND `propertypath` = ?')
			->will($this->returnValue($statement));

		$this->backend->move('foo_bar_path_1337', 'bar_foo_path_7331');
	}
}
