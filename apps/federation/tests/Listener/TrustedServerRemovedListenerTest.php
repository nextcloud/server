<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Federation\Tests\Listener;

use OC\AppFramework\Utility\TimeFactory;
use OCA\Federation\BackgroundJob\GetSharedSecret;
use OCA\Federation\Listener\TrustedServerRemovedListener;
use OCA\Federation\TrustedServers;
use OCP\BackgroundJob\IJobList;
use OCP\Federation\Events\TrustedServerRemovedEvent;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\OCS\IDiscoveryService;
use Psr\Log\NullLogger;
use Test\BackgroundJob\DummyJobList;
use Test\TestCase;

class TrustedServerRemovedListenerTest extends TestCase {

	private IJobList $jobList;
	private TrustedServerRemovedListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->jobList = new DummyJobList();
		$this->listener = new TrustedServerRemovedListener($this->jobList);
	}

	public function testHandle(): void {
		// Arrange
		$url = 'https://example.com';
		$event = new TrustedServerRemovedEvent(md5($url), $url); // we are using a different hashing in the tests.
		$job1 = $this->createGetSharedSecretMock();
		$job2 = $this->createGetSharedSecretMock();
		$job3 = $this->createGetSharedSecretMock();
		$job4 = $this->createGetSharedSecretMock();
		$this->jobList->add($job1, ['url' => 'https://example.org', 'token' => 'nei0dooX', 'created' => 0]);
		$this->jobList->add($job2, ['url' => 'https://example.net', 'token' => 'ci6Shah7', 'created' => 0]);
		$this->jobList->add($job3, ['url' => $url, 'token' => 'ieXie6Me', 'created' => 0]);
		$this->jobList->add($job4, ['url' => $url, 'token' => 'thoQu8th', 'created' => 0]);

		// Act
		$this->listener->handle($event);
		$jobs = iterator_to_array($this->jobList->getJobsIterator(GetSharedSecret::class, null, 0), false);

		// Assert
		$this->assertCount(2, $jobs);
	}

	private function createGetSharedSecretMock(): GetSharedSecret {
		return new GetSharedSecret(
			$this->createMock(IClientService::class),
			$this->createMock(IURLGenerator::class),
			$this->jobList,
			$this->createMock(TrustedServers::class),
			new NullLogger(),
			$this->createMock(IDiscoveryService::class),
			new TimeFactory(),
			$this->createMock(IConfig::class),
		);
	}
}
