<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Files\NodeAttributes;

use OC\Files\NodeAttributes\NodeAttributeRegistry;
use OC\Files\NodeAttributes\NodeSerializer;
use OC\Share20\ShareAttributes;
use OCA\DAV\Connector\Sabre\File as DavFile;
use OCA\DAV\Connector\Sabre\FilesPlugin;
use OCA\DAV\Connector\Sabre\NodeAttributesPlugin;
use OCA\DAV\Connector\Sabre\SharesPlugin;
use OCA\DAV\Files\NodeAttributes\ShareAttributesProvider;
use OCA\DAV\Files\NodeAttributes\SharesProvider;
use OCA\Files_Sharing\SharedStorage;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\Files\NodeAttributes\NodeAttributeContext;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Share\IManager;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\Xml\Service;
use Test\TestCase;

/**
 * The share attributes drive download and permission decisions:
 * a PROPFIND and the serializer must give the same values.
 */
class SharesParityTest extends TestCase {
	private const ATTRIBUTES = [
		SharesPlugin::SHARETYPES_PROPERTYNAME,
		SharesPlugin::SHAREES_PROPERTYNAME,
		FilesPlugin::SHARE_ATTRIBUTES_PROPERTYNAME,
	];

	public static function dataShares(): array {
		return [
			'not shared' => [[], [], null],
			'outgoing user and link shares' => [[[IShare::TYPE_USER, 'bob', 'Bob'], [IShare::TYPE_LINK, null, null]], [], null],
			'received through a group' => [[], [[IShare::TYPE_GROUP, 'staff', 'Staff & Co <3>']], ['download' => false]],
			'received and reshared' => [[[IShare::TYPE_EMAIL, 'a@example.com', 'a@example.com']], [[IShare::TYPE_USER, 'alice', 'Alice']], ['download' => true]],
		];
	}

	/**
	 * @param list<array{int, ?string, ?string}> $outgoing
	 * @param list<array{int, ?string, ?string}> $incoming
	 * @param ?array{download: bool} $permissions attributes of the share the file was received through
	 */
	#[DataProvider('dataShares')]
	public function testDavAndSerializerAgree(array $outgoing, array $incoming, ?array $permissions): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$context = NodeAttributeContext::forUser($user);
		$node = $this->node($permissions);

		$registry = $this->createMock(NodeAttributeRegistry::class);
		$registry->method('getAttributeNames')->willReturn(self::ATTRIBUTES);
		$registry->method('getProvidersFor')->willReturn([
			['provider' => new SharesProvider($this->shareManager($outgoing, $incoming)), 'attributes' => [SharesPlugin::SHARETYPES_PROPERTYNAME, SharesPlugin::SHAREES_PROPERTYNAME]],
			['provider' => new ShareAttributesProvider(), 'attributes' => [FilesPlugin::SHARE_ATTRIBUTES_PROPERTYNAME]],
		]);

		self::assertSame($this->serialize($registry, $context, $node), $this->propFind($registry, $context, $node));
	}

	/**
	 * @param list<array{int, ?string, ?string}> $outgoing
	 * @param list<array{int, ?string, ?string}> $incoming
	 */
	private function shareManager(array $outgoing, array $incoming): IManager {
		$toShares = fn (array $shares, int $type): array => array_values(array_map(function (array $data): IShare {
			$share = $this->createMock(IShare::class);
			$share->method('getShareType')->willReturn($data[0]);
			$share->method('getSharedWith')->willReturn($data[1]);
			$share->method('getSharedWithDisplayName')->willReturn($data[2]);
			return $share;
		}, array_filter($shares, static fn (array $data): bool => $data[0] === $type)));

		$shareManager = $this->createMock(IManager::class);
		$shareManager->method('getSharesBy')->willReturnCallback(static fn (string $userId, int $type): array => $toShares($outgoing, $type));
		$shareManager->method('getSharedWith')->willReturnCallback(static fn (string $userId, int $type): array => $toShares($incoming, $type));
		return $shareManager;
	}

	private function node(?array $permissions): File {
		$share = $this->createMock(IShare::class);
		$attributes = null;
		if ($permissions !== null) {
			$attributes = new ShareAttributes();
			$attributes->setAttribute('permissions', 'download', $permissions['download']);
		}
		$share->method('getAttributes')->willReturn($attributes);
		$storage = $this->createMock(SharedStorage::class);
		$storage->method('instanceOfStorage')->willReturn($permissions !== null);
		$storage->method('getShare')->willReturn($share);

		$node = $this->createMock(File::class);
		$node->method('getId')->willReturn(42);
		$node->method('getPath')->willReturn('/alice/files/report.md');
		$node->method('getName')->willReturn('report.md');
		$node->method('getType')->willReturn(FileInfo::TYPE_FILE);
		$node->method('getStorage')->willReturn($storage);
		return $node;
	}

	/**
	 * The attributes of the PROPFIND response, read back as the WebDAV client does: a list per child element
	 *
	 * @return array<string, mixed>
	 */
	private function propFind(NodeAttributeRegistry $registry, NodeAttributeContext $context, File $node): array {
		$server = new Server();
		$server->addPlugin(new NodeAttributesPlugin($registry, static fn (): NodeAttributeContext => $context));
		$davNode = $this->createMock(DavFile::class);
		$davNode->method('getNode')->willReturn($node);

		$propFind = new PropFind('/report.md', self::ATTRIBUTES, 0);
		$server->emit('propFind', [$propFind, $davNode]);

		$document = new \DOMDocument();
		$document->loadXML((new Service())->write('{DAV:}prop', $propFind->getResultForMultiStatus()[200]));
		$result = [];
		foreach ($document->documentElement->childNodes as $property) {
			if ($property instanceof \DOMElement) {
				$result[$property->localName] = $property->localName === 'share-attributes' ? $property->textContent : $this->readList($property);
			}
		}
		ksort($result);
		return $result;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function serialize(NodeAttributeRegistry $registry, NodeAttributeContext $context, File $node): array {
		$userFolder = $this->createMock(IUserFolder::class);
		$userFolder->method('getPath')->willReturn('/alice/files');
		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')->willReturn($userFolder);
		$serializer = new NodeSerializer($registry, $rootFolder, $this->createMock(IURLGenerator::class), $this->createMock(LoggerInterface::class));

		$attributes = $serializer->serialize($context, [$node], self::ATTRIBUTES)[0]['attributes'];
		unset($attributes['etag']);
		// An empty list has no child element to name
		$attributes = array_map(static fn (mixed $value): mixed => is_array($value) && array_values($value) === [[]] ? [] : $value, $attributes);
		ksort($attributes);
		// XML only carries text
		array_walk_recursive($attributes, static function (mixed &$value): void {
			$value = is_bool($value) ? ($value ? 'true' : 'false') : (string)$value;
		});
		return $attributes;
	}

	/**
	 * A list attribute: one entry per child element, its text or the text of its fields
	 *
	 * @return array<string, list<string|array<string, string>>>
	 */
	private function readList(\DOMElement $element): array {
		$values = [];
		foreach ($element->childNodes as $child) {
			if (!($child instanceof \DOMElement)) {
				continue;
			}
			$fields = [];
			foreach ($child->childNodes as $field) {
				if ($field instanceof \DOMElement) {
					$fields[$field->localName] = $field->textContent;
				}
			}
			$values[$child->localName][] = $fields === [] ? $child->textContent : $fields;
		}
		return $values;
	}
}
