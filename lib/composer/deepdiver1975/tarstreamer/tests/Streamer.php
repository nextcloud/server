<?php

namespace ownCloud\TarStreamer\Tests;

use Archive_Tar;
use ownCloud\TarStreamer\TarStreamer;
use PHPUnit\Framework\TestCase;

class Streamer extends TestCase
{
	/** @var string */
	private $archive;

	/** @var TarStreamer */
	private $streamer;

	public function setUp()
	{
		$this->archive = tempnam('/tmp', 'tar');
		$this->streamer = new TarStreamer(
			['outstream' => fopen($this->archive, 'w')]
		);
	}
	
	public function tearDown()
	{
		unlink($this->archive);
	}

	/**
	 * @dataProvider providesNameAndData
	 * @param $fileName
	 * @param $data
	 */
	public function testSimpleFile($fileName, $data)
	{
		$dataStream = fopen('data://text/plain,' . $data, 'r');
		$ret = $this->streamer->addFileFromStream($dataStream, $fileName, 10);
		$this->assertTrue($ret);

		$this->streamer->finalize();

		$this->assertFileInTar($fileName);
	}

	/**
	 * @dataProvider providesNameAndData
	 * @param $fileName
	 * @param $data
	 */
	public function testAddingNoResource($fileName, $data)
	{
		$ret = $this->streamer->addFileFromStream($data, $fileName, 10);
		$this->assertFalse($ret);

		$this->streamer->finalize();

		$this->assertFileNotInTar($fileName);
	}

	public function testDir()
	{
		$folderName = 'foo-folder';
		$this->streamer->addEmptyDir($folderName);

		$this->streamer->finalize();

		$this->assertFolderInTar($folderName);
	}

	public function providesNameAndData()
	{
		return [
			['foo.bar', '1234567890'],
			['foobar1234foobar1234foobar1234foobar1234foobar1234foobar1234foobar1234foobar1234foobar1234foobar1234.txt', 'abcdefghij']
		];
	}

	/**
	 * @return array array(filename, mimetype), expectedMimetype, expectedFilename, $description, $browser
	 */
	public function providerSendHeadersOK() {
		return array(
			// Regular browsers
				array(
						array(),
						'application/x-tar',
						'archive.tar',
						'default headers',
						'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36',
						'Content-Disposition: attachment; filename*=UTF-8\'\'archive.tar; filename="archive.tar"',
				),
				array(
						array(
								'file.tar',
								'application/octet-stream',
						),
						'application/octet-stream',
						'file.tar',
						'specific headers',
						'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36',
						'Content-Disposition: attachment; filename*=UTF-8\'\'file.tar; filename="file.tar"',
				),
			// Internet Explorer
				array(
						array(),
						'application/x-tar',
						'archive.tar',
						'default headers',
						'Mozilla/5.0 (compatible, MSIE 11, Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko',
						'Content-Disposition: attachment; filename="archive.tar"',
				),
				array(
						array(
								'file.tar',
								'application/octet-stream',
						),
						'application/octet-stream',
						'file.tar',
						'specific headers',
						'Mozilla/5.0 (compatible, MSIE 11, Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko',
						'Content-Disposition: attachment; filename="file.tar"',
				),
		);
	}

	/**
	 * @dataProvider providerSendHeadersOK
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 *
	 * @param array $arguments
	 * @param string $expectedMimetype
	 * @param string $expectedFilename
	 * @param string $description
	 * @param string $browser
	 * @param string $expectedDisposition
	 */
	public function testSendHeadersOKWithRegularBrowser(array $arguments,
														$expectedMimetype,
														$expectedFilename,
														$description,
														$browser,
														$expectedDisposition) {
		$_SERVER['HTTP_USER_AGENT'] = $browser;
		call_user_func_array(array($this->streamer, "sendHeaders"), $arguments);
		$headers = xdebug_get_headers();
		$this->assertContains('Pragma: public', $headers);
		$this->assertContains('Expires: 0', $headers);
		$this->assertContains('Accept-Ranges: bytes', $headers);
		$this->assertContains('Connection: Keep-Alive', $headers);
		$this->assertContains('Content-Transfer-Encoding: binary', $headers);
		$this->assertContains('Content-Type: ' . $expectedMimetype, $headers);
		$this->assertContains($expectedDisposition, $headers);
	}

	private function assertFileInTar($file)
	{
		$elem = $this->getElementFromTar($file);
		$this->assertNotNull($elem);
		$this->assertEquals('0', $elem['typeflag']);
	}

	private function assertFileNotInTar($file)
	{
		$arc = new Archive_Tar($this->archive);
		$content = $arc->extractInString($file);
		$this->assertNull($content);
	}

	private function assertFolderInTar($folderName)
	{
		$elem = $this->getElementFromTar($folderName . '/');
		$this->assertNotNull($elem);
		$this->assertEquals('5', $elem['typeflag']);
	}

	/**
	 * @param $folderName
	 * @return array
	 */
	private function getElementFromTar($folderName)
	{
		$arc = new Archive_Tar($this->archive);
		$list = $arc->listContent();
		if (!is_array($list)){
			$list = [];
		}
		$elem = array_filter($list, function ($element) use ($folderName) {
			return $element['filename'] == $folderName;
		});
		return isset($elem[0]) ? $elem[0] : null;
	}
}
