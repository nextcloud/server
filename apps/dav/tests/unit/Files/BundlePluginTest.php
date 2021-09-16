<?php
/**
 * @author Piotr Mrowczynski <Piotr.Mrowczynski@owncloud.com>
 * @author Louis Chemineau <louis@chmn.me>
 *
 * @copyright Copyright (c) 2016, ownCloud GmbH.
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
namespace OCA\DAV\Files;

use OC\Files\FileInfo;
use OC\Files\Storage\Local;
use Sabre\HTTP\RequestInterface;
use Test\TestCase;
use OC\Files\View;
use OCP\Files\Storage;
use Sabre\DAV\Exception;
use OC\Files\Filesystem;
use OCP\Files\StorageNotAvailableException;

/**
 * Class BundlingPlugin
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\unit\Files
 */
class BundlingPluginTest extends TestCase {

	/**
	 * @var string
	 */
	private $user;

	/** @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject */
	private $view;

	/** @var \OC\Files\FileInfo | \PHPUnit_Framework_MockObject_MockObject */
	private $info;

	/**
	 * @var \Sabre\DAV\Server | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $server;

	/**
	 * @var FilesPlugin
	 */
	private $plugin;

	/**
	 * @var \Sabre\HTTP\RequestInterface | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $request;
	/**
	 * @var \Sabre\HTTP\ResponseInterface | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $response;

	/**
	 * @var MultipartContentsParser | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $contentHandler;

	const BOUNDRARY = 'test_boundrary';

	public function setUp() {
		parent::setUp();
//		$this->server = new \Sabre\DAV\Server();

		$this->server = $this->getMockBuilder('\Sabre\DAV\Server')
			->setConstructorArgs(array())
			->setMethods(array('emit'))
			->getMock();

		$this->server->tree = $this->getMockBuilder('\Sabre\DAV\Tree')
			->disableOriginalConstructor()
			->getMock();

		// setup
		$storage = $this->getMockBuilder(Local::class)
			->setMethods(["fopen","moveFromStorage","file_exists"])
			->setConstructorArgs([['datadir' => \OC::$server->getTempManager()->getTemporaryFolder()]])
			->getMock();
		$storage->method('fopen')
			->will($this->returnCallback(
				function ($path,$mode) {
					$bodyStream = fopen('php://temp', 'r+');
					return $bodyStream;
				}
			));
		$storage->method('moveFromStorage')
			->will($this->returnValue(true));
		$storage->method('file_exists')
			->will($this->returnValue(true));

		\OC_Hook::clear();

		$this->user = $this->getUniqueID('user_');
		$userManager = \OC::$server->getUserManager();
		$userManager->createUser($this->user, 'pass');

		$this->loginAsUser($this->user);

		Filesystem::mount($storage, [], $this->user . '/');

		$this->view = $this->getMockBuilder(View::class)
			->setMethods(['resolvePath', 'touch', 'file_exists', 'getFileInfo'])
			->setConstructorArgs([])
			->getMock();

		$this->view->method('touch')
			->will($this->returnValue(true));

		$this->view
			->method('resolvePath')
			->will($this->returnCallback(
				function ($path) use ($storage) {
					return [$storage, $path];
				}
			));

		$this->view
			->method('getFileInfo')
			->will($this->returnCallback(
				function ($path) {
					$props = array();
					$props['checksum'] = null;
					$props['etag'] = $path;
					$props['fileid'] = $path;
					$info = new FileInfo($path, null, null, $props, null);
					return $info;
				}
			));

		$this->info = $this->createMock('OC\Files\FileInfo', [], [], '', false);

		$this->request = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();

		$this->response = new \Sabre\HTTP\Response();

		$this->plugin = new BundlingPlugin(
			$this->view
		);

		$this->plugin->initialize($this->server);
	}

	/*TESTS*/

	/**
	 * This test checks that if url endpoint is wrong, plugin with return exception
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage URL endpoint has to be instance of \OCA\DAV\Files\FilesHome
	 */
	public function testHandleBundleNotHomeCollection() {

		$this->request
			->expects($this->once())
			->method('getPath')
			->will($this->returnValue('notFilesHome.xml'));

		$node = $this->getMockBuilder('\OCA\DAV\Connector\Sabre\File')
			->disableOriginalConstructor()
			->getMock();

		$this->server->tree->expects($this->once())
			->method('getNodeForPath')
			->with('notFilesHome.xml')
			->will($this->returnValue($node));

		$this->plugin->handleBundle($this->request, $this->response);
	}

	/**
	 * Simulate NULL request header
	 *
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage Content-Type header is needed
	 */
	public function testHandleBundleNoHeader() {
		$this->setupServerTillFilesHome();

		$this->request
			->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->will($this->returnValue(null));

		$this->plugin->handleBundle($this->request, $this->response);
	}

	/**
	 * Simulate empty request header
	 *
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage Content-Type header must not be empty
	 */
	public function testHandleBundleEmptyHeader() {
		$this->setupServerTillFilesHome();

		$this->request
			->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->will($this->returnValue(""));

		$this->plugin->handleBundle($this->request, $this->response);
	}

	/**
	 * Simulate content-type header without boundrary specification request header
	 *
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage Improper Content-type format. Boundary may be missing
	 */
	public function testHandleBundleNoBoundraryHeader() {
		$this->setupServerTillFilesHome();

		$this->request
			->expects($this->atLeastOnce())
			->method('getHeader')
			->with('Content-Type')
			->will($this->returnValue("multipart/related"));

		$this->plugin->handleBundle($this->request, $this->response);
	}

	/**
	 * Simulate content-type header with wrong boundrary specification request header
	 *
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage Boundary is not set
	 */
	public function testHandleBundleWrongBoundraryHeader() {
		$this->setupServerTillFilesHome();

		$this->request
			->expects($this->atLeastOnce())
			->method('getHeader')
			->with('Content-Type')
			->will($this->returnValue("multipart/related;thisIsNotBoundrary"));

		$this->plugin->handleBundle($this->request, $this->response);
	}

	/**
	 * Simulate content-type header with wrong boundrary specification request header
	 *
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage Content-Type must be multipart/related
	 */
	public function testHandleBundleWrongContentTypeHeader() {
		$this->setupServerTillFilesHome();

		$this->request
			->expects($this->atLeastOnce())
			->method('getHeader')
			->with('Content-Type')
			->will($this->returnValue("multipart/mixed; boundary=".self::BOUNDRARY));

		$this->plugin->handleBundle($this->request, $this->response);
	}

	/**
	 * Simulate content-type header with alternative correct boundrary specification request header
	 *
	 * Request with user out of quota
	 *
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage beforeWriteBundle preconditions failed
	 */
	public function testHandleAlternativeBoundraryPlusBundleOutOfQuota() {
		$this->setupServerTillFilesHome();

		$this->request
			->expects($this->atLeastOnce())
			->method('getHeader')
			->with('Content-Type')
			->will($this->returnValue("multipart/related; boundary=\"".self::BOUNDRARY."\""));

		$this->server
			->expects($this->once())
			->method('emit')
			->will($this->returnValue(false));

		$this->plugin->handleBundle($this->request, $this->response);
	}

	/**
	 * Request without request body
	 *
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage Unable to get request content
	 */
	public function testHandleBundleWithNullBody() {
		$this->setupServerTillHeader();

		$this->plugin->handleBundle($this->request, $this->response);
	}

	/**
	 * Test empty request body. This will pass getPartHeader, but exception will be raised after we ready headers
	 *
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage Incorrect Content-type format. Charset might be missing
	 */
	public function testHandleBundleWithEmptyBody() {
		$this->setupServerTillHeader();

		$this->fillMultipartContentsParserStreamWithBody("");

		$this->plugin->handleBundle($this->request, $this->response);
	}

	/**
	 * Test wrong request body
	 *
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage Expected boundary delimiter in content part - this is not a multipart request
	 */
	public function testHandleBundleWithWrongBody() {
		$this->setupServerTillHeader();

		$this->fillMultipartContentsParserStreamWithBody("WrongBody");

		$this->plugin->handleBundle($this->request, $this->response);
	}

	/**
	 * Test wrong request body, with metadata header containing no charset
	 *
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage Incorrect Content-type format. Charset might be missing
	 */
	public function testHandleMetadataNoCharsetType(){
		$bodyContent = 'I am wrong metadata not in utf-8';
		$headers['content-length'] = strlen($bodyContent);
		$headers['content-type'] = 'text/xml';

		//this part will have some arbitrary, correct headers
		$bodyFull = "--".self::BOUNDRARY
			."\r\nContent-Type: ".$headers['content-type']
			."\r\n\r\n"
			."$bodyContent\r\n--".self::BOUNDRARY."--";

		$this->setupServerTillHeader();

		$this->fillMultipartContentsParserStreamWithBody($bodyFull);

		$this->plugin->handleBundle($this->request, $this->response);
	}

	/**
	 * Test wrong request body, with metadata header containing wrong content-type
	 *
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage Content-Type must be text/xml
	 */
	public function testHandleMetadataWrongContentType(){
		$bodyContent = 'I am wrong metadata content type';
		$headers['content-type'] = 'text/plain; charset=utf-8';

		//this part will have some arbitrary, correct headers
		$bodyFull = "--".self::BOUNDRARY
			."\r\nContent-Type: ".$headers['content-type']
			."\r\n\r\n"
			."$bodyContent\r\n--".self::BOUNDRARY."--";

		$this->setupServerTillHeader();

		$this->fillMultipartContentsParserStreamWithBody($bodyFull);

		$this->plugin->handleBundle($this->request, $this->response);
	}

	/**
	 * Test wrong request body, with metadata header containing wrong content-type
	 *
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage Bundle metadata header does not contain Content-Length. Unable to parse whole bundle request
	 */
	public function testHandleMetadataNoContentLength(){
		$bodyContent = 'I am wrong metadata content type';
		//$headers['content-length'] = strlen($bodyContent);
		$headers['content-type'] = 'text/xml; charset=utf-8';

		//this part will have some arbitrary, correct headers
		$bodyFull = "--".self::BOUNDRARY
			."\r\nContent-Type: ".$headers['content-type']
			//."\r\nContent-length: ".$headers['content-length']
			."\r\n\r\n"
			."$bodyContent\r\n--".self::BOUNDRARY."--";

		$this->setupServerTillHeader();

		$this->fillMultipartContentsParserStreamWithBody($bodyFull);

		$this->plugin->handleBundle($this->request, $this->response);
	}

	/**
	 * Try to parse body which is not xml
	 *
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage Bundle metadata contains incorrect xml structure. Unable to parse whole bundle request
	 */
	public function testHandleWrongMetadataNoXML(){
		$bodyContent = "I am not xml";

		$this->setupServerTillMetadata($bodyContent);

		$this->plugin->handleBundle($this->request, $this->response);
	}

	/**
	 * Try to parse body which has xml d:multipart element which
	 * has not been declared <d:multipart xmlns:d='DAV:'>
	 *
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage Bundle metadata does not contain d:multipart children elements
	 */
	public function testHandleWrongMetadataWrongXMLdElement(){
		$bodyContent = "<?xml version='1.0' encoding='UTF-8'?><d:multipart></d:multipart>";

		$this->setupServerTillMetadata($bodyContent);

		$this->plugin->handleBundle($this->request, $this->response);
	}

	/**
	 * This test checks that exception is raised for
	 * parsed XML which contains empty(without d:part elements) d:multipart section in metadata XML
	 *
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage Bundle metadata does not contain d:multipart/d:part/d:prop children elements
	 */
	public function testHandleEmptyMultipartMetadataSection(){
		$bodyContent = "<?xml version='1.0' encoding='UTF-8'?><d:multipart xmlns:d='DAV:'></d:multipart>";

		$this->setupServerTillMetadata($bodyContent);

		$this->plugin->handleBundle($this->request, $this->response);
	}

	/**
	 * Metadata contains part properties not containing obligatory field will raise exception
	 *
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage Undefined index: oc-id
	 */
	public function testHandleWrongMetadataNoPartID(){
		$bodyContent = "<?xml version='1.0' encoding='UTF-8'?>
			<d:multipart xmlns:d='DAV:'>
			<d:part>
				<d:prop>
				</d:prop>
			</d:part>
			</d:multipart>";

		$this->setupServerTillMetadata($bodyContent);

		$this->plugin->handleBundle($this->request, $this->response);
	}

	/**
	 * In the request, insert two files with the same Content-ID
	 *
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage One or more files have the same Content-ID 1. Unable to parse whole bundle request
	 */
	public function testHandleWrongMetadataMultipleIDs(){
		$bodyContent = "<?xml version='1.0' encoding='UTF-8'?>
			<d:multipart xmlns:d='DAV:'>
			<d:part>
				<d:prop>
					<d:oc-path>/test/zombie1.jpg</d:oc-path>\n
					<d:oc-mtime>1476393386</d:oc-mtime>\n
					<d:oc-id>1</d:oc-id>\n
					<d:oc-total-length>6</d:oc-total-length>\n
				</d:prop>
			</d:part>
			<d:part>
				<d:prop>
					<d:oc-path>/test/zombie2.jpg</d:oc-path>\n
					<d:oc-mtime>1476393386</d:oc-mtime>\n
					<d:oc-id>1</d:oc-id>\n
					<d:oc-total-length>6</d:oc-total-length>\n
				</d:prop>
			</d:part>
			</d:multipart>";

		$this->setupServerTillMetadata($bodyContent);

		$this->plugin->handleBundle($this->request, $this->response);
	}

	/**
	 * Specify metadata part without corresponding binary content
	 *
	 */
	public function testHandleWithoutBinaryContent(){
		$bodyContent = "<?xml version='1.0' encoding='UTF-8'?>
			<d:multipart xmlns:d='DAV:'>
			<d:part>
				<d:prop>
					<d:oc-path>/test/zombie1.jpg</d:oc-path>\n
					<d:oc-mtime>1476393386</d:oc-mtime>\n
					<d:oc-id>1</d:oc-id>\n
					<d:oc-total-length>6</d:oc-total-length>\n
				</d:prop>
			</d:part>
			</d:multipart>";

		$this->setupServerTillMetadata($bodyContent);
		$this->plugin->handleBundle($this->request, $this->response);
		$return = $this->response->getBody();
		$this->assertTrue(false != $return);
		$xml = simplexml_load_string($return);
		$this->assertTrue(false != $xml);
		$xml->registerXPathNamespace('d','urn:DAV');
		$xml->registerXPathNamespace('s','http://sabredav.org/ns');

		$this->assertEquals(1, count($xml->xpath('/d:multistatus')));

		$fileMetadataObjectXML = $xml->xpath('/d:multistatus/d:response/d:propstat/d:status');
		$this->assertTrue(false != $fileMetadataObjectXML);
		$this->assertEquals(1, count($fileMetadataObjectXML));
		$this->assertEquals("HTTP/1.1 400 Bad Request", (string) $fileMetadataObjectXML[0]);

		$fileMetadataObjectXML = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:error/s:message');
		$this->assertTrue(false != $fileMetadataObjectXML);
		$this->assertEquals(1, count($fileMetadataObjectXML));
		$this->assertEquals("File parsing error", (string) $fileMetadataObjectXML[0]);
	}

	/**
	 * This test will simulate success and failure in putFile class.
	 *
	 */
	public function testHandlePutFile(){
		$this->setupServerTillData();

		$this->view
			->method('file_exists')
			->will($this->onConsecutiveCalls(true, false, $this->throwException(new StorageNotAvailableException())));

		$this->plugin->handleBundle($this->request, $this->response);

		$return = $this->response->getBody();
		$this->assertTrue(false != $return);
		$xml = simplexml_load_string($return);
		$this->assertTrue(false != $xml);
		$xml->registerXPathNamespace('d','urn:DAV');
		$xml->registerXPathNamespace('s','http://sabredav.org/ns');

		$this->assertEquals(1, count($xml->xpath('/d:multistatus')));

		$fileMetadataObjectXML = $xml->xpath('/d:multistatus/d:response/d:propstat/d:status');
		$this->assertTrue(false != $fileMetadataObjectXML);
		$this->assertEquals(3, count($fileMetadataObjectXML));
		$this->assertEquals("HTTP/1.1 400 Bad Request", (string) $fileMetadataObjectXML[0]);
		$this->assertEquals("HTTP/1.1 200 OK", (string) $fileMetadataObjectXML[1]);
		$this->assertEquals("HTTP/1.1 400 Bad Request", (string) $fileMetadataObjectXML[2]);

		$fileMetadataObjectXML = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:error/s:message');
		$this->assertTrue(false != $fileMetadataObjectXML);
		$this->assertEquals(2, count($fileMetadataObjectXML));
		$this->assertEquals("Bundling not supported for already existing files", (string) $fileMetadataObjectXML[0]);
		$this->assertEquals("StorageNotAvailableException raised", (string) $fileMetadataObjectXML[1]);
	}

	/*UTILITIES*/

	private function setupServerTillData(){
		$bodyContent = "<?xml version='1.0' encoding='UTF-8'?>
			<d:multipart xmlns:d='DAV:'>
			<d:part>
				<d:prop>
					<d:oc-path>/test/zombie1.jpg</d:oc-path>\n
					<d:oc-mtime>1476393386</d:oc-mtime>\n
					<d:oc-id>0</d:oc-id>\n
					<d:oc-total-length>7</d:oc-total-length>\n
				</d:prop>
			</d:part>
			<d:part>
				<d:prop>
					<d:oc-path>/test/zombie2.jpg</d:oc-path>\n
					<d:oc-mtime>1476393386</d:oc-mtime>\n
					<d:oc-id>1</d:oc-id>\n
					<d:oc-total-length>7</d:oc-total-length>\n
				</d:prop>
			</d:part>
			<d:part>
				<d:prop>
					<d:oc-path>zombie3.jpg</d:oc-path>\n
					<d:oc-mtime>1476393232</d:oc-mtime>\n
					<d:oc-id>2</d:oc-id>\n
					<d:oc-total-length>7</d:oc-total-length>\n
				</d:prop>
			</d:part>
			</d:multipart>";

		$headers['content-length'] = strlen($bodyContent);
		$headers['content-type'] = 'text/xml; charset=utf-8';

		//this part will have some arbitrary, correct headers
		$bodyFull = "--".self::BOUNDRARY
			."\r\nContent-Type: ".$headers['content-type']
			."\r\nContent-length: ".$headers['content-length']
			."\r\n\r\n"
			."$bodyContent"
			."\r\n--".self::BOUNDRARY
			."\r\nContent-ID: 0"
			."\r\n\r\n"
			."zombie1"
			."\r\n--".self::BOUNDRARY
			."\r\nContent-ID: 1"
			."\r\n\r\n"
			."zombie2"
			."\r\n--".self::BOUNDRARY
			."\r\nContent-ID: 2"
			."\r\n\r\n"
			."zombie3"
			."\r\n--".self::BOUNDRARY."--";

		$this->setupServerTillHeader();

		$this->fillMultipartContentsParserStreamWithBody($bodyFull);
	}

	private function setupServerTillMetadata($bodyContent){
		$headers['content-length'] = strlen($bodyContent);
		$headers['content-type'] = 'text/xml; charset=utf-8';

		//this part will have some arbitrary, correct headers
		$bodyFull = "--".self::BOUNDRARY
			."\r\nContent-Type: ".$headers['content-type']
			."\r\nContent-length: ".$headers['content-length']
			."\r\n\r\n"
			."$bodyContent\r\n--".self::BOUNDRARY."--";

		$this->setupServerTillHeader();

		$this->fillMultipartContentsParserStreamWithBody($bodyFull);
	}

	private function setupServerTillHeader(){
		$this->setupServerTillFilesHome();

		$this->request
			->expects($this->atLeastOnce())
			->method('getHeader')
			->with('Content-Type')
			->will($this->returnValue("multipart/related; boundary=".self::BOUNDRARY));

		$this->server
			->expects($this->once())
			->method('emit')
			->will($this->returnValue(true));
	}

	private function setupServerTillFilesHome(){
		$this->request
			->expects($this->once())
			->method('getPath')
			->will($this->returnValue('files/admin'));

		$node = $this->getMockBuilder('\OCA\DAV\Files\FilesHome')
			->disableOriginalConstructor()
			->getMock();

		$this->server->tree->expects($this->once())
			->method('getNodeForPath')
			->with('files/admin')
			->will($this->returnValue($node));
	}

	private function fillMultipartContentsParserStreamWithBody($bodyString){
		$bodyStream = fopen('php://temp', 'r+');
		fwrite($bodyStream, $bodyString);
		rewind($bodyStream);

		$this->request->expects($this->any())
			->method('getBody')
			->willReturn($bodyStream);
	}

	public function tearDown() {
		$userManager = \OC::$server->getUserManager();
		$userManager->get($this->user)->delete();
		unset($_SERVER['HTTP_OC_CHUNKED']);

		parent::tearDown();
	}
}
