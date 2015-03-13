<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB\Test;


use Icewind\SMB\FileInfo;

class Parser extends \PHPUnit_Framework_TestCase {
	public function modeProvider() {
		return array(
			array('D', FileInfo::MODE_DIRECTORY),
			array('A', FileInfo::MODE_ARCHIVE),
			array('S', FileInfo::MODE_SYSTEM),
			array('H', FileInfo::MODE_HIDDEN),
			array('R', FileInfo::MODE_READONLY),
			array('N', FileInfo::MODE_NORMAL),
			array('RA', FileInfo::MODE_READONLY | FileInfo::MODE_ARCHIVE),
			array('RAH', FileInfo::MODE_READONLY | FileInfo::MODE_ARCHIVE | FileInfo::MODE_HIDDEN)
		);
	}

	/**
	 * @param string $timeZone
	 * @return \Icewind\SMB\TimeZoneProvider
	 */
	private function getTimeZoneProvider($timeZone) {
		$mock = $this->getMockBuilder('\Icewind\SMB\TimeZoneProvider')
			->disableOriginalConstructor()
			->getMock();
		$mock->expects($this->any())
			->method('get')
			->will($this->returnValue($timeZone));
		return $mock;
	}

	/**
	 * @dataProvider modeProvider
	 */
	public function testParseMode($string, $mode) {
		$parser = new \Icewind\SMB\Parser($this->getTimeZoneProvider('UTC'));
		$this->assertEquals($mode, $parser->parseMode($string), 'Failed parsing ' . $string);
	}

	public function statProvider() {
		return array(
			array(
				array(
					'altname: test.txt',
					'create_time:    Sat Oct 12 07:05:58 PM 2013 CEST',
					'access_time:    Tue Oct 15 02:58:48 PM 2013 CEST',
					'write_time:     Sat Oct 12 07:05:58 PM 2013 CEST',
					'change_time:    Sat Oct 12 07:05:58 PM 2013 CEST',
					'attributes:  (80)',
					'stream: [::$DATA], 29634 bytes'
				),
				array(
					'mtime' => strtotime('12 Oct 2013 19:05:58 CEST'),
					'mode' => FileInfo::MODE_NORMAL,
					'size' => 29634
				)
			)
		);
	}

	/**
	 * @dataProvider statProvider
	 */
	public function testStat($output, $stat) {
		$parser = new \Icewind\SMB\Parser($this->getTimeZoneProvider('UTC'));
		$this->assertEquals($stat, $parser->parseStat($output));
	}

	public function dirProvider() {
		return array(
			array(
				array(
					'  .                                   D        0  Tue Aug 26 19:11:56 2014',
					'  ..                                 DR        0  Sun Oct 28 15:24:02 2012',
					'  c.pdf                               N    29634  Sat Oct 12 19:05:58 2013',
					'',
					'                62536 blocks of size 8388608. 57113 blocks available'
				),
				array(
					new FileInfo('/c.pdf', 'c.pdf', 29634, strtotime('12 Oct 2013 19:05:58 CEST'),
						FileInfo::MODE_NORMAL)
				)
			)
		);
	}

	/**
	 * @dataProvider dirProvider
	 */
	public function testDir($output, $dir) {
		$parser = new \Icewind\SMB\Parser($this->getTimeZoneProvider('CEST'));
		$this->assertEquals($dir, $parser->parseDir($output, ''));
	}
}
