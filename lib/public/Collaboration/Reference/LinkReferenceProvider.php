<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Collaboration\Reference;

use Fusonic\OpenGraph\Consumer;
use OC\Security\RateLimiting\Exception\RateLimitExceededException;
use OC\Security\RateLimiting\Limiter;
use OC\SystemConfig;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\NotFoundException;
use OCP\Http\Client\IClientService;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * @since 29.0.0
 */
class LinkReferenceProvider implements IReferenceProvider, IPublicReferenceProvider {

	/**
	 * for image size and webpage size
	 * @since 29.0.0
	 */
	public const MAX_CONTENT_LENGTH = 5 * 1024 * 1024;

	/**
	 * @since 29.0.0
	 */
	public const ALLOWED_CONTENT_TYPES = [
		'image/png',
		'image/jpg',
		'image/jpeg',
		'image/gif',
		'image/svg+xml',
		'image/webp'
	];

	/**
	 * @since 36.0.0
	 */
	private const GET_OPTIONS = ['timeout' => 3, 'nextcloud' => ['max_body_size' => self::MAX_CONTENT_LENGTH]];

	/**
	 * @since 29.0.0
	 */
	public function __construct(
		private IClientService $clientService,
		private LoggerInterface $logger,
		private SystemConfig $systemConfig,
		private IAppDataFactory $appDataFactory,
		private IURLGenerator $urlGenerator,
		private Limiter $limiter,
		private IUserSession $userSession,
		private IRequest $request,
	) {
	}

	/**
	 * @inheritDoc
	 * @since 29.0.0
	 */
	#[\Override]
	public function matchReference(string $referenceText): bool {
		if ($this->systemConfig->getValue('reference_opengraph', true) !== true) {
			return false;
		}

		return (bool)preg_match(IURLGenerator::URL_REGEX, $referenceText);
	}

	/**
	 * @inheritDoc
	 * @since 29.0.0
	 */
	#[\Override]
	public function resolveReference(string $referenceText): ?IReference {
		if ($this->matchReference($referenceText)) {
			$reference = new Reference($referenceText);
			$this->fetchReference($reference);
			return $reference;
		}

		return null;
	}

	/**
	 * @inheritDoc
	 * @since 30.0.0
	 */
	#[\Override]
	public function resolveReferencePublic(string $referenceText, string $sharingToken): ?IReference {
		return $this->resolveReference($referenceText);
	}

	/**
	 * Populates the reference with OpenGraph data
	 *
	 * @param Reference $reference
	 * @since 29.0.0
	 */
	private function fetchReference(Reference $reference): void {
		try {
			$user = $this->userSession->getUser();
			if ($user) {
				$this->limiter->registerUserRequest('opengraph', 10, 120, $user);
			} else {
				$this->limiter->registerAnonRequest('opengraph', 10, 120, $this->request->getRemoteAddress());
			}
		} catch (RateLimitExceededException $e) {
			return;
		}

		$client = $this->clientService->newClient();
		try {
			$headResponse = $client->head($reference->getId(), [ 'timeout' => 3 ]);
		} catch (\Exception $e) {
			$this->logger->debug('Failed to perform HEAD request to get target metadata', ['exception' => $e]);
			return;
		}

		$linkContentType = $headResponse->getHeader('Content-Type');
		$expectedContentTypeRegex = '/^text\/html;?/i';

		// check the header begins with the expected content type
		if (!preg_match($expectedContentTypeRegex, $linkContentType)) {
			$this->logger->debug('Skip resolving links pointing to content type that is not "text/html"');
			return;
		}

		try {
			$responseContent = $client->get($reference->getId(), self::GET_OPTIONS)->getBody();
		} catch (\Exception $e) {
			$this->logger->debug('Failed to fetch link for obtaining open graph data', ['exception' => $e]);
			return;
		}

		// OpenGraph handling
		$consumer = new Consumer();
		$consumer->useFallbackMode = true;
		$object = $consumer->loadHtml($responseContent);

		$reference->setUrl($reference->getId());

		if ($object->title) {
			$reference->setTitle($object->title);
		}

		if ($object->description) {
			$reference->setDescription($object->description);
		}

		if ($object->images) {
			try {
				$host = parse_url($object->images[0]->url, PHP_URL_HOST);
				if ($host === false || $host === null) {
					$this->logger->warning('Could not detect host of open graph image URI for ' . $reference->getId());
					return;
				}

				$appData = $this->appDataFactory->get('core');
				try {
					$folder = $appData->getFolder('opengraph');
				} catch (NotFoundException $e) {
					$folder = $appData->newFolder('opengraph');
				}

				$response = $client->get($object->images[0]->url, self::GET_OPTIONS);
				$contentType = $response->getHeader('Content-Type');

				if (in_array($contentType, self::ALLOWED_CONTENT_TYPES, true)) {
					$content = $response->getBody();

					if ($contentType === 'image/svg+xml' && $this->containsXslt($content)) {
						return;
					}

					$reference->setImageContentType($contentType);
					$folder->newFile(md5($reference->getId()), $content);
					$reference->setImageUrl($this->urlGenerator->linkToRouteAbsolute('core.Reference.preview', ['referenceId' => md5($reference->getId())]));
				}
			} catch (\Exception $e) {
				$this->logger->debug('Failed to fetch and store the open graph image for ' . $reference->getId(), ['exception' => $e]);
			}
		}
	}

	/**
	 * @inheritDoc
	 * @since 29.0.0
	 */
	#[\Override]
	public function getCachePrefix(string $referenceId): string {
		return $referenceId;
	}

	/**
	 * @inheritDoc
	 * @since 29.0.0
	 */
	#[\Override]
	public function getCacheKey(string $referenceId): ?string {
		return null;
	}

	/**
	 * @inheritDoc
	 * @since 30.0.0
	 */
	#[\Override]
	public function getCacheKeyPublic(string $referenceId, string $sharingToken): ?string {
		return null;
	}

	/**
	 * Check if XML content contains XSLT transformations
	 *
	 * XSLT transformations in SVG files can cause memory exhaustion
	 * in Chromium based browsers when rendered.
	 */
	private function containsXslt(string $xmlContent): bool {
		set_error_handler(function (int $code, string $message): bool {
			$this->logger->debug('Failed to parse XML content for XSLT check', ['error' => $message]);
			return true;
		});

		$xml = simplexml_load_string($xmlContent);

		restore_error_handler();

		$namespaces = $xml ? $xml->getNamespaces(true) : [];
		foreach ($namespaces as $namespace) {
			if (stripos($namespace, 'XSL/Transform') !== false) {
				return true;
			}
		}

		return false;
	}
}
