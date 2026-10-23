<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\NodeAttributes;

use NCU\Files\NodeAttributes\INodeSerializer;
use NCU\Files\NodeAttributes\NodeAttributeContext;
use NCU\Files\NodeAttributes\NodeAttributeList;
use OCP\Files\FileInfo;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type NodeData from INodeSerializer
 */
class NodeSerializer implements INodeSerializer {
	public function __construct(
		private readonly NodeAttributeRegistry $registry,
		private readonly IRootFolder $rootFolder,
		private readonly IURLGenerator $urlGenerator,
		private readonly LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function serialize(NodeAttributeContext $context, array $nodes, ?array $attributes = null): array {
		$groups = $this->registry->getProvidersFor($attributes ?? $this->registry->getAttributeNames());
		foreach ($groups as $group) {
			$group['provider']->preload($context, $nodes, $group['attributes']);
		}

		[$root, $rootPath, $remoteUrl] = $this->getRoot($context);
		$result = [];
		foreach ($nodes as $node) {
			$path = $this->getRelativePath($rootPath, $node->getPath());
			if ($path === null) {
				$this->logger->warning('Skipping ' . $node->getPath() . ', it is outside of ' . $rootPath);
				continue;
			}

			$data = $this->getNodeData($node, $root, $path, $remoteUrl, $context);
			foreach ($groups as $group) {
				$values = $group['provider']->getAttributes($context, $node, $group['attributes']);
				foreach ($group['attributes'] as $attribute) {
					if (array_key_exists($attribute, $values)) {
						$value = $values[$attribute];
						$data['attributes'][NodeAttributeRegistry::getLocalName($attribute)] = $value instanceof NodeAttributeList ? $value->jsonSerialize() : $value;
					}
				}
			}
			$result[] = $data;
		}
		return $result;
	}

	#[\Override]
	public function getAttributeNames(): array {
		return $this->registry->getAttributeNames();
	}

	/**
	 * The DAV root of the context, the absolute path it maps to and the DAV remote URL
	 *
	 * @return array{string, string, string}
	 */
	private function getRoot(NodeAttributeContext $context): array {
		$user = $context->getUser();
		if ($user !== null) {
			return [
				'/files/' . $user->getUID(),
				$this->rootFolder->getUserFolder($user->getUID())->getPath(),
				$this->urlGenerator->getAbsoluteURL('/remote.php/dav'),
			];
		}

		$share = $context->getShare();
		if ($share === null) {
			throw new \InvalidArgumentException('The node attribute context has neither a user nor a share');
		}
		return [
			'/files/' . $share->getToken(),
			$share->getNode()->getPath(),
			$this->urlGenerator->getAbsoluteURL('/public.php/dav'),
		];
	}

	/**
	 * Path of a node within the root, starting with a slash, null when outside of it
	 */
	private function getRelativePath(string $rootPath, string $path): ?string {
		if ($path === $rootPath) {
			return '/';
		}
		if (!str_starts_with($path, rtrim($rootPath, '/') . '/')) {
			return null;
		}
		return substr($path, strlen(rtrim($rootPath, '/')));
	}

	/**
	 * @return NodeData
	 */
	private function getNodeData(Node $node, string $root, string $path, string $remoteUrl, NodeAttributeContext $context): array {
		$isFolder = $node->getType() === FileInfo::TYPE_FOLDER;
		$creationTime = $node->getCreationTime();

		return [
			'id' => $node->getId(),
			'source' => $remoteUrl . $root . ($path === '/' ? '' : $path),
			'root' => $root,
			'owner' => $node->getOwner()?->getUID() ?? $context->getUser()?->getUID(),
			'mime' => $isFolder ? 'httpd/unix-directory' : $node->getMimetype(),
			'mtime' => $node->getMTime(),
			'crtime' => $creationTime > 0 ? $creationTime : null,
			'size' => $node->getSize(),
			'permissions' => $node->getPermissions(),
			'displayname' => $node->getName(),
			'type' => $isFolder ? 'folder' : 'file',
			'attributes' => [
				'etag' => $node->getEtag(),
			],
		];
	}
}
