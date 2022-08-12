<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 */

namespace OC\Collaboration\Reference;

use Fusonic\OpenGraph\Consumer;
use OC\SystemConfig;
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\IReferenceProvider;
use OCP\Http\Client\IClientService;
use Psr\Log\LoggerInterface;

class LinkReferenceProvider implements IReferenceProvider {
	public const URL_PATTERN = '/(\s|^)(https?:\/\/)?((?:[-A-Z0-9+_]+\.)+[-A-Z]+(?:\/[-A-Z0-9+&@#%?=~_|!:,.;()]*)*)(\s|$)/i';

	private IClientService $clientService;
	private LoggerInterface $logger;
	private SystemConfig $systemConfig;

	public function __construct(IClientService $clientService, LoggerInterface $logger, SystemConfig $systemConfig) {
		$this->clientService = $clientService;
		$this->logger = $logger;
		$this->systemConfig = $systemConfig;
	}

	public function matchReference(string $referenceText): bool {
		if ($this->systemConfig->getValue('reference_opengraph', true) !== true) {
			return false;
		}

		return (bool)preg_match(self::URL_PATTERN, $referenceText);
	}

	public function resolveReference(string $referenceText): ?IReference {
		if ($this->matchReference($referenceText)) {
			$reference = new Reference($referenceText);
			$this->fetchReference($reference);
			return $reference;
		}

		return null;
	}

	private function fetchReference(Reference $reference) {
		$client = $this->clientService->newClient();
		try {
			$response = $client->get($reference->getId());
		} catch (\Exception $e) {
			$this->logger->debug('Failed to fetch link for obtaining open graph data', ['exception' => $e]);
			return;
		}

		$responseBody = (string)$response->getBody();

		$reference->setUrl($reference->getId());

		// OpenGraph handling
		$consumer = new Consumer();
		$object = $consumer->loadHtml($responseBody);

		if ($object->title) {
			$reference->setTitle($object->title);
		}

		if ($object->description) {
			$reference->setDescription($object->description);
		}

		if ($object->images) {
			$reference->setImageUrl($object->images[0]->url);
		}
	}

	public function isGloballyCachable(): bool {
		return true;
	}

	public function getCacheKey(string $referenceId): string {
		return '';
	}
}
