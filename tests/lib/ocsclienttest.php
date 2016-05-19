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

namespace Test;

use OC\OCSClient;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\ILogger;

/**
 * Class OCSClientTest
 */
class OCSClientTest extends \Test\TestCase {
	/** @var OCSClient */
	private $ocsClient;
	/** @var IConfig */
	private $config;
	/** @var IClientService */
	private $clientService;
	/** @var ILogger */
	private $logger;

	public function setUp() {
		parent::setUp();

		$this->config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()->getMock();
		$this->clientService = $this->getMock('\OCP\Http\Client\IClientService');
		$this->logger = $this->getMock('\OCP\ILogger');

		$this->ocsClient = new OCSClient(
			$this->clientService,
			$this->config,
			$this->logger
		);
	}

	public function testIsAppStoreEnabledSuccess() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->will($this->returnValue(true));
		$this->assertTrue($this->ocsClient->isAppStoreEnabled());
	}

	public function testIsAppStoreEnabledFail() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->will($this->returnValue(false));
		$this->assertFalse($this->ocsClient->isAppStoreEnabled());
	}

	public function testGetAppStoreUrl() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('appstoreurl', 'https://api.owncloud.com/v1')
			->will($this->returnValue('https://api.owncloud.com/v1'));
		$this->assertSame('https://api.owncloud.com/v1', self::invokePrivate($this->ocsClient, 'getAppStoreUrl'));
	}

	public function testGetCategoriesDisabledAppStore() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->will($this->returnValue(false));
		$this->assertNull($this->ocsClient->getCategories([8, 1, 0, 7]));
	}

	public function testGetCategoriesExceptionClient() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->will($this->returnValue(true));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('appstoreurl', 'https://api.owncloud.com/v1')
			->will($this->returnValue('https://api.owncloud.com/v1'));

		$client = $this->getMock('\OCP\Http\Client\IClient');
		$client
			->expects($this->once())
			->method('get')
			->with(
				'https://api.owncloud.com/v1/content/categories',
				[
					'timeout' => 20,
					'query' => ['version' => '8x1x0x7'],
				]
			)
			->will($this->throwException(new \Exception('TheErrorMessage')));

		$this->clientService
			->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));

		$this->logger
			->expects($this->once())
			->method('error')
			->with(
				'Could not get categories: TheErrorMessage',
				[
					'app' => 'core',
				]
			);

		$this->assertNull($this->ocsClient->getCategories([8, 1, 0, 7]));
	}

	public function testGetCategoriesParseError() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->will($this->returnValue(true));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('appstoreurl', 'https://api.owncloud.com/v1')
			->will($this->returnValue('https://api.owncloud.com/v1'));

		$response = $this->getMock('\OCP\Http\Client\IResponse');
		$response
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue('MyInvalidXml'));

		$client = $this->getMock('\OCP\Http\Client\IClient');
		$client
			->expects($this->once())
			->method('get')
			->with(
				'https://api.owncloud.com/v1/content/categories',
				[
					'timeout' => 20,
					'query' => ['version' => '8x1x0x7'],
				]
			)
			->will($this->returnValue($response));

		$this->clientService
			->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));

		$this->logger
			->expects($this->once())
			->method('error')
			->with(
				'Could not get categories, content was no valid XML',
				[
					'app' => 'core',
				]
			);

		$this->assertNull($this->ocsClient->getCategories([8, 1, 0, 7]));
	}

	public function testGetCategoriesSuccessful() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->will($this->returnValue(true));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('appstoreurl', 'https://api.owncloud.com/v1')
			->will($this->returnValue('https://api.owncloud.com/v1'));

		$response = $this->getMock('\OCP\Http\Client\IResponse');
		$response
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue('<?xml version="1.0"?>
				<ocs>
				 <meta>
				  <status>ok</status>
				  <statuscode>100</statuscode>
				  <message></message>
				  <totalitems>6</totalitems>
				 </meta>
				 <data>
				  <category>
				   <id>920</id>
				   <name>ownCloud Multimedia</name>
				  </category>
				  <category>
				   <id>921</id>
				   <name>ownCloud PIM</name>
				  </category>
				  <category>
				   <id>922</id>
				   <name>ownCloud Productivity</name>
				  </category>
				  <category>
				   <id>923</id>
				   <name>ownCloud Game</name>
				  </category>
				  <category>
				   <id>924</id>
				   <name>ownCloud Tool</name>
				  </category>
				  <category>
				   <id>925</id>
				   <name>ownCloud other</name>
				  </category>
				 </data>
				</ocs>
				'));

		$client = $this->getMock('\OCP\Http\Client\IClient');
		$client
			->expects($this->once())
			->method('get')
			->with(
				'https://api.owncloud.com/v1/content/categories',
				[
					'timeout' => 20,
					'query' => ['version' => '8x1x0x7'],
				]
			)
			->will($this->returnValue($response));

		$this->clientService
			->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));

		$expected = [
			920 => 'ownCloud Multimedia',
			921 => 'ownCloud PIM',
			922 => 'ownCloud Productivity',
			923 => 'ownCloud Game',
			924 => 'ownCloud Tool',
			925 => 'ownCloud other',
		];
		$this->assertSame($expected, $this->ocsClient->getCategories([8, 1, 0, 7]));
	}

	public function testGetApplicationsDisabledAppStore() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->will($this->returnValue(false));
		$this->assertSame([], $this->ocsClient->getApplications([], 1, 'approved', [8, 1, 0, 7]));
	}

	public function testGetApplicationsExceptionClient() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->will($this->returnValue(true));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('appstoreurl', 'https://api.owncloud.com/v1')
			->will($this->returnValue('https://api.owncloud.com/v1'));

		$client = $this->getMock('\OCP\Http\Client\IClient');
		$client
			->expects($this->once())
			->method('get')
			->with(
				'https://api.owncloud.com/v1/content/data',
				[
					'timeout' => 20,
					'query' => [
						'version' => implode('x', [8, 1, 0, 7]),
						'filter' => 'approved',
						'categories' => '815x1337',
						'sortmode' => 'new',
						'page' => 1,
						'pagesize' => 100,
						'approved' => 'approved',
					],
				]
			)
			->will($this->throwException(new \Exception('TheErrorMessage')));

		$this->clientService
			->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));

		$this->logger
			->expects($this->once())
			->method('error')
			->with(
				'Could not get applications: TheErrorMessage',
				[
					'app' => 'core',
				]
			);

		$this->assertSame([], $this->ocsClient->getApplications([815, 1337], 1, 'approved', [8, 1, 0, 7]));
	}

	public function testGetApplicationsParseError() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->will($this->returnValue(true));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('appstoreurl', 'https://api.owncloud.com/v1')
			->will($this->returnValue('https://api.owncloud.com/v1'));

		$response = $this->getMock('\OCP\Http\Client\IResponse');
		$response
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue('MyInvalidXml'));

		$client = $this->getMock('\OCP\Http\Client\IClient');
		$client
			->expects($this->once())
			->method('get')
			->with(
				'https://api.owncloud.com/v1/content/data',
				[
					'timeout' => 20,
					'query' => [
						'version' => implode('x', [8, 1, 0, 7]),
						'filter' => 'approved',
						'categories' => '815x1337',
						'sortmode' => 'new',
						'page' => 1,
						'pagesize' => 100,
						'approved' => 'approved',
					],
				]
			)
			->will($this->returnValue($response));

		$this->clientService
			->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));

		$this->logger
			->expects($this->once())
			->method('error')
			->with(
				'Could not get applications, content was no valid XML',
				[
					'app' => 'core',
				]
			);

		$this->assertSame([], $this->ocsClient->getApplications([815, 1337], 1, 'approved', [8, 1, 0, 7]));
	}

	public function testGetApplicationsSuccessful() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->will($this->returnValue(true));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('appstoreurl', 'https://api.owncloud.com/v1')
			->will($this->returnValue('https://api.owncloud.com/v1'));

		$response = $this->getMock('\OCP\Http\Client\IResponse');
		$response
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue('<?xml version="1.0"?>
				<ocs>
				 <meta>
				  <status>ok</status>
				  <statuscode>100</statuscode>
				  <message></message>
				  <totalitems>2</totalitems>
				  <itemsperpage>100</itemsperpage>
				 </meta>
				 <data>
				  <content details="summary">
				   <id>168707</id>
				   <name>Calendar 8.0</name>
				   <version>0.6.4</version>
				   <label>recommended</label>
				   <changed>2015-02-09T15:23:56+01:00</changed>
				   <created>2015-01-26T04:35:19+01:00</created>
				   <typeid>921</typeid>
				   <typename>ownCloud PIM</typename>
				   <language></language>
				   <personid>owncloud</personid>
				   <profilepage>http://opendesktop.org/usermanager/search.php?username=owncloud</profilepage>
				   <downloads>5393</downloads>
				   <score>60</score>
				   <description>Calendar App for ownCloud</description>
				   <comments>7</comments>
				   <fans>10</fans>
				   <licensetype>16</licensetype>
				   <approved>0</approved>
				   <category>1</category>
				   <license>AGPL</license>
				   <preview1></preview1>
				   <detailpage>https://apps.owncloud.com/content/show.php?content=168707</detailpage>
				   <downloadtype1></downloadtype1>
				   <downloadway1>0</downloadway1>
				   <downloadprice1>0</downloadprice1>
				   <downloadlink1>http://apps.owncloud.com/content/download.php?content=168707&amp;id=1</downloadlink1>
				   <downloadgpgsignature1></downloadgpgsignature1>
				   <downloadgpgfingerprint1></downloadgpgfingerprint1>
				   <downloadpackagename1></downloadpackagename1>
				   <downloadrepository1></downloadrepository1>
				   <downloadname1></downloadname1>
				   <downloadsize1>885</downloadsize1>
				  </content>
				  <content details="summary">
				   <id>168708</id>
				   <name>Contacts 8.0</name>
				   <version>0.3.0.18</version>
				   <label>recommended</label>
				   <changed>2015-02-09T15:18:58+01:00</changed>
				   <created>2015-01-26T04:45:17+01:00</created>
				   <typeid>921</typeid>
				   <typename>ownCloud PIM</typename>
				   <language></language>
				   <personid>owncloud</personid>
				   <profilepage>http://opendesktop.org/usermanager/search.php?username=owncloud</profilepage>
				   <downloads>4237</downloads>
				   <score>58</score>
				   <description></description>
				   <comments>3</comments>
				   <fans>6</fans>
				   <licensetype>16</licensetype>
				   <approved>200</approved>
				   <category>1</category>
				   <license>AGPL</license>
				   <preview1></preview1>
				   <detailpage>https://apps.owncloud.com/content/show.php?content=168708</detailpage>
				   <downloadtype1></downloadtype1>
				   <downloadway1>0</downloadway1>
				   <downloadprice1>0</downloadprice1>
				   <downloadlink1>http://apps.owncloud.com/content/download.php?content=168708&amp;id=1</downloadlink1>
				   <downloadgpgsignature1></downloadgpgsignature1>
				   <downloadgpgfingerprint1></downloadgpgfingerprint1>
				   <downloadpackagename1></downloadpackagename1>
				   <downloadrepository1></downloadrepository1>
				   <downloadname1></downloadname1>
				   <downloadsize1>1409</downloadsize1>
				  </content>
				 </data>
				</ocs> '));

		$client = $this->getMock('\OCP\Http\Client\IClient');
		$client
			->expects($this->once())
			->method('get')
			->with(
				'https://api.owncloud.com/v1/content/data',
				[
					'timeout' => 20,
					'query' => [
						'version' => implode('x', [8, 1, 0, 7]),
						'filter' => 'approved',
						'categories' => '815x1337',
						'sortmode' => 'new',
						'page' => 1,
						'pagesize' => 100,
						'approved' => 'approved',
					],
				]
			)
			->will($this->returnValue($response));

		$this->clientService
			->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));

		$expected = [
			[
				'id' => '168707',
				'name' => 'Calendar 8.0',
				'label' => 'recommended',
				'version' => '0.6.4',
				'type' => '921',
				'typename' => 'ownCloud PIM',
				'personid' => 'owncloud',
				'license' => 'AGPL',
				'detailpage' => 'https://apps.owncloud.com/content/show.php?content=168707',
				'preview' => '',
				'preview-full' => '',
				'changed' => 1423491836,
				'description' => 'Calendar App for ownCloud',
				'score' => '60',
				'downloads' => 5393,
				'level' => 0,
				'profilepage' => 'http://opendesktop.org/usermanager/search.php?username=owncloud',
			],
			[
				'id' => '168708',
				'name' => 'Contacts 8.0',
				'label' => 'recommended',
				'version' => '0.3.0.18',
				'type' => '921',
				'typename' => 'ownCloud PIM',
				'personid' => 'owncloud',
				'license' => 'AGPL',
				'detailpage' => 'https://apps.owncloud.com/content/show.php?content=168708',
				'preview' => '',
				'preview-full' => '',
				'changed' => 1423491538,
				'description' => '',
				'score' => '58',
				'downloads' => 4237,
				'level' => 200,
				'profilepage' => 'http://opendesktop.org/usermanager/search.php?username=owncloud',
			],
		];
		$this->assertEquals($expected, $this->ocsClient->getApplications([815, 1337], 1, 'approved', [8, 1, 0, 7]));
	}

	public function tesGetApplicationDisabledAppStore() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->will($this->returnValue(false));
		$this->assertNull($this->ocsClient->getApplication('MyId', [8, 1, 0, 7]));
	}

	public function testGetApplicationExceptionClient() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->will($this->returnValue(true));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('appstoreurl', 'https://api.owncloud.com/v1')
			->will($this->returnValue('https://api.owncloud.com/v1'));

		$client = $this->getMock('\OCP\Http\Client\IClient');
		$client
			->expects($this->once())
			->method('get')
			->with(
				'https://api.owncloud.com/v1/content/data/MyId',
				[
					'timeout' => 20,
					'query' => ['version' => '8x1x0x7'],
				]
			)
			->will($this->throwException(new \Exception('TheErrorMessage')));

		$this->clientService
			->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));

		$this->logger
			->expects($this->once())
			->method('error')
			->with(
				'Could not get application: TheErrorMessage',
				[
					'app' => 'core',
				]
			);

		$this->assertNull($this->ocsClient->getApplication('MyId', [8, 1, 0, 7]));
	}

	public function testGetApplicationParseError() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->will($this->returnValue(true));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('appstoreurl', 'https://api.owncloud.com/v1')
			->will($this->returnValue('https://api.owncloud.com/v1'));

		$response = $this->getMock('\OCP\Http\Client\IResponse');
		$response
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue('MyInvalidXml'));

		$client = $this->getMock('\OCP\Http\Client\IClient');
		$client
			->expects($this->once())
			->method('get')
			->with(
				'https://api.owncloud.com/v1/content/data/MyId',
				[
					'timeout' => 20,
					'query' => ['version' => '8x1x0x7'],
				]
			)
			->will($this->returnValue($response));

		$this->clientService
			->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));

		$this->logger
			->expects($this->once())
			->method('error')
			->with(
				'Could not get application, content was no valid XML',
				[
					'app' => 'core',
				]
			);

		$this->assertNull($this->ocsClient->getApplication('MyId', [8, 1, 0, 7]));
	}

	public function testGetApplicationSuccessful() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->will($this->returnValue(true));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('appstoreurl', 'https://api.owncloud.com/v1')
			->will($this->returnValue('https://api.owncloud.com/v1'));

		$response = $this->getMock('\OCP\Http\Client\IResponse');
		$response
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue('<?xml version="1.0"?>
				<ocs>
				 <meta>
				  <status>ok</status>
				  <statuscode>100</statuscode>
				  <message></message>
				 </meta>
				 <data>
				  <content details="full">
				   <id>166053</id>
				   <name>Versioning</name>
				   <version>0.0.1</version>
				   <label>recommended</label>
				   <typeid>925</typeid>
				   <typename>ownCloud other</typename>
			   <language></language>
			   <personid>owncloud</personid>
			   <profilepage>http://opendesktop.org/usermanager/search.php?username=owncloud</profilepage>
			   <created>2014-07-07T16:34:40+02:00</created>
			   <changed>2014-07-07T16:34:40+02:00</changed>
			   <downloads>140</downloads>
			   <score>50</score>
			   <description>Placeholder for future updates</description>
			   <summary></summary>
			   <feedbackurl></feedbackurl>
			   <changelog></changelog>
			   <homepage></homepage>
			   <homepagetype></homepagetype>
			   <homepage2></homepage2>
			   <homepagetype2></homepagetype2>
			   <homepage3></homepage3>
			   <homepagetype3></homepagetype3>
			   <homepage4></homepage4>
			   <homepagetype4></homepagetype4>
			   <homepage5></homepage5>
			   <homepagetype5></homepagetype5>
			   <homepage6></homepage6>
			   <homepagetype6></homepagetype6>
			   <homepage7></homepage7>
			   <homepagetype7></homepagetype7>
			   <homepage8></homepage8>
			   <homepagetype8></homepagetype8>
			   <homepage9></homepage9>
			   <homepagetype9></homepagetype9>
			   <homepage10></homepage10>
			   <homepagetype10></homepagetype10>
			   <licensetype>16</licensetype>
			   <license>AGPL</license>
			   <donationpage></donationpage>
			   <comments>0</comments>
			   <commentspage>http://apps.owncloud.com/content/show.php?content=166053</commentspage>
			   <fans>0</fans>
			   <fanspage>http://apps.owncloud.com/content/show.php?action=fan&amp;content=166053</fanspage>
			   <knowledgebaseentries>0</knowledgebaseentries>
			   <knowledgebasepage>http://apps.owncloud.com/content/show.php?action=knowledgebase&amp;content=166053</knowledgebasepage>
			   <depend>ownCloud 7</depend>
			   <preview1></preview1>
			   <preview2></preview2>
			   <preview3></preview3>
			   <previewpic1></previewpic1>
			   <previewpic2></previewpic2>
			   <previewpic3></previewpic3>
			   <picsmall1></picsmall1>
			   <picsmall2></picsmall2>
			   <picsmall3></picsmall3>
			   <detailpage>https://apps.owncloud.com/content/show.php?content=166053</detailpage>
			   <downloadtype1></downloadtype1>
			   <downloadprice1>0</downloadprice1>
			   <downloadlink1>http://apps.owncloud.com/content/download.php?content=166053&amp;id=1</downloadlink1>
			   <downloadname1></downloadname1>
			   <downloadgpgfingerprint1></downloadgpgfingerprint1>
			   <downloadgpgsignature1></downloadgpgsignature1>
			   <downloadpackagename1></downloadpackagename1>
			   <downloadrepository1></downloadrepository1>
			   <downloadsize1>1</downloadsize1>
			   <approved>200</approved>
			  </content>
			 </data>
			</ocs>
			'));

		$client = $this->getMock('\OCP\Http\Client\IClient');
		$client
			->expects($this->once())
			->method('get')
			->with(
				'https://api.owncloud.com/v1/content/data/166053',
				[
					'timeout' => 20,
					'query' => ['version' => '8x1x0x7'],
				]
			)
			->will($this->returnValue($response));

		$this->clientService
			->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));

		$expected = [
			'id' => 166053,
			'name' => 'Versioning',
			'version' => '0.0.1',
			'type' => '925',
			'label' => 'recommended',
			'typename' => 'ownCloud other',
			'personid' => 'owncloud',
			'profilepage' => 'http://opendesktop.org/usermanager/search.php?username=owncloud',
			'detailpage' => 'https://apps.owncloud.com/content/show.php?content=166053',
			'preview1' => '',
			'preview2' => '',
			'preview3' => '',
			'changed' => 1404743680,
			'description' => 'Placeholder for future updates',
			'score' => 50,
			'level' => 200,
		];
		$this->assertSame($expected, $this->ocsClient->getApplication(166053, [8, 1, 0, 7]));
	}

	public function testGetApplicationSuccessfulWithOldId() {
		$this->config
				->expects($this->at(0))
				->method('getSystemValue')
				->with('appstoreenabled', true)
				->will($this->returnValue(true));
		$this->config
				->expects($this->at(1))
				->method('getSystemValue')
				->with('appstoreurl', 'https://api.owncloud.com/v1')
				->will($this->returnValue('https://api.owncloud.com/v1'));

		$response = $this->getMock('\OCP\Http\Client\IResponse');
		$response
				->expects($this->once())
				->method('getBody')
				->will($this->returnValue('<?xml version="1.0"?>
				<ocs>
				 <meta>
				  <status>ok</status>
				  <statuscode>100</statuscode>
				  <message></message>
				 </meta>
				 <data>
				  <content details="full">
				   <id>1337</id>
				   <name>Versioning</name>
				   <version>0.0.1</version>
				   <label>recommended</label>
				   <typeid>925</typeid>
				   <typename>ownCloud other</typename>
			   <language></language>
			   <personid>owncloud</personid>
			   <profilepage>http://opendesktop.org/usermanager/search.php?username=owncloud</profilepage>
			   <created>2014-07-07T16:34:40+02:00</created>
			   <changed>2014-07-07T16:34:40+02:00</changed>
			   <downloads>140</downloads>
			   <score>50</score>
			   <description>Placeholder for future updates</description>
			   <summary></summary>
			   <feedbackurl></feedbackurl>
			   <changelog></changelog>
			   <homepage></homepage>
			   <homepagetype></homepagetype>
			   <homepage2></homepage2>
			   <homepagetype2></homepagetype2>
			   <homepage3></homepage3>
			   <homepagetype3></homepagetype3>
			   <homepage4></homepage4>
			   <homepagetype4></homepagetype4>
			   <homepage5></homepage5>
			   <homepagetype5></homepagetype5>
			   <homepage6></homepage6>
			   <homepagetype6></homepagetype6>
			   <homepage7></homepage7>
			   <homepagetype7></homepagetype7>
			   <homepage8></homepage8>
			   <homepagetype8></homepagetype8>
			   <homepage9></homepage9>
			   <homepagetype9></homepagetype9>
			   <homepage10></homepage10>
			   <homepagetype10></homepagetype10>
			   <licensetype>16</licensetype>
			   <license>AGPL</license>
			   <donationpage></donationpage>
			   <comments>0</comments>
			   <commentspage>http://apps.owncloud.com/content/show.php?content=166053</commentspage>
			   <fans>0</fans>
			   <fanspage>http://apps.owncloud.com/content/show.php?action=fan&amp;content=166053</fanspage>
			   <knowledgebaseentries>0</knowledgebaseentries>
			   <knowledgebasepage>http://apps.owncloud.com/content/show.php?action=knowledgebase&amp;content=166053</knowledgebasepage>
			   <depend>ownCloud 7</depend>
			   <preview1></preview1>
			   <preview2></preview2>
			   <preview3></preview3>
			   <previewpic1></previewpic1>
			   <previewpic2></previewpic2>
			   <previewpic3></previewpic3>
			   <picsmall1></picsmall1>
			   <picsmall2></picsmall2>
			   <picsmall3></picsmall3>
			   <detailpage>https://apps.owncloud.com/content/show.php?content=166053</detailpage>
			   <downloadtype1></downloadtype1>
			   <downloadprice1>0</downloadprice1>
			   <downloadlink1>http://apps.owncloud.com/content/download.php?content=166053&amp;id=1</downloadlink1>
			   <downloadname1></downloadname1>
			   <downloadgpgfingerprint1></downloadgpgfingerprint1>
			   <downloadgpgsignature1></downloadgpgsignature1>
			   <downloadpackagename1></downloadpackagename1>
			   <downloadrepository1></downloadrepository1>
			   <downloadsize1>1</downloadsize1>
			   <approved>200</approved>
			  </content>
			 </data>
			</ocs>
			'));

		$client = $this->getMock('\OCP\Http\Client\IClient');
		$client
				->expects($this->once())
				->method('get')
				->with(
						'https://api.owncloud.com/v1/content/data/166053',
						[
								'timeout' => 20,
								'query' => ['version' => '8x1x0x7'],
						]
				)
				->will($this->returnValue($response));

		$this->clientService
				->expects($this->once())
				->method('newClient')
				->will($this->returnValue($client));

		$expected = [
				'id' => 166053,
				'name' => 'Versioning',
				'version' => '0.0.1',
				'type' => '925',
				'label' => 'recommended',
				'typename' => 'ownCloud other',
				'personid' => 'owncloud',
				'profilepage' => 'http://opendesktop.org/usermanager/search.php?username=owncloud',
				'detailpage' => 'https://apps.owncloud.com/content/show.php?content=166053',
				'preview1' => '',
				'preview2' => '',
				'preview3' => '',
				'changed' => 1404743680,
				'description' => 'Placeholder for future updates',
				'score' => 50,
				'level' => 200,
		];
		$this->assertSame($expected, $this->ocsClient->getApplication(166053, [8, 1, 0, 7]));
	}

	public function testGetApplicationEmptyXml() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->will($this->returnValue(true));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('appstoreurl', 'https://api.owncloud.com/v1')
			->will($this->returnValue('https://api.owncloud.com/v1'));

		$response = $this->getMock('\OCP\Http\Client\IResponse');
		$response
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue('<?xml version="1.0"?>
				<ocs>
				 <meta>
				  <status>ok</status>
				  <statuscode>100</statuscode>
				  <message></message>
				 </meta>
			</ocs>
			'));

		$client = $this->getMock('\OCP\Http\Client\IClient');
		$client
			->expects($this->once())
			->method('get')
			->with(
				'https://api.owncloud.com/v1/content/data/MyId',
				[
					'timeout' => 20,
					'query' => ['version' => '8x1x0x7'],
				]
			)
			->will($this->returnValue($response));

		$this->clientService
			->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));

		$this->assertSame(null, $this->ocsClient->getApplication('MyId', [8, 1, 0, 7]));
	}

	public function testGetApplicationDownloadDisabledAppStore() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->will($this->returnValue(false));
		$this->assertNull($this->ocsClient->getApplicationDownload('MyId', [8, 1, 0, 7]));
	}

	public function testGetApplicationDownloadExceptionClient() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->will($this->returnValue(true));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('appstoreurl', 'https://api.owncloud.com/v1')
			->will($this->returnValue('https://api.owncloud.com/v1'));

		$client = $this->getMock('\OCP\Http\Client\IClient');
		$client
			->expects($this->once())
			->method('get')
			->with(
				'https://api.owncloud.com/v1/content/download/MyId/1',
				[
					'timeout' => 20,
					'query' => ['version' => '8x1x0x7'],
				]
			)
			->will($this->throwException(new \Exception('TheErrorMessage')));

		$this->clientService
			->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));

		$this->logger
			->expects($this->once())
			->method('error')
			->with(
				'Could not get application download URL: TheErrorMessage',
				[
					'app' => 'core',
				]
			);

		$this->assertNull($this->ocsClient->getApplicationDownload('MyId', [8, 1, 0, 7]));
	}

	public function testGetApplicationDownloadParseError() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->will($this->returnValue(true));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('appstoreurl', 'https://api.owncloud.com/v1')
			->will($this->returnValue('https://api.owncloud.com/v1'));

		$response = $this->getMock('\OCP\Http\Client\IResponse');
		$response
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue('MyInvalidXml'));

		$client = $this->getMock('\OCP\Http\Client\IClient');
		$client
			->expects($this->once())
			->method('get')
			->with(
				'https://api.owncloud.com/v1/content/download/MyId/1',
				[
					'timeout' => 20,
					'query' => ['version' => '8x1x0x7'],
				]
			)
			->will($this->returnValue($response));

		$this->clientService
			->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));

		$this->logger
			->expects($this->once())
			->method('error')
			->with(
				'Could not get application download URL, content was no valid XML',
				[
					'app' => 'core',
				]
			);

		$this->assertNull($this->ocsClient->getApplicationDownload('MyId', [8, 1, 0, 7]));
	}

	public function testGetApplicationDownloadUrlSuccessful() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->will($this->returnValue(true));
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('appstoreurl', 'https://api.owncloud.com/v1')
			->will($this->returnValue('https://api.owncloud.com/v1'));

		$response = $this->getMock('\OCP\Http\Client\IResponse');
		$response
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue('<?xml version="1.0"?>
				<ocs>
				 <meta>
				  <status>ok</status>
				  <statuscode>100</statuscode>
				  <message></message>
				 </meta>
				 <data>
				  <content details="download">
				   <downloadlink>https://apps.owncloud.com/CONTENT/content-files/166052-files_trashbin.zip</downloadlink>
				   <mimetype>application/zip</mimetype>
				   <gpgfingerprint></gpgfingerprint>
				   <gpgsignature></gpgsignature>
				   <packagename></packagename>
				   <repository></repository>
				  </content>
				 </data>
				</ocs>
				'));

		$client = $this->getMock('\OCP\Http\Client\IClient');
		$client
			->expects($this->once())
			->method('get')
			->with(
				'https://api.owncloud.com/v1/content/download/MyId/1',
				[
					'timeout' => 20,
					'query' => ['version' => '8x1x0x7'],
				]
			)
			->will($this->returnValue($response));

		$this->clientService
			->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));

		$expected = [
			'downloadlink' => 'https://apps.owncloud.com/CONTENT/content-files/166052-files_trashbin.zip',
		];
		$this->assertSame($expected, $this->ocsClient->getApplicationDownload('MyId', [8, 1, 0, 7]));
	}
}
