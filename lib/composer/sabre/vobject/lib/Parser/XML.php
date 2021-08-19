<?php

namespace Sabre\VObject\Parser;

use Sabre\VObject\Component;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\EofException;
use Sabre\VObject\ParseException;
use Sabre\Xml as SabreXml;

/**
 * XML Parser.
 *
 * This parser parses both the xCal and xCard formats.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Ivan Enderlin
 * @license http://sabre.io/license/ Modified BSD License
 */
class XML extends Parser
{
    const XCAL_NAMESPACE = 'urn:ietf:params:xml:ns:icalendar-2.0';
    const XCARD_NAMESPACE = 'urn:ietf:params:xml:ns:vcard-4.0';

    /**
     * The input data.
     *
     * @var array
     */
    protected $input;

    /**
     * A pointer/reference to the input.
     *
     * @var array
     */
    private $pointer;

    /**
     * Document, root component.
     *
     * @var \Sabre\VObject\Document
     */
    protected $root;

    /**
     * Creates the parser.
     *
     * Optionally, it's possible to parse the input stream here.
     *
     * @param mixed $input
     * @param int   $options any parser options (OPTION constants)
     */
    public function __construct($input = null, $options = 0)
    {
        if (0 === $options) {
            $options = parent::OPTION_FORGIVING;
        }

        parent::__construct($input, $options);
    }

    /**
     * Parse xCal or xCard.
     *
     * @param resource|string $input
     * @param int             $options
     *
     * @throws \Exception
     *
     * @return \Sabre\VObject\Document
     */
    public function parse($input = null, $options = 0)
    {
        if (!is_null($input)) {
            $this->setInput($input);
        }

        if (0 !== $options) {
            $this->options = $options;
        }

        if (is_null($this->input)) {
            throw new EofException('End of input stream, or no input supplied');
        }

        switch ($this->input['name']) {
            case '{'.self::XCAL_NAMESPACE.'}icalendar':
                $this->root = new VCalendar([], false);
                $this->pointer = &$this->input['value'][0];
                $this->parseVCalendarComponents($this->root);
                break;

            case '{'.self::XCARD_NAMESPACE.'}vcards':
                foreach ($this->input['value'] as &$vCard) {
                    $this->root = new VCard(['version' => '4.0'], false);
                    $this->pointer = &$vCard;
                    $this->parseVCardComponents($this->root);

                    // We just parse the first <vcard /> element.
                    break;
                }
                break;

            default:
                throw new ParseException('Unsupported XML standard');
        }

        return $this->root;
    }

    /**
     * Parse a xCalendar component.
     */
    protected function parseVCalendarComponents(Component $parentComponent)
    {
        foreach ($this->pointer['value'] ?: [] as $children) {
            switch (static::getTagName($children['name'])) {
                case 'properties':
                    $this->pointer = &$children['value'];
                    $this->parseProperties($parentComponent);
                    break;

                case 'components':
                    $this->pointer = &$children;
                    $this->parseComponent($parentComponent);
                    break;
            }
        }
    }

    /**
     * Parse a xCard component.
     */
    protected function parseVCardComponents(Component $parentComponent)
    {
        $this->pointer = &$this->pointer['value'];
        $this->parseProperties($parentComponent);
    }

    /**
     * Parse xCalendar and xCard properties.
     *
     * @param string $propertyNamePrefix
     */
    protected function parseProperties(Component $parentComponent, $propertyNamePrefix = '')
    {
        foreach ($this->pointer ?: [] as $xmlProperty) {
            list($namespace, $tagName) = SabreXml\Service::parseClarkNotation($xmlProperty['name']);

            $propertyName = $tagName;
            $propertyValue = [];
            $propertyParameters = [];
            $propertyType = 'text';

            // A property which is not part of the standard.
            if (self::XCAL_NAMESPACE !== $namespace
                && self::XCARD_NAMESPACE !== $namespace) {
                $propertyName = 'xml';
                $value = '<'.$tagName.' xmlns="'.$namespace.'"';

                foreach ($xmlProperty['attributes'] as $attributeName => $attributeValue) {
                    $value .= ' '.$attributeName.'="'.str_replace('"', '\"', $attributeValue).'"';
                }

                $value .= '>'.$xmlProperty['value'].'</'.$tagName.'>';

                $propertyValue = [$value];

                $this->createProperty(
                    $parentComponent,
                    $propertyName,
                    $propertyParameters,
                    $propertyType,
                    $propertyValue
                );

                continue;
            }

            // xCard group.
            if ('group' === $propertyName) {
                if (!isset($xmlProperty['attributes']['name'])) {
                    continue;
                }

                $this->pointer = &$xmlProperty['value'];
                $this->parseProperties(
                    $parentComponent,
                    strtoupper($xmlProperty['attributes']['name']).'.'
                );

                continue;
            }

            // Collect parameters.
            foreach ($xmlProperty['value'] as $i => $xmlPropertyChild) {
                if (!is_array($xmlPropertyChild)
                    || 'parameters' !== static::getTagName($xmlPropertyChild['name'])) {
                    continue;
                }

                $xmlParameters = $xmlPropertyChild['value'];

                foreach ($xmlParameters as $xmlParameter) {
                    $propertyParameterValues = [];

                    foreach ($xmlParameter['value'] as $xmlParameterValues) {
                        $propertyParameterValues[] = $xmlParameterValues['value'];
                    }

                    $propertyParameters[static::getTagName($xmlParameter['name'])]
                        = implode(',', $propertyParameterValues);
                }

                array_splice($xmlProperty['value'], $i, 1);
            }

            $propertyNameExtended = ($this->root instanceof VCalendar
                                      ? 'xcal'
                                      : 'xcard').':'.$propertyName;

            switch ($propertyNameExtended) {
                case 'xcal:geo':
                    $propertyType = 'float';
                    $propertyValue['latitude'] = 0;
                    $propertyValue['longitude'] = 0;

                    foreach ($xmlProperty['value'] as $xmlRequestChild) {
                        $propertyValue[static::getTagName($xmlRequestChild['name'])]
                            = $xmlRequestChild['value'];
                    }
                    break;

                case 'xcal:request-status':
                    $propertyType = 'text';

                    foreach ($xmlProperty['value'] as $xmlRequestChild) {
                        $propertyValue[static::getTagName($xmlRequestChild['name'])]
                            = $xmlRequestChild['value'];
                    }
                    break;

                case 'xcal:freebusy':
                    $propertyType = 'freebusy';
                    // We don't break because we only want to set
                    // another property type.

                    // no break
                case 'xcal:categories':
                case 'xcal:resources':
                case 'xcal:exdate':
                    foreach ($xmlProperty['value'] as $specialChild) {
                        $propertyValue[static::getTagName($specialChild['name'])]
                            = $specialChild['value'];
                    }
                    break;

                case 'xcal:rdate':
                    $propertyType = 'date-time';

                    foreach ($xmlProperty['value'] as $specialChild) {
                        $tagName = static::getTagName($specialChild['name']);

                        if ('period' === $tagName) {
                            $propertyParameters['value'] = 'PERIOD';
                            $propertyValue[] = implode('/', $specialChild['value']);
                        } else {
                            $propertyValue[] = $specialChild['value'];
                        }
                    }
                    break;

                default:
                    $propertyType = static::getTagName($xmlProperty['value'][0]['name']);

                    foreach ($xmlProperty['value'] as $value) {
                        $propertyValue[] = $value['value'];
                    }

                    if ('date' === $propertyType) {
                        $propertyParameters['value'] = 'DATE';
                    }
                    break;
            }

            $this->createProperty(
                $parentComponent,
                $propertyNamePrefix.$propertyName,
                $propertyParameters,
                $propertyType,
                $propertyValue
            );
        }
    }

    /**
     * Parse a component.
     */
    protected function parseComponent(Component $parentComponent)
    {
        $components = $this->pointer['value'] ?: [];

        foreach ($components as $component) {
            $componentName = static::getTagName($component['name']);
            $currentComponent = $this->root->createComponent(
                $componentName,
                null,
                false
            );

            $this->pointer = &$component;
            $this->parseVCalendarComponents($currentComponent);

            $parentComponent->add($currentComponent);
        }
    }

    /**
     * Create a property.
     *
     * @param string $name
     * @param array  $parameters
     * @param string $type
     * @param mixed  $value
     */
    protected function createProperty(Component $parentComponent, $name, $parameters, $type, $value)
    {
        $property = $this->root->createProperty(
            $name,
            null,
            $parameters,
            $type
        );
        $parentComponent->add($property);
        $property->setXmlValue($value);
    }

    /**
     * Sets the input data.
     *
     * @param resource|string $input
     */
    public function setInput($input)
    {
        if (is_resource($input)) {
            $input = stream_get_contents($input);
        }

        if (is_string($input)) {
            $reader = new SabreXml\Reader();
            $reader->elementMap['{'.self::XCAL_NAMESPACE.'}period']
                = XML\Element\KeyValue::class;
            $reader->elementMap['{'.self::XCAL_NAMESPACE.'}recur']
                = XML\Element\KeyValue::class;
            $reader->xml($input);
            $input = $reader->parse();
        }

        $this->input = $input;
    }

    /**
     * Get tag name from a Clark notation.
     *
     * @param string $clarkedTagName
     *
     * @return string
     */
    protected static function getTagName($clarkedTagName)
    {
        list(, $tagName) = SabreXml\Service::parseClarkNotation($clarkedTagName);

        return $tagName;
    }
}
