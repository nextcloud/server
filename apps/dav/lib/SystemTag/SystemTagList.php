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
	private array $canAssignTagMap = [];

	/**
	 * @param ISystemTag[] $tags
	 */
	public function __construct(
		private array $tags,
		ISystemTagManager $tagManager,
		?IUser $user,
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

	public static function xmlDeserialize(Reader $reader): void {
		// unsupported/unused
	}

	public function xmlSerialize(Writer $writer): void {
		foreach ($this->tags as $tag) {
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
	}
}
