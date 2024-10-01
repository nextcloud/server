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

	/** @var ISystemTag[] */
	private array $tags;
	private ISystemTagManager $tagManager;
	private IUser $user;

	public function __construct(array $tags, ISystemTagManager $tagManager, IUser $user) {
		$this->tags = $tags;
		$this->tagManager = $tagManager;
		$this->user = $user;
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
				SystemTagPlugin::CANASSIGN_PROPERTYNAME => $this->tagManager->canUserAssignTag($tag, $this->user) ? 'true' : 'false',
				SystemTagPlugin::ID_PROPERTYNAME => $tag->getId(),
				SystemTagPlugin::USERASSIGNABLE_PROPERTYNAME => $tag->isUserAssignable() ? 'true' : 'false',
				SystemTagPlugin::USERVISIBLE_PROPERTYNAME => $tag->isUserVisible() ? 'true' : 'false',
			]);
			$writer->write($tag->getName());
			$writer->endElement();
		}
	}
}
