<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OCM;

use OC\OCM\Model\OCMProvider;
use OCP\OCM\IOCMResource;
use Test\TestCase;

class OCMProviderTest extends TestCase {
	private OCMProvider $provider;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->provider = new OCMProvider();
	}

	private function resource(string $name, array $shareTypes, array $protocols): IOCMResource {
		$resource = $this->provider->createNewResourceType();
		$resource->setName($name)
			->setShareTypes($shareTypes)
			->setProtocols($protocols);
		return $resource;
	}

	public function testAddResourceTypeKeepsDistinctNames(): void {
		$this->provider->addResourceType($this->resource('file', ['user'], ['webdav' => '/dav/']));
		$this->provider->addResourceType($this->resource('folder', ['user'], ['webapp' => []]));

		$this->assertCount(2, $this->provider->getResourceTypes());
	}

	public function testAddResourceTypeMergesSameName(): void {
		$this->provider->addResourceType($this->resource('folder', ['user'], ['webapp' => []]));
		$this->provider->addResourceType($this->resource('folder', ['user'], ['webapp-receive' => ['targets' => ['blank', 'iframe']]]));

		$resourceTypes = $this->provider->getResourceTypes();
		$this->assertCount(1, $resourceTypes);
		$this->assertSame(
			['webapp' => [], 'webapp-receive' => ['targets' => ['blank', 'iframe']]],
			$resourceTypes[0]->getProtocols(),
		);
	}

	public function testAddResourceTypeDedupesShareTypes(): void {
		$this->provider->addResourceType($this->resource('folder', ['user'], ['webapp' => []]));
		$this->provider->addResourceType($this->resource('folder', ['user', 'group'], ['webapp-receive' => []]));

		$shareTypes = $this->provider->getResourceTypes()[0]->getShareTypes();
		$this->assertSame(['user', 'group'], $shareTypes);
		// Deduplication must not leave key gaps, or shareTypes would
		// serialize as a JSON object instead of an array.
		$this->assertSame('["user","group"]', json_encode($shareTypes));
	}

	public function testAddResourceTypeMergeOverwritesSameProtocol(): void {
		$this->provider->addResourceType($this->resource('folder', ['user'], ['webapp' => ['a' => 1]]));
		$this->provider->addResourceType($this->resource('folder', ['user'], ['webapp' => ['b' => 2]]));

		$this->assertSame(
			['webapp' => ['b' => 2]],
			$this->provider->getResourceTypes()[0]->getProtocols(),
		);
	}
}
