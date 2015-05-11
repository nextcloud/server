<?php
/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Appconfig extends \Test\TestCase {
	/** @var \OCP\IAppConfig */
	protected $appConfig;

	/** @var \OCP\IDBConnection */
	protected $connection;

	public function setUp() {
		parent::setUp();

		$this->connection = \OC::$server->getDatabaseConnection();
		$this->registerAppConfig(new \OC\AppConfig(\OC::$server->getDatabaseConnection()));

		$query = $this->connection->prepare('DELETE FROM `*PREFIX*appconfig` WHERE `appid` = ?');
		$query->execute(array('testapp'));
		$query->execute(array('someapp'));
		$query->execute(array('123456'));
		$query->execute(array('anotherapp'));

		$query = $this->connection->prepare('INSERT INTO `*PREFIX*appconfig` VALUES (?, ?, ?)');

		$query->execute(array('testapp', 'enabled', 'true'));
		$query->execute(array('testapp', 'installed_version', '1.2.3'));
		$query->execute(array('testapp', 'depends_on', 'someapp'));
		$query->execute(array('testapp', 'deletethis', 'deletethis'));
		$query->execute(array('testapp', 'key', 'value'));

		$query->execute(array('someapp', 'key', 'value'));
		$query->execute(array('someapp', 'otherkey', 'othervalue'));

		$query->execute(array('123456', 'key', 'value'));
		$query->execute(array('123456', 'enabled', 'false'));

		$query->execute(array('anotherapp', 'key', 'value'));
		$query->execute(array('anotherapp', 'enabled', 'false'));
	}

	public function tearDown() {
		$query = $this->connection->prepare('DELETE FROM `*PREFIX*appconfig` WHERE `appid` = ?');
		$query->execute(array('testapp'));
		$query->execute(array('someapp'));
		$query->execute(array('123456'));
		$query->execute(array('anotherapp'));

		$this->registerAppConfig(new \OC\AppConfig(\OC::$server->getDatabaseConnection()));
		parent::tearDown();
	}

	/**
	 * Register an app config object for testing purposes.
	 *
	 * @param \OCP\IAppConfig $appConfig
	 */
	protected function registerAppConfig($appConfig) {
		\OC::$server->registerService('AppConfig', function ($c) use ($appConfig) {
			return $appConfig;
		});
	}

	public function getAppConfigs() {
		return [
			['\OC_Appconfig'],
			[new \OC\AppConfig(\OC::$server->getDatabaseConnection())],
		];
	}

	/**
	 * @dataProvider getAppConfigs
	 *
	 * @param mixed $callable
	 */
	public function testGetApps($callable) {
		$query = \OC_DB::prepare('SELECT DISTINCT `appid` FROM `*PREFIX*appconfig` ORDER BY `appid`');
		$result = $query->execute();
		$expected = array();
		while ($row = $result->fetchRow()) {
			$expected[] = $row['appid'];
		}
		sort($expected);
		$apps = call_user_func([$callable, 'getApps']);
		$this->assertEquals($expected, $apps);
	}

	/**
	 * @dataProvider getAppConfigs
	 *
	 * @param mixed $callable
	 */
	public function testGetKeys($callable) {
		$query = \OC_DB::prepare('SELECT `configkey` FROM `*PREFIX*appconfig` WHERE `appid` = ?');
		$result = $query->execute(array('testapp'));
		$expected = array();
		while($row = $result->fetchRow()) {
			$expected[] = $row["configkey"];
		}
		sort($expected);
		$keys = call_user_func([$callable, 'getKeys'], 'testapp');
		$this->assertEquals($expected, $keys);
	}

	/**
	 * @dataProvider getAppConfigs
	 *
	 * @param mixed $callable
	 */
	public function testGetValue($callable) {
		$value = call_user_func([$callable, 'getValue'], 'testapp', 'installed_version');
		$this->assertConfigKey('testapp', 'installed_version', $value);

		$value = call_user_func([$callable, 'getValue'], 'testapp', 'nonexistant');
		$this->assertNull($value);

		$value = call_user_func([$callable, 'getValue'], 'testapp', 'nonexistant', 'default');
		$this->assertEquals('default', $value);
	}

	/**
	 * @dataProvider getAppConfigs
	 *
	 * @param mixed $callable
	 */
	public function testHasKey($callable) {
		$value = call_user_func([$callable, 'hasKey'], 'testapp', 'installed_version');
		$this->assertTrue($value);

		$value = call_user_func([$callable, 'hasKey'], 'nonexistant', 'nonexistant');
		$this->assertFalse($value);
	}

	/**
	 * @dataProvider getAppConfigs
	 *
	 * @param mixed $callable
	 */
	public function testSetValue($callable) {
		call_user_func([$callable, 'setValue'], 'testapp', 'installed_version', '1.33.7');
		$this->assertConfigKey('testapp', 'installed_version', '1.33.7');

		call_user_func([$callable, 'setValue'], 'someapp', 'somekey', 'somevalue');
		$this->assertConfigKey('someapp', 'somekey', 'somevalue');
	}

	/**
	 * @dataProvider getAppConfigs
	 *
	 * @param mixed $callable
	 */
	public function testDeleteKey($callable) {
		call_user_func([$callable, 'deleteKey'], 'testapp', 'deletethis');
		$query = \OC_DB::prepare('SELECT `configvalue` FROM `*PREFIX*appconfig` WHERE `appid` = ? AND `configkey` = ?');
		$query->execute(array('testapp', 'deletethis'));
		$result = (bool)$query->fetchRow();
		$this->assertFalse($result);
	}

	/**
	 * @dataProvider getAppConfigs
	 *
	 * @param mixed $callable
	 */
	public function testDeleteApp($callable) {
		call_user_func([$callable, 'deleteApp'], 'someapp');
		$query = \OC_DB::prepare('SELECT `configkey` FROM `*PREFIX*appconfig` WHERE `appid` = ?');
		$query->execute(array('someapp'));
		$result = (bool)$query->fetchRow();
		$this->assertFalse($result);
	}

	/**
	 * @dataProvider getAppConfigs
	 *
	 * @param mixed $callable
	 */
	public function testGetValues($callable) {
		$this->assertFalse(call_user_func([$callable, 'getValues'], 'testapp', 'enabled'));

		$query = \OC_DB::prepare('SELECT `configkey`, `configvalue` FROM `*PREFIX*appconfig` WHERE `appid` = ?');
		$query->execute(array('testapp'));
		$expected = array();
		while ($row = $query->fetchRow()) {
			$expected[$row['configkey']] = $row['configvalue'];
		}
		$values = call_user_func([$callable, 'getValues'], 'testapp', false);
		$this->assertEquals($expected, $values);

		$query = \OC_DB::prepare('SELECT `appid`, `configvalue` FROM `*PREFIX*appconfig` WHERE `configkey` = ?');
		$query->execute(array('enabled'));
		$expected = array();
		while ($row = $query->fetchRow()) {
			$expected[$row['appid']] = $row['configvalue'];
		}
		$values = call_user_func([$callable, 'getValues'], false, 'enabled');
		$this->assertEquals($expected, $values);
	}

	public function testSetValueUnchanged() {
		$statementMock = $this->getMock('\Doctrine\DBAL\Statement', array(), array(), '', false);
		$statementMock->expects($this->once())
			->method('fetch')
			->will($this->returnValue(false));

		$connectionMock = $this->getMock('\OC\DB\Connection', array(), array(), '', false);
		$connectionMock->expects($this->once())
			->method('executeQuery')
			->with($this->equalTo('SELECT `configvalue`, `configkey` FROM `*PREFIX*appconfig`'
				.' WHERE `appid` = ?'), $this->equalTo(array('bar')))
			->will($this->returnValue($statementMock));
		$connectionMock->expects($this->once())
			->method('insertIfNotExist')
			->with($this->equalTo('*PREFIX*appconfig'),
				$this->equalTo(
					array(
						'appid' => 'bar',
						'configkey' => 'foo',
						'configvalue' => 'v1',
					)
				), $this->equalTo(['appid', 'configkey']))
			->willReturn(1);
		$connectionMock->expects($this->never())
			->method('update');

		$appconfig = new OC\AppConfig($connectionMock);
		$appconfig->setValue('bar', 'foo', 'v1');
		$appconfig->setValue('bar', 'foo', 'v1');
		$appconfig->setValue('bar', 'foo', 'v1');
	}

	public function testSetValueUnchanged2() {
		$statementMock = $this->getMock('\Doctrine\DBAL\Statement', array(), array(), '', false);
		$statementMock->expects($this->once())
			->method('fetch')
			->will($this->returnValue(false));

		$connectionMock = $this->getMock('\OC\DB\Connection', array(), array(), '', false);
		$connectionMock->expects($this->once())
			->method('executeQuery')
			->with($this->equalTo('SELECT `configvalue`, `configkey` FROM `*PREFIX*appconfig`'
				.' WHERE `appid` = ?'), $this->equalTo(array('bar')))
			->will($this->returnValue($statementMock));
		$connectionMock->expects($this->once())
			->method('insertIfNotExist')
			->with($this->equalTo('*PREFIX*appconfig'),
				$this->equalTo(
					array(
						'appid' => 'bar',
						'configkey' => 'foo',
						'configvalue' => 'v1',
					)
				), $this->equalTo(['appid', 'configkey']))
			->willReturn(1);
		$connectionMock->expects($this->once())
			->method('update')
			->with($this->equalTo('*PREFIX*appconfig'),
				$this->equalTo(array('configvalue' => 'v2')),
				$this->equalTo(array('appid' => 'bar', 'configkey' => 'foo'))
				);

		$appconfig = new OC\AppConfig($connectionMock);
		$appconfig->setValue('bar', 'foo', 'v1');
		$appconfig->setValue('bar', 'foo', 'v2');
		$appconfig->setValue('bar', 'foo', 'v2');
	}

	public function testSettingConfigParallel() {
		$appConfig1 = new OC\AppConfig(\OC::$server->getDatabaseConnection());
		$appConfig2 = new OC\AppConfig(\OC::$server->getDatabaseConnection());
		$appConfig1->getValue('testapp', 'foo', 'v1');
		$appConfig2->getValue('testapp', 'foo', 'v1');

		$appConfig1->setValue('testapp', 'foo', 'v1');
		$this->assertConfigKey('testapp', 'foo', 'v1');

		$appConfig2->setValue('testapp', 'foo', 'v2');
		$this->assertConfigKey('testapp', 'foo', 'v2');
	}

	/**
	 * @param string $app
	 * @param string $key
	 * @param string $expected
	 * @throws \OC\DatabaseException
	 */
	protected function assertConfigKey($app, $key, $expected) {
		$query = \OC_DB::prepare('SELECT `configvalue` FROM `*PREFIX*appconfig` WHERE `appid` = ? AND `configkey` = ?');
		$result = $query->execute([$app, $key]);
		$actual = $result->fetchRow();
		$this->assertEquals($expected, $actual['configvalue']);
	}
}
