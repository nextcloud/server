<?php

declare(strict_types=1);

namespace Sabre\DAVACL\Xml\Property;

use Sabre\DAV;
use Sabre\DAV\Browser\HtmlOutput;
use Sabre\DAV\Browser\HtmlOutputHelper;
use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;

/**
 * This class represents the {DAV:}acl property.
 *
 * The {DAV:}acl property is a full list of access control entries for a
 * resource.
 *
 * {DAV:}acl is used as a WebDAV property, but it is also used within the body
 * of the ACL request.
 *
 * See:
 * http://tools.ietf.org/html/rfc3744#section-5.5
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Acl implements Element, HtmlOutput
{
    /**
     * List of privileges.
     *
     * @var array
     */
    protected $privileges;

    /**
     * Whether or not the server base url is required to be prefixed when
     * serializing the property.
     *
     * @var bool
     */
    protected $prefixBaseUrl;

    /**
     * Constructor.
     *
     * This object requires a structure similar to the return value from
     * Sabre\DAVACL\Plugin::getACL().
     *
     * Each privilege is a an array with at least a 'privilege' property, and a
     * 'principal' property. A privilege may have a 'protected' property as
     * well.
     *
     * The prefixBaseUrl should be set to false, if the supplied principal urls
     * are already full urls. If this is kept to true, the servers base url
     * will automatically be prefixed.
     *
     * @param bool $prefixBaseUrl
     */
    public function __construct(array $privileges, $prefixBaseUrl = true)
    {
        $this->privileges = $privileges;
        $this->prefixBaseUrl = $prefixBaseUrl;
    }

    /**
     * Returns the list of privileges for this property.
     *
     * @return array
     */
    public function getPrivileges()
    {
        return $this->privileges;
    }

    /**
     * The xmlSerialize method is called during xml writing.
     *
     * Use the $writer argument to write its own xml serialization.
     *
     * An important note: do _not_ create a parent element. Any element
     * implementing XmlSerializable should only ever write what's considered
     * its 'inner xml'.
     *
     * The parent of the current element is responsible for writing a
     * containing element.
     *
     * This allows serializers to be re-used for different element names.
     *
     * If you are opening new elements, you must also close them again.
     */
    public function xmlSerialize(Writer $writer)
    {
        foreach ($this->privileges as $ace) {
            $this->serializeAce($writer, $ace);
        }
    }

    /**
     * Generate html representation for this value.
     *
     * The html output is 100% trusted, and no effort is being made to sanitize
     * it. It's up to the implementor to sanitize user provided values.
     *
     * The output must be in UTF-8.
     *
     * The baseUri parameter is a url to the root of the application, and can
     * be used to construct local links.
     *
     * @return string
     */
    public function toHtml(HtmlOutputHelper $html)
    {
        ob_start();
        echo '<table>';
        echo '<tr><th>Principal</th><th>Privilege</th><th></th></tr>';
        foreach ($this->privileges as $privilege) {
            echo '<tr>';
            // if it starts with a {, it's a special principal
            if ('{' === $privilege['principal'][0]) {
                echo '<td>', $html->xmlName($privilege['principal']), '</td>';
            } else {
                echo '<td>', $html->link($privilege['principal']), '</td>';
            }
            echo '<td>', $html->xmlName($privilege['privilege']), '</td>';
            echo '<td>';
            if (!empty($privilege['protected'])) {
                echo '(protected)';
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';

        return ob_get_clean();
    }

    /**
     * The deserialize method is called during xml parsing.
     *
     * This method is called statically, this is because in theory this method
     * may be used as a type of constructor, or factory method.
     *
     * Often you want to return an instance of the current class, but you are
     * free to return other data as well.
     *
     * Important note 2: You are responsible for advancing the reader to the
     * next element. Not doing anything will result in a never-ending loop.
     *
     * If you just want to skip parsing for this element altogether, you can
     * just call $reader->next();
     *
     * $reader->parseInnerTree() will parse the entire sub-tree, and advance to
     * the next element.
     *
     * @return mixed
     */
    public static function xmlDeserialize(Reader $reader)
    {
        $elementMap = [
            '{DAV:}ace' => 'Sabre\Xml\Element\KeyValue',
            '{DAV:}privilege' => 'Sabre\Xml\Element\Elements',
            '{DAV:}principal' => 'Sabre\DAVACL\Xml\Property\Principal',
        ];

        $privileges = [];

        foreach ((array) $reader->parseInnerTree($elementMap) as $element) {
            if ('{DAV:}ace' !== $element['name']) {
                continue;
            }
            $ace = $element['value'];

            if (empty($ace['{DAV:}principal'])) {
                throw new DAV\Exception\BadRequest('Each {DAV:}ace element must have one {DAV:}principal element');
            }
            $principal = $ace['{DAV:}principal'];

            switch ($principal->getType()) {
                case Principal::HREF:
                    $principal = $principal->getHref();
                    break;
                case Principal::AUTHENTICATED:
                    $principal = '{DAV:}authenticated';
                    break;
                case Principal::UNAUTHENTICATED:
                    $principal = '{DAV:}unauthenticated';
                    break;
                case Principal::ALL:
                    $principal = '{DAV:}all';
                    break;
            }

            $protected = array_key_exists('{DAV:}protected', $ace);

            if (!isset($ace['{DAV:}grant'])) {
                throw new DAV\Exception\NotImplemented('Every {DAV:}ace element must have a {DAV:}grant element. {DAV:}deny is not yet supported');
            }
            foreach ($ace['{DAV:}grant'] as $elem) {
                if ('{DAV:}privilege' !== $elem['name']) {
                    continue;
                }

                foreach ($elem['value'] as $priv) {
                    $privileges[] = [
                        'principal' => $principal,
                        'protected' => $protected,
                        'privilege' => $priv,
                    ];
                }
            }
        }

        return new self($privileges);
    }

    /**
     * Serializes a single access control entry.
     */
    private function serializeAce(Writer $writer, array $ace)
    {
        $writer->startElement('{DAV:}ace');

        switch ($ace['principal']) {
            case '{DAV:}authenticated':
                $principal = new Principal(Principal::AUTHENTICATED);
                break;
            case '{DAV:}unauthenticated':
                $principal = new Principal(Principal::UNAUTHENTICATED);
                break;
            case '{DAV:}all':
                $principal = new Principal(Principal::ALL);
                break;
            default:
                $principal = new Principal(Principal::HREF, $ace['principal']);
                break;
        }

        $writer->writeElement('{DAV:}principal', $principal);
        $writer->startElement('{DAV:}grant');
        $writer->startElement('{DAV:}privilege');

        $writer->writeElement($ace['privilege']);

        $writer->endElement(); // privilege
        $writer->endElement(); // grant

        if (!empty($ace['protected'])) {
            $writer->writeElement('{DAV:}protected');
        }

        $writer->endElement(); // ace
    }
}
