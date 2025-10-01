<?php

declare(strict_types=1);

namespace Sabre\DAVACL\Xml\Property;

use Sabre\DAV\Browser\HtmlOutput;
use Sabre\DAV\Browser\HtmlOutputHelper;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * SupportedPrivilegeSet property.
 *
 * This property encodes the {DAV:}supported-privilege-set property, as defined
 * in rfc3744. Please consult the rfc for details about it's structure.
 *
 * This class expects a structure like the one given from
 * Sabre\DAVACL\Plugin::getSupportedPrivilegeSet as the argument in its
 * constructor.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class SupportedPrivilegeSet implements XmlSerializable, HtmlOutput
{
    /**
     * privileges.
     *
     * @var array
     */
    protected $privileges;

    /**
     * Constructor.
     */
    public function __construct(array $privileges)
    {
        $this->privileges = $privileges;
    }

    /**
     * Returns the privilege value.
     *
     * @return array
     */
    public function getValue()
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
        $this->serializePriv($writer, '{DAV:}all', ['aggregates' => $this->privileges]);
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
        $traverse = function ($privName, $priv) use (&$traverse, $html) {
            echo '<li>';
            echo $html->xmlName($privName);
            if (isset($priv['abstract']) && $priv['abstract']) {
                echo ' <i>(abstract)</i>';
            }
            if (isset($priv['description'])) {
                echo ' '.$html->h($priv['description']);
            }
            if (isset($priv['aggregates'])) {
                echo "\n<ul>\n";
                foreach ($priv['aggregates'] as $subPrivName => $subPriv) {
                    $traverse($subPrivName, $subPriv);
                }
                echo '</ul>';
            }
            echo "</li>\n";
        };

        ob_start();
        echo '<ul class="tree">';
        $traverse('{DAV:}all', ['aggregates' => $this->getValue()]);
        echo "</ul>\n";

        return ob_get_clean();
    }

    /**
     * Serializes a property.
     *
     * This is a recursive function.
     *
     * @param string $privName
     * @param array  $privilege
     */
    private function serializePriv(Writer $writer, $privName, $privilege)
    {
        $writer->startElement('{DAV:}supported-privilege');

        $writer->startElement('{DAV:}privilege');
        $writer->writeElement($privName);
        $writer->endElement(); // privilege

        if (!empty($privilege['abstract'])) {
            $writer->writeElement('{DAV:}abstract');
        }
        if (!empty($privilege['description'])) {
            $writer->writeElement('{DAV:}description', $privilege['description']);
        }
        if (isset($privilege['aggregates'])) {
            foreach ($privilege['aggregates'] as $subPrivName => $subPrivilege) {
                $this->serializePriv($writer, $subPrivName, $subPrivilege);
            }
        }

        $writer->endElement(); // supported-privilege
    }
}
