<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Federation\Listener;

use OCA\Federation\BackgroundJob\GetSharedSecret;
use OCA\Federation\BackgroundJob\RequestSharedSecret;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Federation\Events\TrustedServerRemovedEvent;

/** @template-implements IEventListener<TrustedServerRemovedEvent> */
class TrustedServerRemovedListener implements IEventListener {
	public function __construct(
		private readonly IJobList $jobList,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof TrustedServerRemovedEvent) {
			return;
		}

		if ($event->getUrl() === null) {
			return; // safe guard
		}

		$this->removeJobsByUrl(RequestSharedSecret::class, $event->getUrl());
		$this->removeJobsByUrl(GetSharedSecret::class, $event->getUrl());
	}

	/**
	 * Remove RequestSharedSecret or GetSharedSecret jobs from the job list by their URL.
	 * The jobs are scheduled with url, token, and created as arguments.
	 * Thus, we have to loop over the jobs here and cannot use IJobList.remove.
	 */
	private function removeJobsByUrl(string $class, string $url): void {
		foreach ($this->jobList->getJobsIterator($class, null, 0) as $job) {
			$arguments = $job->getArgument();
			if (isset($arguments['url']) && $arguments['url'] === $url) {
				try {
					$this->jobList->removeById($job->getId());
				} catch (\Exception) {
					// Removing the background jobs is optional because they will expire sometime.
					// Therefore, we are using catch and ignore.
				}
			}
		}
	}
}
