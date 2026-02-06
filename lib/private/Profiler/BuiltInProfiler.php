<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Profiler;

use DateTime;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Server;

class BuiltInProfiler {
	private \ExcimerProfiler $excimer;

	public function __construct(
		private IConfig $config,
		private IRequest $request,
	) {
	}

	public function start(): void {
		if (!extension_loaded('excimer')) {
			return;
		}

		$shouldProfileSingleRequest = $this->shouldProfileSingleRequest();
		$shouldSample = $this->config->getSystemValueBool('profiling.sample') && !$shouldProfileSingleRequest;


		if (!$shouldProfileSingleRequest && !$shouldSample) {
			return;
		}

		$requestRate = $this->config->getSystemValue('profiling.request.rate', 0.001);
		$sampleRate = $this->config->getSystemValue('profiling.sample.rate', 1.0);
		$eventType = $this->config->getSystemValue('profiling.event_type', EXCIMER_REAL);


		$this->excimer = new \ExcimerProfiler();
		$this->excimer->setPeriod($shouldProfileSingleRequest ? $requestRate : $sampleRate);
		$this->excimer->setEventType($eventType);
		$this->excimer->setMaxDepth(250);

		if ($shouldSample) {
			$this->excimer->setFlushCallback([$this, 'handleSampleFlush'], 1);
		}

		$this->excimer->start();
		register_shutdown_function([$this, 'handleShutdown']);
	}

	public function handleSampleFlush(\ExcimerLog $log): void {
		file_put_contents($this->getSampleFilename(), $log->formatCollapsed(), FILE_APPEND);
	}

	public function handleShutdown(): void {
		$this->excimer->stop();

		if (!$this->shouldProfileSingleRequest()) {
			$this->excimer->flush();
			return;
		}

		$request = Server::get(IRequest::class);
		$data = $this->excimer->getLog()->getSpeedscopeData();

		$data['profiles'][0]['name'] = $request->getMethod() . ' ' . $request->getRequestUri() . ' ' . $request->getId();

		file_put_contents($this->getProfileFilename(), json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	}

	private function shouldProfileSingleRequest(): bool {
		$shouldProfileSingleRequest = $this->config->getSystemValueBool('profiling.request', false);
		$profileSecret = $this->config->getSystemValueString('profiling.secret', '');
		$secretParam = $this->request->getParam('profile_secret') ?? null;
		return $shouldProfileSingleRequest || (!empty($profileSecret) && $profileSecret === $secretParam);
	}

	private function getSampleFilename(): string {
		$profilePath = $this->config->getSystemValueString('profiling.path', '/tmp');
		$sampleRotation = $this->config->getSystemValueInt('profiling.sample.rotation', 60);
		$timestamp = floor(time() / ($sampleRotation * 60)) * ($sampleRotation * 60);
		$sampleName = date('Y-m-d_Hi', (int)$timestamp);
		return $profilePath . '/sample-' . $sampleName . '.log';
	}

	private function getProfileFilename(): string {
		$profilePath = $this->config->getSystemValueString('profiling.path', '/tmp');
		$requestId = $this->request->getId();
		return $profilePath . '/profile-' . (new DateTime)->format('Y-m-d_His_v') . '-' . $requestId . '.json';
	}
}
