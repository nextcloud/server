<?php

namespace OCA\DAV\CardDAV\Sharing\Xml;

use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

class ShareRequest implements XmlDeserializable {

    public $set = [];

    public $remove = [];

    /**
     * Constructor
     *
     * @param array $set
     * @param array $remove
     */
    function __construct(array $set, array $remove) {

        $this->set = $set;
        $this->remove = $remove;

    }

    static function xmlDeserialize(Reader $reader) {

        $elems = $reader->parseInnerTree([
            '{' . \Sabre\CardDAV\Plugin::NS_CARDDAV. '}set'    => 'Sabre\\Xml\\Element\\KeyValue',
            '{' . \Sabre\CardDAV\Plugin::NS_CARDDAV . '}remove' => 'Sabre\\Xml\\Element\\KeyValue',
        ]);

        $set = [];
        $remove = [];

        foreach ($elems as $elem) {
            switch ($elem['name']) {

                case '{' . \Sabre\CardDAV\Plugin::NS_CARDDAV . '}set' :
                    $sharee = $elem['value'];

                    $sumElem = '{' . \Sabre\CardDAV\Plugin::NS_CARDDAV . '}summary';
                    $commonName = '{' . \Sabre\CardDAV\Plugin::NS_CARDDAV . '}common-name';

                    $set[] = [
                        'href'       => $sharee['{DAV:}href'],
                        'commonName' => isset($sharee[$commonName]) ? $sharee[$commonName] : null,
                        'summary'    => isset($sharee[$sumElem]) ? $sharee[$sumElem] : null,
                        'readOnly'   => !array_key_exists('{' . \Sabre\CardDAV\Plugin::NS_CARDDAV . '}read-write', $sharee),
                    ];
                    break;

                case '{' . \Sabre\CardDAV\Plugin::NS_CARDDAV . '}remove' :
                    $remove[] = $elem['value']['{DAV:}href'];
                    break;

            }
        }

        return new self($set, $remove);

    }

}
