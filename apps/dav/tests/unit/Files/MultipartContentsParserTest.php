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

namespace OCA\DAV\Tests\unit\DAV;

use Test\TestCase;

class MultipartContentsParserTest extends TestCase {
	private $boundrary;

	protected function setUp() {
		parent::setUp();

		$this->boundrary = 'boundary';

	}

	/*TESTS*/

	/**
	 * Test basic gets() functionality, that if passed string instead of resource, it should fail
	 *
	 * @expectedException \Sabre\DAV\Exception\BadRequest
	 * @expectedExceptionMessage Unable to get request content
	 */
	public function testGetsThrowWrongContents() {
		//TODO
		$bodyStream = "I am not a stream, but pretend to be";
		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();
		$request->expects($this->any())
			->method('getBody')
			->willReturn($bodyStream);

		$mcp = new \OCA\DAV\Files\MultipartContentsParser($request);

		$mcp->gets();
	}

	/**
	 * Test function readHeaders(), so if passed empty string, it will return null
	 *
	 */
	public function testReadHeadersThrowEmptyHeader() {
		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();

		$mcp = new \OCA\DAV\Files\MultipartContentsParser($request);
		$mcp->readHeaders('');
		$this->assertEquals(null, $mcp->readHeaders(''));
	}

	/**
	 * streamRead function with incorrect parameter
	 *
	 * @expectedException \Sabre\DAV\Exception\BadRequest
	 * @expectedExceptionMessage Method streamRead cannot read contents with negative length
	 */
	public function testStreamReadToStringThrowNegativeLength() {
		$bodyContent = 'blabla';
		$multipartContentsParser = $this->fillMultipartContentsParserStreamWithBody($bodyContent);
		//give negative length
		$multipartContentsParser->streamReadToString(-1);
	}

	/**
	 * streamRead function with incorrect parameter
	 *
	 * @expectedException \Sabre\DAV\Exception\BadRequest
	 * @expectedExceptionMessage Method streamRead cannot read contents with negative length
	 */
	public function testStreamReadToStreamThrowNegativeLength() {
		$target = fopen('php://temp', 'r+');
		$bodyContent = 'blabla';
		$multipartContentsParser = $this->fillMultipartContentsParserStreamWithBody($bodyContent);
		//give negative length
		$multipartContentsParser->streamReadToStream($target,-1);
	}

	public function testStreamReadToString() {
		$length = 0;
		list($multipartContentsParser, $bodyString) = $this->fillMultipartContentsParserStreamWithChars($length);
		$this->assertEquals($bodyString, $multipartContentsParser->streamReadToString($length));

		$length = 1000;
		list($multipartContentsParser, $bodyString) = $this->fillMultipartContentsParserStreamWithChars($length);
		$this->assertEquals($bodyString, $multipartContentsParser->streamReadToString($length));

		$length = 8192;
		list($multipartContentsParser, $bodyString) = $this->fillMultipartContentsParserStreamWithChars($length);
		$this->assertEquals($bodyString, $multipartContentsParser->streamReadToString($length));

		$length = 20000;
		list($multipartContentsParser, $bodyString) = $this->fillMultipartContentsParserStreamWithChars($length);
		$this->assertEquals($bodyString, $multipartContentsParser->streamReadToString($length));
	}

	public function testStreamReadToStream() {
		$length = 0;
		$this->streamReadToStreamBuilder($length);

		$length = 1000;
		$this->streamReadToStreamBuilder($length);

		$length = 8192;
		$this->streamReadToStreamBuilder($length);

		$length = 20000;
		$this->streamReadToStreamBuilder($length);
	}

	private function streamReadToStreamBuilder($length) {
		$target = fopen('php://temp', 'r+');
		list($multipartContentsParser, $bodyString) = $this->fillMultipartContentsParserStreamWithChars($length);
		$this->assertEquals(true, $multipartContentsParser->streamReadToStream($target,$length));
		rewind($target);
		$this->assertEquals($bodyString, stream_get_contents($target));
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage An error appears while reading and parsing header of content part using fgets
	 */
	public function testGetPartThrowFailfgets() {
		$bodyStream = fopen('php://temp', 'r+');
		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();
		$request->expects($this->any())
			->method('getBody')
			->willReturn($bodyStream);

		$mcp = $this->getMockBuilder('OCA\DAV\Files\MultipartContentsParser')
			->setConstructorArgs(array($request))
			->setMethods(array('gets'))
			->getMock();

		$mcp->expects($this->any())
			->method('gets')
			->will($this->onConsecutiveCalls("--boundary\r\n", "Content-ID: 0\r\n", false));

		$mcp->getPartHeaders($this->boundrary);
	}

	/**
	 * If one one the content parts does not contain boundrary, means that received wrong request
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage Expected boundary delimiter in content part
	 */
	public function testGetPartThrowNoBoundraryFound() {
		// Calling multipletimes getPart on parts without contents should return null,null and signal immedietaly that endDelimiter was reached
		$bodyFull = "--boundary_wrong\r\n--boundary--";
		$multipartContentsParser = $this->fillMultipartContentsParserStreamWithBody($bodyFull);
		$multipartContentsParser->getPartHeaders($this->boundrary);
	}

	/**
	 *  Reading from request which method getBody returns false
	 *
	 * @expectedException \Sabre\DAV\Exception\BadRequest
	 * @expectedExceptionMessage Unable to get request content
	 */
	public function testStreamReadThrowWrongBody() {
		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();
		$request->expects($this->any())
			->method('getBody')
			->willReturn(false);

		$mcp = new \OCA\DAV\Files\MultipartContentsParser($request);
		$mcp->getPartHeaders($this->boundrary);
	}

	/**
	 *  Reading from request which method getBody returns false
	 *
	 */
	public function testMultipartContentSeekToContentLength() {
		$bodyStream = fopen('php://temp', 'r+');
		$bodyString = '';
		$length = 1000;
		for ($x = 0; $x < $length; $x++) {
			$bodyString .= 'k';
		}
		fwrite($bodyStream, $bodyString);
		rewind($bodyStream);
		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();
		$request->expects($this->any())
			->method('getBody')
			->willReturn($bodyStream);

		$mcp = new \OCA\DAV\Files\MultipartContentsParser($request);
		$this->assertEquals(true,$mcp->multipartContentSeekToContentLength($length));
	}

	/**
	 *  Test cases with wrong or incomplete boundraries
	 *
	 */
	public function testGetPartHeadersWrongBoundaryCases() {
		// Calling multipletimes getPart on parts without contents should return null and signal immedietaly that endDelimiter was reached
		$bodyFull = "--boundary\r\n--boundary_wrong\r\n--boundary--";
		$multipartContentsParser = $this->fillMultipartContentsParserStreamWithBody($bodyFull);
		$this->assertEquals(null,$multipartContentsParser->getPartHeaders($this->boundrary));
		$this->assertEquals(true,$multipartContentsParser->getEndDelimiterReached());

		// Test empty content
		$bodyFull = "--boundary\r\n";
		$multipartContentsParser = $this->fillMultipartContentsParserStreamWithBody($bodyFull);
		$this->assertEquals(null, $multipartContentsParser->getPartHeaders($this->boundrary));
		$this->assertEquals(true,$multipartContentsParser->getEndDelimiterReached());

		// Test empty content
		$multipartContentsParser = $this->fillMultipartContentsParserStreamWithBody('');
		$this->assertEquals(null, $multipartContentsParser->getPartHeaders($this->boundrary));
		$this->assertEquals(true,$multipartContentsParser->getEndDelimiterReached());

		// Calling multipletimes getPart on parts without contents should return null and signal immedietaly that endDelimiter was reached
		// endDelimiter should be signaled after first getPart since it will read --boundrary till it finds contents.
		$bodyFull = "--boundary\r\n--boundary\r\n--boundary--";
		$multipartContentsParser = $this->fillMultipartContentsParserStreamWithBody($bodyFull);
		$this->assertEquals(null,$multipartContentsParser->getPartHeaders($this->boundrary));
		$this->assertEquals(true,$multipartContentsParser->getEndDelimiterReached());
		$this->assertEquals(null,$multipartContentsParser->getPartHeaders($this->boundrary));
		$this->assertEquals(true,$multipartContentsParser->getEndDelimiterReached());
		$this->assertEquals(null,$multipartContentsParser->getPartHeaders($this->boundrary));
		$this->assertEquals(true,$multipartContentsParser->getEndDelimiterReached());
		$this->assertEquals(null,$multipartContentsParser->getPartHeaders($this->boundrary));
		$this->assertEquals(true,$multipartContentsParser->getEndDelimiterReached());
	}

	/**
	 *  Test will check if we can correctly parse headers and content using streamReadToString
	 *
	 */
	public function testReadHeaderBodyCorrect() {
		//multipart part will have some content bodyContent and some headers
		$bodyContent = 'blabla';
		$headers['content-length'] = '6';
		$headers['content-type'] = 'text/xml; charset=utf-8';

		//this part will have some arbitrary, correct headers
		$bodyFull = '--boundary'
			."\r\nContent-Type: ".$headers['content-type']
			."\r\nContent-length: ".$headers['content-length']
			."\r\n\r\n"
			."$bodyContent\r\n--boundary--";
		$multipartContentsParser = $this->fillMultipartContentsParserStreamWithBody($bodyFull);

		//parse it
		$headersParsed = $multipartContentsParser->getPartHeaders($this->boundrary);
		$bodyParsed = $multipartContentsParser->streamReadToString(6);

		//check if end delimiter is not reached, since we just read 6 bytes, and stopped at \r\n
		$this->assertEquals(false,$multipartContentsParser->getEndDelimiterReached());

		//check that we parsed correct headers
		$this->assertEquals($bodyContent, $bodyParsed);
		$this->assertEquals($headers, $headersParsed);

		//parse further to check if there is new part. There is no, so headers are null and delimiter reached
		$headersParsed = $multipartContentsParser->getPartHeaders($this->boundrary);
		$this->assertEquals(null,$headersParsed);
		$this->assertEquals(true,$multipartContentsParser->getEndDelimiterReached());
	}

	/**
	 *  Test will check parsing incorrect headers and content using streamReadToString
	 *
	 */
	public function testReadHeaderBodyIncorrect() {

		//multipart part will have some content bodyContent and some headers
		$bodyContent = 'blabla';
		$headers['content-length'] = '6';
		$headers['content-type'] = 'text/xml; charset=utf-8';

		//this part will one correct and one incorrect header
		$bodyFull = '--boundary'
			."\r\nContent-Type: ".$headers['content-type']
			."\r\nContent-length"
			."\r\n\r\n"
			."$bodyContent\r\n--boundary--";
		$multipartContentsParser = $this->fillMultipartContentsParserStreamWithBody($bodyFull);

		//parse it and expect null, since contains incorrect headers
		$headersParsed = $multipartContentsParser->getPartHeaders($this->boundrary);
		$this->assertEquals(null, $headersParsed);
		$this->assertEquals(false,$multipartContentsParser->getEndDelimiterReached());

		//parse further to check if next call with not read headers again
		//this should return null again and get to end of delimiter
		$headersParsed = $multipartContentsParser->getPartHeaders($this->boundrary);
		$this->assertEquals(null,$headersParsed);
		$this->assertEquals(true,$multipartContentsParser->getEndDelimiterReached());
	}

	/**
	 *  Test will check reading error in StreamReadToString
	 *
	 * @expectedException \Sabre\DAV\Exception\BadRequest
	 * @expectedExceptionMessage Method streamRead read 20 expeceted 60
	 */
	public function testReadBodyIncorrect() {
		//multipart part will have some content bodyContent and content-length header will specify to big value
		//this
		$bodyContent = 'blabla';
		$headers['content-length'] = '60';
		$headers['content-type'] = 'text/xml; charset=utf-8';

		//this part will have some arbitrary, correct headers
		$bodyFull = '--boundary'
			."\r\nContent-Type: ".$headers['content-type']
			."\r\nContent-length: ".$headers['content-length']
			."\r\n\r\n"
			."$bodyContent\r\n--boundary--";
		$multipartContentsParser = $this->fillMultipartContentsParserStreamWithBody($bodyFull);

		//parse headers
		$headersParsed = $multipartContentsParser->getPartHeaders($this->boundrary);
		$this->assertEquals($headers, $headersParsed);

		$this->assertEquals(true, array_key_exists('content-length',$headersParsed));
		$multipartContentsParser->streamReadToString($headersParsed['content-length']);
	}

	/**
	 *  Test will check reading error in StreamReadToString return false
	 *
	 */
	public function testReadBodyStreamIncorrect() {
		//multipart part will have some content bodyContent and content-length header will specify to big value
		//this
		$bodyContent = 'blabla';
		$headers['content-length'] = '60';
		$headers['content-type'] = 'text/xml; charset=utf-8';

		//this part will have some arbitrary, correct headers
		$bodyFull = '--boundary'
			."\r\nContent-Type: ".$headers['content-type']
			."\r\nContent-length: ".$headers['content-length']
			."\r\n\r\n"
			."$bodyContent\r\n--boundary--";
		$multipartContentsParser = $this->fillMultipartContentsParserStreamWithBody($bodyFull);

		//parse headers
		$headersParsed = $multipartContentsParser->getPartHeaders($this->boundrary);
		$this->assertEquals($headers, $headersParsed);

		$this->assertEquals(true, array_key_exists('content-length',$headersParsed));
		$target = fopen('php://temp', 'r+');
		$bodyParsed = $multipartContentsParser->streamReadToStream($target, $headersParsed['content-length']);
		$this->assertEquals(false, $bodyParsed);
	}

	/*UTILITIES*/

	private function fillMultipartContentsParserStreamWithChars($length){
		$bodyStream = fopen('php://temp', 'r+');
		$bodyString = '';
		for ($x = 0; $x < $length; $x++) {
			$bodyString .= 'k';
		}
		fwrite($bodyStream, $bodyString);
		rewind($bodyStream);
		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();
		$request->expects($this->any())
			->method('getBody')
			->willReturn($bodyStream);

		$mcp = new \OCA\DAV\Files\MultipartContentsParser($request);
		return array($mcp, $bodyString);
	}

	private function fillMultipartContentsParserStreamWithBody($bodyString){
		$bodyStream = fopen('php://temp', 'r+');
		fwrite($bodyStream, $bodyString);
		rewind($bodyStream);
		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();
		$request->expects($this->any())
			->method('getBody')
			->willReturn($bodyStream);

		$mcp = new \OCA\DAV\Files\MultipartContentsParser($request);
		return $mcp;
	}
}
