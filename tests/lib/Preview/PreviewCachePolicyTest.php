<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Preview\PreviewCachePolicy;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class PreviewCachePolicyTest extends TestCase {
	private IConfig&MockObject $config;
	private PreviewCachePolicy $policy;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->policy = new PreviewCachePolicy($this->config);
	}

	public function testDefaultAuthenticatedContainsPrivateAndNotPublic(): void {
		$control = PreviewCachePolicy::buildCacheControl(PreviewCachePolicy::defaultAuthenticated());
		$this->assertStringContainsString('private', $control);
		$this->assertStringNotContainsString('public', $control);
		$this->assertStringContainsString('immutable', $control);
	}

	public function testPublicSharePolicyCanEmitPublicAndSMaxAge(): void {
		$control = PreviewCachePolicy::buildCacheControl([
			'visibility' => 'public',
			'max_age' => 86400,
			's_maxage' => 86400,
			'immutable' => false,
		]);
		$this->assertStringContainsString('public', $control);
		$this->assertStringContainsString('s-maxage=86400', $control);
		$this->assertStringContainsString('must-revalidate', $control);
		$this->assertStringNotContainsString('immutable', $control);
	}

	public function testImmutableAppendedOnlyWhenEnabled(): void {
		$without = PreviewCachePolicy::buildCacheControl([
			'visibility' => 'private',
			'max_age' => 60,
			'immutable' => false,
		]);
		$with = PreviewCachePolicy::buildCacheControl([
			'visibility' => 'private',
			'max_age' => 60,
			'immutable' => true,
		]);
		$this->assertStringContainsString('must-revalidate', $without);
		$this->assertStringNotContainsString('immutable', $without);
		$this->assertStringContainsString('immutable', $with);
	}

	public function testRawOverrideUsedWhenSet(): void {
		$control = PreviewCachePolicy::buildCacheControl([
			'visibility' => 'public',
			'max_age' => 10,
			'cache_control' => 'private, max-age=1',
		]);
		$this->assertSame('private, max-age=1', $control);
	}

	public function testMaxAgeZeroEmitsNoCache(): void {
		$control = PreviewCachePolicy::buildCacheControl([
			'visibility' => 'private',
			'max_age' => 0,
		]);
		$this->assertSame('no-cache, no-store, must-revalidate', $control);
	}

	public function testMissingConfigReturnsNullPolicy(): void {
		$unset = new \stdClass();
		$this->config->method('getSystemValue')->willReturn($unset);
		$this->assertNull($this->policy->getConfiguredPolicy(PreviewCachePolicy::AUTHENTICATED));
	}

	public function testConfiguredPolicyIsRead(): void {
		$this->config->method('getSystemValue')->willReturn([
			'visibility' => 'public',
			'max_age' => 120,
			's_maxage' => 60,
			'immutable' => true,
		]);
		$policy = $this->policy->getConfiguredPolicy(PreviewCachePolicy::PUBLIC);
		$this->assertNotNull($policy);
		$this->assertSame('public', $policy['visibility']);
		$this->assertSame(120, $policy['max_age']);
		$this->assertSame(60, $policy['s_maxage']);
		$this->assertTrue($policy['immutable']);
	}
}
