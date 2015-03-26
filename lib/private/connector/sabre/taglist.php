<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Connector\Sabre;

use Sabre\DAV;

/**
 * TagList property
 *
 * This property contains multiple "tag" elements, each containing a tag name.
 */
class TagList extends DAV\Property {
	const NS_OWNCLOUD = 'http://owncloud.org/ns';

    /**
     * tags
     *
     * @var array
     */
    private $tags;

    /**
     * @param array $tags
     */
    public function __construct(array $tags) {
        $this->tags = $tags;
    }

    /**
     * Returns the tags
     *
     * @return array
     */
    public function getTags() {

        return $this->tags;

    }

    /**
     * Serializes this property.
     *
     * @param DAV\Server $server
     * @param \DOMElement $dom
     * @return void
     */
    public function serialize(DAV\Server $server,\DOMElement $dom) {

        $prefix = $server->xmlNamespaces[self::NS_OWNCLOUD];

        foreach($this->tags as $tag) {

            $elem = $dom->ownerDocument->createElement($prefix . ':tag');
            $elem->appendChild($dom->ownerDocument->createTextNode($tag));

            $dom->appendChild($elem);
        }

    }

    /**
     * Unserializes this property from a DOM Element
     *
     * This method returns an instance of this class.
     * It will only decode tag values.
     *
     * @param \DOMElement $dom
	 * @param array $propertyMap
     * @return \OC\Connector\Sabre\TagList
     */
    static function unserialize(\DOMElement $dom, array $propertyMap) {

        $tags = array();
        foreach($dom->childNodes as $child) {
            if (DAV\XMLUtil::toClarkNotation($child)==='{' . self::NS_OWNCLOUD . '}tag') {
                $tags[] = $child->textContent;
            }
        }
        return new self($tags);

    }

}
