<?php

declare(strict_types=1);

namespace Sabre\DAVACL\Xml\Request;

use Sabre\DAV\Exception\BadRequest;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

/**
 * PrincipalSearchPropertySetReport request parser.
 *
 * This class parses the {DAV:}principal-property-search REPORT, as defined
 * in:
 *
 * https://tools.ietf.org/html/rfc3744#section-9.4
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class PrincipalPropertySearchReport implements XmlDeserializable
{
    /**
     * The requested properties.
     *
     * @var array|null
     */
    public $properties;

    /**
     * searchProperties.
     *
     * @var array
     */
    public $searchProperties = [];

    /**
     * By default the property search will be conducted on the url of the http
     * request. If this is set to true, it will be applied to the principal
     * collection set instead.
     *
     * @var bool
     */
    public $applyToPrincipalCollectionSet = false;

    /**
     * Search for principals matching ANY of the properties (OR) or a ALL of
     * the properties (AND).
     *
     * This property is either "anyof" or "allof".
     *
     * @var string
     */
    public $test;

    /**
     * The deserialize method is called during xml parsing.
     *
     * This method is called statically, this is because in theory this method
     * may be used as a type of constructor, or factory method.
     *
     * Often you want to return an instance of the current class, but you are
     * free to return other data as well.
     *
     * You are responsible for advancing the reader to the next element. Not
     * doing anything will result in a never-ending loop.
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
        $self = new self();

        $foundSearchProp = false;
        $self->test = 'allof';
        if ('anyof' === $reader->getAttribute('test')) {
            $self->test = 'anyof';
        }

        $elemMap = [
            '{DAV:}property-search' => 'Sabre\\Xml\\Element\\KeyValue',
            '{DAV:}prop' => 'Sabre\\Xml\\Element\\KeyValue',
        ];

        foreach ($reader->parseInnerTree($elemMap) as $elem) {
            switch ($elem['name']) {
                case '{DAV:}prop':
                    $self->properties = array_keys($elem['value']);
                    break;
                case '{DAV:}property-search':
                    $foundSearchProp = true;
                    // This property has two sub-elements:
                    //   {DAV:}prop - The property to be searched on. This may
                    //                also be more than one
                    //   {DAV:}match - The value to match with
                    if (!isset($elem['value']['{DAV:}prop']) || !isset($elem['value']['{DAV:}match'])) {
                        throw new BadRequest('The {DAV:}property-search element must contain one {DAV:}match and one {DAV:}prop element');
                    }
                    foreach ($elem['value']['{DAV:}prop'] as $propName => $discard) {
                        $self->searchProperties[$propName] = $elem['value']['{DAV:}match'];
                    }
                    break;
                case '{DAV:}apply-to-principal-collection-set':
                    $self->applyToPrincipalCollectionSet = true;
                    break;
            }
        }
        if (!$foundSearchProp) {
            throw new BadRequest('The {DAV:}principal-property-search report must contain at least 1 {DAV:}property-search element');
        }

        return $self;
    }
}
