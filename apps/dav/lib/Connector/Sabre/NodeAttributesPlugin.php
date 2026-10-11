<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Connector\Sabre;

use Closure;
use NCU\Files\NodeAttributes\NodeAttributeContext;
use NCU\Files\NodeAttributes\NodeAttributeList;
use OC\Files\NodeAttributes\NodeAttributeRegistry;
use OCA\DAV\Files\NodeAttributes\IFolderListingPreload;
use OCP\Files\Folder;
use Sabre\DAV\ICollection;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

/**
 * Serves the attributes of the registered node attribute providers as WebDAV properties
 */
class NodeAttributesPlugin extends ServerPlugin {
	/** @var array<int, true> file ids already preloaded in this request */
	private array $preloaded = [];

	/**
	 * @param Closure(): ?NodeAttributeContext $contextResolver context of the request, null to serve nothing
	 */
	public function __construct(
		private readonly NodeAttributeRegistry $registry,
		private readonly Closure $contextResolver,
	) {
	}

	#[\Override]
	public function initialize(Server $server): void {
		$server->on('preloadCollection', $this->preloadCollection(...));
		$server->on('preloadProperties', $this->preloadProperties(...));
		$server->on('propFind', $this->propFind(...));
	}

	private function preloadCollection(PropFind $propFind, ICollection $collection): void {
		if (!($collection instanceof Directory)) {
			return;
		}

		$attributes = $this->getRequestedAttributes($propFind);
		if ($attributes === []) {
			return;
		}

		$folder = $collection->getNode();
		$children = [];
		foreach ($collection->getChildren() as $child) {
			if ($child instanceof Node) {
				$children[] = $child->getNode();
			}
		}
		$this->preload([$folder, ...$children], $attributes, $folder);
	}

	/**
	 * Preload the attributes of search results
	 *
	 * @param INode[] $nodes
	 * @param string[] $requestProperties
	 */
	private function preloadProperties(array $nodes, array $requestProperties): void {
		$attributes = array_values(array_intersect($requestProperties, $this->registry->getAttributeNames()));
		if ($attributes === []) {
			return;
		}

		$fileNodes = [];
		foreach ($nodes as $node) {
			if ($node instanceof Node) {
				$fileNodes[] = $node->getNode();
			}
		}
		$this->preload($fileNodes, $attributes);
	}

	private function propFind(PropFind $propFind, INode $node): void {
		if (!($node instanceof Node)) {
			return;
		}

		$attributes = $this->getRequestedAttributes($propFind);
		$context = ($this->contextResolver)();
		if ($attributes === [] || $context === null) {
			return;
		}

		$fileNode = $node->getNode();
		if (!isset($this->preloaded[$fileNode->getId()])) {
			$this->preload([$fileNode], $attributes);
		}

		foreach ($this->registry->getProvidersFor($attributes) as $group) {
			$values = $group['provider']->getAttributes($context, $fileNode, $group['attributes']);
			foreach ($group['attributes'] as $attribute) {
				if (!isset($values[$attribute])) {
					continue;
				}
				$value = $this->toDavValue($attribute, $values[$attribute]);
				$propFind->handle($attribute, static fn () => $value);
			}
		}
	}

	/**
	 * Attributes are only served when requested, also for allprop requests like other custom properties
	 *
	 * @return list<string>
	 */
	private function getRequestedAttributes(PropFind $propFind): array {
		return array_values(array_intersect($propFind->getRequestedProperties(), $this->registry->getAttributeNames()));
	}

	/**
	 * @param list<\OCP\Files\Node> $nodes
	 * @param list<string> $attributes
	 * @param ?Folder $listing the folder when the nodes are the folder followed by its children
	 */
	private function preload(array $nodes, array $attributes, ?Folder $listing = null): void {
		$context = ($this->contextResolver)();
		if ($context === null || $nodes === []) {
			return;
		}

		foreach ($this->registry->getProvidersFor($attributes) as $group) {
			if ($listing !== null && $group['provider'] instanceof IFolderListingPreload) {
				$group['provider']->preloadListing($context, $listing, array_slice($nodes, 1), $group['attributes']);
			} else {
				$group['provider']->preload($context, $nodes, $group['attributes']);
			}
		}
		foreach ($nodes as $node) {
			$this->preloaded[$node->getId()] = true;
		}
	}

	/**
	 * Values are typed for JSON, WebDAV carries text that the client parses back
	 */
	private function toDavValue(string $attribute, bool|int|float|string|NodeAttributeList $value): int|float|string|NodeAttributeListXml {
		if (is_bool($value)) {
			return $value ? 'true' : 'false';
		}
		if ($value instanceof NodeAttributeList) {
			$namespace = substr($attribute, 0, (int)strrpos($attribute, '}') + 1);
			return new NodeAttributeListXml($namespace . $value->getChildName(), $value->getValues());
		}
		return $value;
	}
}
