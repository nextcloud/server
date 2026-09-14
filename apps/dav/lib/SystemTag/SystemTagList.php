<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\SystemTag;

use OCP\IUser;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;

/**
 * TagList property
 *
 * This property contains multiple "tag" elements, each containing a tag name.
 */
class SystemTagList implements Element {
	public const NS_NEXTCLOUD = 'http://nextcloud.org/ns';
	public const NS_OWNCLOUD = 'http://owncloud.org/ns';
	private array $canAssignTagMap = [];

	/**
	 * @param ISystemTag[] $tags
	 */
	public function __construct(
		private array $tags,
		ISystemTagManager $tagManager,
		?IUser $user,
		private SystemTagFragmentCache $fragments = new SystemTagFragmentCache(),
	) {
		$this->tags = $tags;
		foreach ($this->tags as $tag) {
			$this->canAssignTagMap[$tag->getId()] = $tagManager->canUserAssignTag($tag, $user);
		}
	}

	/**
	 * @return ISystemTag[]
	 */
	public function getTags(): array {
		return $this->tags;
	}

	#[\Override]
	public static function xmlDeserialize(Reader $reader): void {
		// unsupported/unused
	}

	#[\Override]
	public function xmlSerialize(Writer $writer): void {
		$ncPrefix = $writer->namespaceMap[self::NS_NEXTCLOUD] ?? null;
		$ocPrefix = $writer->namespaceMap[self::NS_OWNCLOUD] ?? null;
		if ($ncPrefix === null || $ocPrefix === null) {
			foreach ($this->tags as $tag) {
				$this->writeTag($writer, $tag);
			}
			return;
		}
		foreach ($this->tags as $tag) {
			$writer->writeRaw($this->serializedTag($tag, $ncPrefix, $ocPrefix));
		}
	}

	private function writeTag(Writer $writer, ISystemTag $tag): void {
		$writer->startElement('{' . self::NS_NEXTCLOUD . '}system-tag');
		$writer->writeAttributes([
			SystemTagPlugin::CANASSIGN_PROPERTYNAME => $this->canAssignTagMap[$tag->getId()] ? 'true' : 'false',
			SystemTagPlugin::ID_PROPERTYNAME => $tag->getId(),
			SystemTagPlugin::USERASSIGNABLE_PROPERTYNAME => $tag->isUserAssignable() ? 'true' : 'false',
			SystemTagPlugin::USERVISIBLE_PROPERTYNAME => $tag->isUserVisible() ? 'true' : 'false',
			SystemTagPlugin::COLOR_PROPERTYNAME => $tag->getColor() ?? '',
		]);
		$writer->write($tag->getName());
		$writer->endElement();
	}

	private function serializedTag(ISystemTag $tag, string $ncPrefix, string $ocPrefix): string {
		$canAssign = $this->canAssignTagMap[$tag->getId()];
		$key = $tag->getId() . ($canAssign ? ':1:' : ':0:') . $ncPrefix . ':' . $ocPrefix;
		$serialized = $this->fragments->get($key);
		if ($serialized === null) {
			$fragment = new \XMLWriter();
			$fragment->openMemory();
			$fragment->startElementNS($ncPrefix, 'system-tag', null);
			$fragment->writeAttribute($ocPrefix . ':can-assign', $canAssign ? 'true' : 'false');
			$fragment->writeAttribute($ocPrefix . ':id', $tag->getId());
			$fragment->writeAttribute($ocPrefix . ':user-assignable', $tag->isUserAssignable() ? 'true' : 'false');
			$fragment->writeAttribute($ocPrefix . ':user-visible', $tag->isUserVisible() ? 'true' : 'false');
			$fragment->writeAttribute($ncPrefix . ':color', $tag->getColor() ?? '');
			$fragment->text($tag->getName());
			$fragment->endElement();
			$serialized = $fragment->outputMemory();
			$this->fragments->set($key, $serialized);
		}
		return $serialized;
	}
}
