<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\StorageConfig;

class StorageConfigTest extends \Test\TestCase {
	public function testJsonSerialization(): void {
		$backend = $this->getMockBuilder(Backend::class)
			->disableOriginalConstructor()
			->getMock();
		$parameter = $this->getMockBuilder(DefinitionParameter::class)
			->disableOriginalConstructor()
			->getMock();
		$parameter
			->expects($this->once())
			->method('getType')
			->willReturn(1);
		$backend
			->expects($this->once())
			->method('getParameters')
			->willReturn(['secure' => $parameter]);
		$backend->method('getIdentifier')
			->willReturn('storage::identifier');

		$authMech = $this->getMockBuilder(AuthMechanism::class)
			->disableOriginalConstructor()
			->getMock();
		$authMech->method('getIdentifier')
			->willReturn('auth::identifier');

		$storageConfig = new StorageConfig(1);
		$storageConfig->setMountPoint('test');
		$storageConfig->setBackend($backend);
		$storageConfig->setAuthMechanism($authMech);
		$storageConfig->setBackendOptions(['user' => 'test', 'password' => 'password123', 'secure' => '1']);
		$storageConfig->setPriority(128);
		$storageConfig->setApplicableUsers(['user1', 'user2']);
		$storageConfig->setApplicableGroups(['group1', 'group2']);
		$storageConfig->setMountOptions(['preview' => false]);

		$json = $storageConfig->jsonSerialize();

		$this->assertSame(1, $json['id']);
		$this->assertSame('/test', $json['mountPoint']);
		$this->assertSame('storage::identifier', $json['backend']);
		$this->assertSame('auth::identifier', $json['authMechanism']);
		$this->assertSame('test', $json['backendOptions']['user']);
		$this->assertSame('password123', $json['backendOptions']['password']);
		$this->assertSame(true, $json['backendOptions']['secure']);
		$this->assertSame(128, $json['priority']);
		$this->assertSame(['user1', 'user2'], $json['applicableUsers']);
		$this->assertSame(['group1', 'group2'], $json['applicableGroups']);
		$this->assertSame(['preview' => false], $json['mountOptions']);
	}
}
