<?php

/**
 * Copyright (c) 2014 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Connector\Sabre;

use OC\Files\FileInfo;
use OC\Files\View;

class Node extends \Test\TestCase {
	public function davPermissionsProvider() {
		return array(
			array(\OCP\PERMISSION_ALL, 'file', false, false, 'RDNVW'),
			array(\OCP\PERMISSION_ALL, 'dir', false, false, 'RDNVCK'),
			array(\OCP\PERMISSION_ALL, 'file', true, false, 'SRDNVW'),
			array(\OCP\PERMISSION_ALL, 'file', true, true, 'SRMDNVW'),
			array(\OCP\PERMISSION_ALL - \OCP\PERMISSION_SHARE, 'file', true, false, 'SDNVW'),
			array(\OCP\PERMISSION_ALL - \OCP\PERMISSION_UPDATE, 'file', false, false, 'RDNV'),
			array(\OCP\PERMISSION_ALL - \OCP\PERMISSION_DELETE, 'file', false, false, 'RW'),
			array(\OCP\PERMISSION_ALL - \OCP\PERMISSION_CREATE, 'file', false, false, 'RDNVW'),
			array(\OCP\PERMISSION_ALL - \OCP\PERMISSION_CREATE, 'dir', false, false, 'RDNV'),
		);
	}

	/**
	 * @dataProvider davPermissionsProvider
	 */
	public function testDavPermissions($permissions, $type, $shared, $mounted, $expected) {
		$info = $this->getMockBuilder('\OC\Files\FileInfo')
			->disableOriginalConstructor()
			->setMethods(array('getPermissions', 'isShared', 'isMounted', 'getType'))
			->getMock();
		$info->expects($this->any())
			->method('getPermissions')
			->will($this->returnValue($permissions));
		$info->expects($this->any())
			->method('isShared')
			->will($this->returnValue($shared));
		$info->expects($this->any())
			->method('isMounted')
			->will($this->returnValue($mounted));
		$info->expects($this->any())
			->method('getType')
			->will($this->returnValue($type));
		$view = $this->getMock('\OC\Files\View');

		$node = new \OC_Connector_Sabre_File($view, $info);
		$this->assertEquals($expected, $node->getDavPermissions());
	}
}
