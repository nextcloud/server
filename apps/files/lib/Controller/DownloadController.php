<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Louis Chmn <louis@chmn.me>
 *
 * @author Louis Chmn <louis@chmn.me>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files\Controller;

use OC\AppFramework\Bootstrap\Coordinator;
use OCP\AppFramework\Http\ZipResponse;
use OCP\AppFramework\Controller;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IFileDownloadProvider;
use OCP\Files\Node;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class DownloadController extends Controller {
	private Coordinator $coordinator;
	private LoggerInterface $logger;

	public function __construct(
		string $appName,
		IRequest $request,
		Coordinator $coordinator,
		LoggerInterface $logger
	) {
		parent::__construct($appName, $request);

		$this->request = $request;
		$this->coordinator = $coordinator;
		$this->logger = $logger;
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 * @UserRateThrottle(limit=5, period=100)
	 * @AnonRateThrottle(limit=1, period=100)
	 * @BruteForceProtection(action='download_files')
	 */
	public function index(string $files): ZipResponse {
		$response = new ZipResponse($this->request, 'download');

		/** @var string[] */
		$files = json_decode($files);

		if (count($files) === 0) {
			return $response;
		}

		[$firstPrefix,] = explode('/', $files[0], 2);
		$commonPrefix = $firstPrefix;
		foreach ($files as $filePath) {
			$commonPrefix = $this->getCommonPrefix($filePath, $commonPrefix);
		}

		foreach ($files as $filePath) {
			$node = null;

			foreach ($this->getProviders() as $provider) {
				try {
					$node = $provider->getNode($filePath);
					if ($node !== null) {
						break;
					}
				} catch (\Throwable $ex) {
					$providerClass = $provider::class;
					$this->logger->warning("Error while getting file content from $providerClass", ['exception' => $ex]);
				}
			}

			if ($node === null) {
				continue;
			}

			$this->addNode($response, $node, substr($filePath, strlen($commonPrefix)));
		}

		return $response;
	}

	private function getCommonPrefix(string $str1, string $str2): string {
		$explodedStr1 = explode('/', $str1);
		$explodedStr2 = explode('/', $str2);

		for ($i = 0; $i < count($explodedStr1); $i++) {
			if (!isset($explodedStr2[$i]) || $explodedStr1[$i] !== $explodedStr2[$i]) {
				$i--;
				break;
			}
		}

		if ($i < 0) {
			return '';
		} else {
			return implode(array_slice($explodedStr1, 0, $i));
		}
	}

	private function addNode(ZipResponse $response, Node $node, string $path): void {
		if ($node instanceof File) {
			$response->addResource($node->fopen('r'), $path, $node->getSize());
		}

		if ($node instanceof Folder) {
			foreach ($node->getDirectoryListing() as $subnode) {
				$this->addNode($response, $subnode, $path.'/'.$subnode->getName());
			}
		}
	}

	/**
	 * @return IFileDownloadProvider[]
	 */
	private function getProviders() {
		/** @var IFileDownloadProvider[] */
		$providers = [];

		$context = $this->coordinator->getRegistrationContext();
		if ($context === null) {
			throw new \Exception("Can't get download providers");
		}

		$providerRegistrations = $context->getFileDownloadProviders();

		foreach ($providerRegistrations as $registration) {
			$providers[] = \OCP\Server::get($registration->getService());
		}

		return $providers;
	}
}
