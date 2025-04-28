<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use OC\Async\Exceptions\BlockAlreadyRunningException;
use OC\Async\ForkManager;
use OC\Config\Lexicon\CoreConfigLexicon;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\Async\Enum\ProcessExecutionTime;
use OCP\IAppConfig;
use OCP\IRequest;

class AsyncProcessController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private IAppConfig $appConfig,
		private ForkManager $forkManager,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[PublicPage]
	#[FrontpageRoute(verb: 'POST', url: '/core/asyncProcessFork')]
	public function processFork(string $token): DataResponse {
		$metadata = ['executionTime' => ProcessExecutionTime::NOW];

		// remote might only need a confirmation that this is a valid loopback endpoint
		if ($token === '__ping__') {
			return new DataResponse(['ping' => $this->appConfig->getValueString('core', CoreConfigLexicon::ASYNC_LOOPBACK_PING)]);
		}

		$this->async($token);

		try {
			$this->forkManager->runSession($token, $metadata);
		} catch (BlockAlreadyRunningException) {
			// TODO: debug() ?
		}

		exit();
	}

	private function async(string $result = ''): void {
		if (ob_get_contents() !== false) {
			ob_end_clean();
		}

		header('Connection: close');
		header('Content-Encoding: none');
		ignore_user_abort();
		$timeLimit = 100;
		set_time_limit(max($timeLimit, 0));
		ob_start();

		echo($result);

		$size = ob_get_length();
		header('Content-Length: ' . $size);
		ob_end_flush();
		flush();
	}

}
