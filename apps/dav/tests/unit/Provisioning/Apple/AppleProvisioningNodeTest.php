<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\Provisioning\Apple;

use OCA\DAV\Provisioning\Apple\AppleProvisioningNode;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\PropPatch;
use Test\TestCase;

class AppleProvisioningNodeTest extends TestCase {
	private ITimeFactory&MockObject $timeFactory;
	private AppleProvisioningNode $node;

	protected function setUp(): void {
		parent::setUp();

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->node = new AppleProvisioningNode($this->timeFactory);
	}

	public function testGetName(): void {
		$this->assertEquals('apple-provisioning.mobileconfig', $this->node->getName());
	}

	public function testSetName(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);
		$this->expectExceptionMessage('Renaming apple-provisioning.mobileconfig is forbidden');

		$this->node->setName('foo');
	}

	public function testGetLastModified(): void {
		$this->assertEquals(null, $this->node->getLastModified());
	}


	public function testDelete(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);
		$this->expectExceptionMessage('apple-provisioning.mobileconfig may not be deleted');

		$this->node->delete();
	}

	public function testGetProperties(): void {
		$this->timeFactory->expects($this->once())
			->method('getDateTime')
			->willReturn(new \DateTime('2000-01-01'));

		$this->assertEquals([
			'{DAV:}getcontentlength' => 42,
			'{DAV:}getlastmodified' => 'Sat, 01 Jan 2000 00:00:00 GMT',
		], $this->node->getProperties([]));
	}


	public function testGetPropPatch(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);
		$this->expectExceptionMessage('apple-provisioning.mobileconfig\'s properties may not be altered.');

		$propPatch = $this->createMock(PropPatch::class);

		$this->node->propPatch($propPatch);
	}
}
