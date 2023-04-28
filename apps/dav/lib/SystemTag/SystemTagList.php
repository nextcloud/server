<?php
/**
 * @copyright Copyright (c) 2023 Robin Appelman <robin@icewind.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
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
