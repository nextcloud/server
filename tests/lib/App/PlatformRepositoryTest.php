<?php

/**
 * @author Thomas Müller
 * @copyright 2014 Thomas Müller deepdiver@owncloud.com
 * later.
 * See the COPYING-README file.
 */

namespace Test\App;

use OC;

class PlatformRepositoryTest extends \Test\TestCase {

	/**
	 * @dataProvider providesVersions
	 * @param $expected
	 * @param $input
	 */
	public function testVersion($input, $expected) {
		$pr = new OC\App\PlatformRepository();
		$normalizedVersion = $pr->normalizeVersion($input);
		$this->assertEquals($expected, $normalizedVersion);
	}

	public function providesVersions() {
		return array(
			'none' => array('1.0.0', '1.0.0.0'),
			'none/2' => array('1.2.3.4', '1.2.3.4'),
			'parses state' => array('1.0.0RC1dev', '1.0.0.0-RC1-dev'),
			'CI parsing' => array('1.0.0-rC15-dev', '1.0.0.0-RC15-dev'),
			'delimiters' => array('1.0.0.RC.15-dev', '1.0.0.0-RC15-dev'),
			'RC uppercase' => array('1.0.0-rc1', '1.0.0.0-RC1'),
			'patch replace' => array('1.0.0.pl3-dev', '1.0.0.0-patch3-dev'),
			'forces w.x.y.z' => array('1.0-dev', '1.0.0.0-dev'),
			'forces w.x.y.z/2' => array('0', '0.0.0.0'),
			'parses long' => array('10.4.13-beta', '10.4.13.0-beta'),
			'parses long/2' => array('10.4.13beta2', '10.4.13.0-beta2'),
			'parses long/semver' => array('10.4.13beta.2', '10.4.13.0-beta2'),
			'expand shorthand' => array('10.4.13-b', '10.4.13.0-beta'),
			'expand shorthand2' => array('10.4.13-b5', '10.4.13.0-beta5'),
			'strips leading v' => array('v1.0.0', '1.0.0.0'),
			'strips v/datetime' => array('v20100102', '20100102'),
			'parses dates y-m' => array('2010.01', '2010-01'),
			'parses dates w/ .' => array('2010.01.02', '2010-01-02'),
			'parses dates w/ -' => array('2010-01-02', '2010-01-02'),
			'parses numbers' => array('2010-01-02.5', '2010-01-02-5'),
			'parses dates y.m.Y' => array('2010.1.555', '2010.1.555.0'),
			'parses datetime' => array('20100102-203040', '20100102-203040'),
			'parses dt+number' => array('20100102203040-10', '20100102203040-10'),
			'parses dt+patch' => array('20100102-203040-p1', '20100102-203040-patch1'),
			'parses master' => array('dev-master', '9999999-dev'),
			'parses trunk' => array('dev-trunk', '9999999-dev'),
//			'parses branches' => array('1.x-dev', '1.9999999.9999999.9999999-dev'),
			'parses arbitrary' => array('dev-feature-foo', 'dev-feature-foo'),
			'parses arbitrary2' => array('DEV-FOOBAR', 'dev-FOOBAR'),
			'parses arbitrary3' => array('dev-feature/foo', 'dev-feature/foo'),
			'ignores aliases' => array('dev-master as 1.0.0', '9999999-dev'),
//			'semver metadata' => array('dev-master+foo.bar', '9999999-dev'),
//			'semver metadata/2' => array('1.0.0-beta.5+foo', '1.0.0.0-beta5'),
//			'semver metadata/3' => array('1.0.0+foo', '1.0.0.0'),
//			'metadata w/ alias' => array('1.0.0+foo as 2.0', '1.0.0.0'),
		);
	}}
