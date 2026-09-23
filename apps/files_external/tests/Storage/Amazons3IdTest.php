<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Tests\Storage;

use OCA\Files_External\Lib\Storage\AmazonS3;
use Test\TestCase;

/**
 * Regression test for the storage id computation in AmazonS3::__construct().
 *
 * Constructs the storage directly (no live S3 backend) to exercise only the id
 * derivation, which runs unconditionally in the constructor.
 */
#[\PHPUnit\Framework\Attributes\Group('S3')]
class Amazons3IdTest extends TestCase {
	/**
	 * Mounts authenticating via the AWS SDK default credential chain (env, EC2
	 * instance profile, ECS task role) carry no static `key` param. The id must
	 * still be derivable without emitting an "Undefined array key" warning
	 * (PHPUnit fails the test on any emitted warning).
	 */
	public function testConstructWithoutKey(): void {
		$storage = new AmazonS3(['bucket' => 'test-bucket']);
		$this->assertNotEmpty($storage->getId());
	}
}
