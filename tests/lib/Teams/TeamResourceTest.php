<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Teams;

use OCP\Teams\ITeamOwnedResourceProvider;
use OCP\Teams\ITeamResourceProvider;
use OCP\Teams\TeamResource;
use PHPUnit\Framework\TestCase;

final class TeamResourceTest extends TestCase {
	public function testOwnedResourceProviderCanOwnTeamResource(): void {
		$provider = $this->createMock(ITeamOwnedResourceProvider::class);
		$resource = new TeamResource($provider, '1', 'Resource', '/open');

		self::assertSame($provider, $resource->getProvider());
	}

	public function testReferenceUrlIsOptionalAndSerializedSeparately(): void {
		$provider = $this->createMock(ITeamResourceProvider::class);
		$legacy = new TeamResource($provider, '1', 'Resource', '/open');
		self::assertNull($legacy->getReferenceUrl());
		self::assertNull($legacy->jsonSerialize()['referenceUrl']);

		$resource = new TeamResource($provider, '1', 'Resource', '/open', referenceUrl: '/reference');
		self::assertSame('/open', $resource->getUrl());
		self::assertSame('/reference', $resource->getReferenceUrl());
		self::assertSame('/reference', $resource->jsonSerialize()['referenceUrl']);
	}
}
