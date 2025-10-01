<?php

namespace Sabre\VObject;

use Sabre\Xml;

/**
 * iCalendar/vCard/jCal/jCard/xCal/xCard writer object.
 *
 * This object provides a few (static) convenience methods to quickly access
 * the serializers.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Ivan Enderlin
 * @license http://sabre.io/license/ Modified BSD License
 */
class Writer
{
    /**
     * Serializes a vCard or iCalendar object.
     *
     * @return string
     */
    public static function write(Component $component)
    {
        return $component->serialize();
    }

    /**
     * Serializes a jCal or jCard object.
     *
     * @param int $options
     *
     * @return string
     */
    public static function writeJson(Component $component, $options = 0)
    {
        return json_encode($component, $options);
    }

    /**
     * Serializes a xCal or xCard object.
     *
     * @return string
     */
    public static function writeXml(Component $component)
    {
        $writer = new Xml\Writer();
        $writer->openMemory();
        $writer->setIndent(true);

        $writer->startDocument('1.0', 'utf-8');

        if ($component instanceof Component\VCalendar) {
            $writer->startElement('icalendar');
            $writer->writeAttribute('xmlns', Parser\XML::XCAL_NAMESPACE);
        } else {
            $writer->startElement('vcards');
            $writer->writeAttribute('xmlns', Parser\XML::XCARD_NAMESPACE);
        }

        $component->xmlSerialize($writer);

        $writer->endElement();

        return $writer->outputMemory();
    }
}
