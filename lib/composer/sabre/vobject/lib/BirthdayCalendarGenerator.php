<?php

namespace Sabre\VObject;

use Sabre\VObject\Component\VCalendar;

/**
 * This class generates birthday calendars.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Dominik Tobschall (http://tobschall.de/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class BirthdayCalendarGenerator
{
    /**
     * Input objects.
     *
     * @var array
     */
    protected $objects = [];

    /**
     * Default year.
     * Used for dates without a year.
     */
    const DEFAULT_YEAR = 2000;

    /**
     * Output format for the SUMMARY.
     *
     * @var string
     */
    protected $format = '%1$s\'s Birthday';

    /**
     * Creates the generator.
     *
     * Check the setTimeRange and setObjects methods for details about the
     * arguments.
     *
     * @param mixed $objects
     */
    public function __construct($objects = null)
    {
        if ($objects) {
            $this->setObjects($objects);
        }
    }

    /**
     * Sets the input objects.
     *
     * You must either supply a vCard as a string or as a Component/VCard object.
     * It's also possible to supply an array of strings or objects.
     *
     * @param mixed $objects
     */
    public function setObjects($objects)
    {
        if (!is_array($objects)) {
            $objects = [$objects];
        }

        $this->objects = [];
        foreach ($objects as $object) {
            if (is_string($object)) {
                $vObj = Reader::read($object);
                if (!$vObj instanceof Component\VCard) {
                    throw new \InvalidArgumentException('String could not be parsed as \\Sabre\\VObject\\Component\\VCard by setObjects');
                }

                $this->objects[] = $vObj;
            } elseif ($object instanceof Component\VCard) {
                $this->objects[] = $object;
            } else {
                throw new \InvalidArgumentException('You can only pass strings or \\Sabre\\VObject\\Component\\VCard arguments to setObjects');
            }
        }
    }

    /**
     * Sets the output format for the SUMMARY.
     *
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * Parses the input data and returns a VCALENDAR.
     *
     * @return Component/VCalendar
     */
    public function getResult()
    {
        $calendar = new VCalendar();

        foreach ($this->objects as $object) {
            // Skip if there is no BDAY property.
            if (!$object->select('BDAY')) {
                continue;
            }

            // We've seen clients (ez-vcard) putting "BDAY:" properties
            // without a value into vCards. If we come across those, we'll
            // skip them.
            if (empty($object->BDAY->getValue())) {
                continue;
            }

            // We're always converting to vCard 4.0 so we can rely on the
            // VCardConverter handling the X-APPLE-OMIT-YEAR property for us.
            $object = $object->convert(Document::VCARD40);

            // Skip if the card has no FN property.
            if (!isset($object->FN)) {
                continue;
            }

            // Skip if the BDAY property is not of the right type.
            if (!$object->BDAY instanceof Property\VCard\DateAndOrTime) {
                continue;
            }

            // Skip if we can't parse the BDAY value.
            try {
                $dateParts = DateTimeParser::parseVCardDateTime($object->BDAY->getValue());
            } catch (InvalidDataException $e) {
                continue;
            }

            // Set a year if it's not set.
            $unknownYear = false;

            if (!$dateParts['year']) {
                $object->BDAY = self::DEFAULT_YEAR.'-'.$dateParts['month'].'-'.$dateParts['date'];

                $unknownYear = true;
            }

            // Create event.
            $event = $calendar->add('VEVENT', [
                'SUMMARY' => sprintf($this->format, $object->FN->getValue()),
                'DTSTART' => new \DateTime($object->BDAY->getValue()),
                'RRULE' => 'FREQ=YEARLY',
                'TRANSP' => 'TRANSPARENT',
            ]);

            // add VALUE=date
            $event->DTSTART['VALUE'] = 'DATE';

            // Add X-SABRE-BDAY property.
            if ($unknownYear) {
                $event->add('X-SABRE-BDAY', 'BDAY', [
                    'X-SABRE-VCARD-UID' => $object->UID->getValue(),
                    'X-SABRE-VCARD-FN' => $object->FN->getValue(),
                    'X-SABRE-OMIT-YEAR' => self::DEFAULT_YEAR,
                ]);
            } else {
                $event->add('X-SABRE-BDAY', 'BDAY', [
                    'X-SABRE-VCARD-UID' => $object->UID->getValue(),
                    'X-SABRE-VCARD-FN' => $object->FN->getValue(),
                ]);
            }
        }

        return $calendar;
    }
}
