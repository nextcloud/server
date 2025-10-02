<?php

namespace Sabre\VObject\Component;

use Sabre\VObject;
use Sabre\Xml;

/**
 * The VCard component.
 *
 * This component represents the BEGIN:VCARD and END:VCARD found in every
 * vcard.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class VCard extends VObject\Document
{
    /**
     * The default name for this component.
     *
     * This should be 'VCALENDAR' or 'VCARD'.
     *
     * @var string
     */
    public static $defaultName = 'VCARD';

    /**
     * Caching the version number.
     *
     * @var int
     */
    private $version = null;

    /**
     * This is a list of components, and which classes they should map to.
     *
     * @var array
     */
    public static $componentMap = [
        'VCARD' => VCard::class,
    ];

    /**
     * List of value-types, and which classes they map to.
     *
     * @var array
     */
    public static $valueMap = [
        'BINARY' => VObject\Property\Binary::class,
        'BOOLEAN' => VObject\Property\Boolean::class,
        'CONTENT-ID' => VObject\Property\FlatText::class,   // vCard 2.1 only
        'DATE' => VObject\Property\VCard\Date::class,
        'DATE-TIME' => VObject\Property\VCard\DateTime::class,
        'DATE-AND-OR-TIME' => VObject\Property\VCard\DateAndOrTime::class, // vCard only
        'FLOAT' => VObject\Property\FloatValue::class,
        'INTEGER' => VObject\Property\IntegerValue::class,
        'LANGUAGE-TAG' => VObject\Property\VCard\LanguageTag::class,
        'PHONE-NUMBER' => VObject\Property\VCard\PhoneNumber::class, // vCard 3.0 only
        'TIMESTAMP' => VObject\Property\VCard\TimeStamp::class,
        'TEXT' => VObject\Property\Text::class,
        'TIME' => VObject\Property\Time::class,
        'UNKNOWN' => VObject\Property\Unknown::class, // jCard / jCal-only.
        'URI' => VObject\Property\Uri::class,
        'URL' => VObject\Property\Uri::class, // vCard 2.1 only
        'UTC-OFFSET' => VObject\Property\UtcOffset::class,
    ];

    /**
     * List of properties, and which classes they map to.
     *
     * @var array
     */
    public static $propertyMap = [
        // vCard 2.1 properties and up
        'N' => VObject\Property\Text::class,
        'FN' => VObject\Property\FlatText::class,
        'PHOTO' => VObject\Property\Binary::class,
        'BDAY' => VObject\Property\VCard\DateAndOrTime::class,
        'ADR' => VObject\Property\Text::class,
        'LABEL' => VObject\Property\FlatText::class, // Removed in vCard 4.0
        'TEL' => VObject\Property\FlatText::class,
        'EMAIL' => VObject\Property\FlatText::class,
        'MAILER' => VObject\Property\FlatText::class, // Removed in vCard 4.0
        'GEO' => VObject\Property\FlatText::class,
        'TITLE' => VObject\Property\FlatText::class,
        'ROLE' => VObject\Property\FlatText::class,
        'LOGO' => VObject\Property\Binary::class,
        // 'AGENT'   => 'Sabre\\VObject\\Property\\',      // Todo: is an embedded vCard. Probably rare, so
                                 // not supported at the moment
        'ORG' => VObject\Property\Text::class,
        'NOTE' => VObject\Property\FlatText::class,
        'REV' => VObject\Property\VCard\TimeStamp::class,
        'SOUND' => VObject\Property\FlatText::class,
        'URL' => VObject\Property\Uri::class,
        'UID' => VObject\Property\FlatText::class,
        'VERSION' => VObject\Property\FlatText::class,
        'KEY' => VObject\Property\FlatText::class,
        'TZ' => VObject\Property\Text::class,

        // vCard 3.0 properties
        'CATEGORIES' => VObject\Property\Text::class,
        'SORT-STRING' => VObject\Property\FlatText::class,
        'PRODID' => VObject\Property\FlatText::class,
        'NICKNAME' => VObject\Property\Text::class,
        'CLASS' => VObject\Property\FlatText::class, // Removed in vCard 4.0

        // rfc2739 properties
        'FBURL' => VObject\Property\Uri::class,
        'CAPURI' => VObject\Property\Uri::class,
        'CALURI' => VObject\Property\Uri::class,
        'CALADRURI' => VObject\Property\Uri::class,

        // rfc4770 properties
        'IMPP' => VObject\Property\Uri::class,

        // vCard 4.0 properties
        'SOURCE' => VObject\Property\Uri::class,
        'XML' => VObject\Property\FlatText::class,
        'ANNIVERSARY' => VObject\Property\VCard\DateAndOrTime::class,
        'CLIENTPIDMAP' => VObject\Property\Text::class,
        'LANG' => VObject\Property\VCard\LanguageTag::class,
        'GENDER' => VObject\Property\Text::class,
        'KIND' => VObject\Property\FlatText::class,
        'MEMBER' => VObject\Property\Uri::class,
        'RELATED' => VObject\Property\Uri::class,

        // rfc6474 properties
        'BIRTHPLACE' => VObject\Property\FlatText::class,
        'DEATHPLACE' => VObject\Property\FlatText::class,
        'DEATHDATE' => VObject\Property\VCard\DateAndOrTime::class,

        // rfc6715 properties
        'EXPERTISE' => VObject\Property\FlatText::class,
        'HOBBY' => VObject\Property\FlatText::class,
        'INTEREST' => VObject\Property\FlatText::class,
        'ORG-DIRECTORY' => VObject\Property\FlatText::class,
    ];

    /**
     * Returns the current document type.
     *
     * @return int
     */
    public function getDocumentType()
    {
        if (!$this->version) {
            $version = (string) $this->VERSION;

            switch ($version) {
                case '2.1':
                    $this->version = self::VCARD21;
                    break;
                case '3.0':
                    $this->version = self::VCARD30;
                    break;
                case '4.0':
                    $this->version = self::VCARD40;
                    break;
                default:
                    // We don't want to cache the version if it's unknown,
                    // because we might get a version property in a bit.
                    return self::UNKNOWN;
            }
        }

        return $this->version;
    }

    /**
     * Converts the document to a different vcard version.
     *
     * Use one of the VCARD constants for the target. This method will return
     * a copy of the vcard in the new version.
     *
     * At the moment the only supported conversion is from 3.0 to 4.0.
     *
     * If input and output version are identical, a clone is returned.
     *
     * @param int $target
     *
     * @return VCard
     */
    public function convert($target)
    {
        $converter = new VObject\VCardConverter();

        return $converter->convert($this, $target);
    }

    /**
     * VCards with version 2.1, 3.0 and 4.0 are found.
     *
     * If the VCARD doesn't know its version, 2.1 is assumed.
     */
    const DEFAULT_VERSION = self::VCARD21;

    /**
     * Validates the node for correctness.
     *
     * The following options are supported:
     *   Node::REPAIR - May attempt to automatically repair the problem.
     *
     * This method returns an array with detected problems.
     * Every element has the following properties:
     *
     *  * level - problem level.
     *  * message - A human-readable string describing the issue.
     *  * node - A reference to the problematic node.
     *
     * The level means:
     *   1 - The issue was repaired (only happens if REPAIR was turned on)
     *   2 - An inconsequential issue
     *   3 - A severe issue.
     *
     * @param int $options
     *
     * @return array
     */
    public function validate($options = 0)
    {
        $warnings = [];

        $versionMap = [
            self::VCARD21 => '2.1',
            self::VCARD30 => '3.0',
            self::VCARD40 => '4.0',
        ];

        $version = $this->select('VERSION');
        if (1 === count($version)) {
            $version = (string) $this->VERSION;
            if ('2.1' !== $version && '3.0' !== $version && '4.0' !== $version) {
                $warnings[] = [
                    'level' => 3,
                    'message' => 'Only vcard version 4.0 (RFC6350), version 3.0 (RFC2426) or version 2.1 (icm-vcard-2.1) are supported.',
                    'node' => $this,
                ];
                if ($options & self::REPAIR) {
                    $this->VERSION = $versionMap[self::DEFAULT_VERSION];
                }
            }
            if ('2.1' === $version && ($options & self::PROFILE_CARDDAV)) {
                $warnings[] = [
                    'level' => 3,
                    'message' => 'CardDAV servers are not allowed to accept vCard 2.1.',
                    'node' => $this,
                ];
            }
        }
        $uid = $this->select('UID');
        if (0 === count($uid)) {
            if ($options & self::PROFILE_CARDDAV) {
                // Required for CardDAV
                $warningLevel = 3;
                $message = 'vCards on CardDAV servers MUST have a UID property.';
            } else {
                // Not required for regular vcards
                $warningLevel = 2;
                $message = 'Adding a UID to a vCard property is recommended.';
            }
            if ($options & self::REPAIR) {
                $this->UID = VObject\UUIDUtil::getUUID();
                $warningLevel = 1;
            }
            $warnings[] = [
                'level' => $warningLevel,
                'message' => $message,
                'node' => $this,
            ];
        }

        $fn = $this->select('FN');
        if (1 !== count($fn)) {
            $repaired = false;
            if (($options & self::REPAIR) && 0 === count($fn)) {
                // We're going to try to see if we can use the contents of the
                // N property.
                if (isset($this->N)) {
                    $value = explode(';', (string) $this->N);
                    if (isset($value[1]) && $value[1]) {
                        $this->FN = $value[1].' '.$value[0];
                    } else {
                        $this->FN = $value[0];
                    }
                    $repaired = true;

                // Otherwise, the ORG property may work
                } elseif (isset($this->ORG)) {
                    $this->FN = (string) $this->ORG;
                    $repaired = true;

                // Otherwise, the NICKNAME property may work
                } elseif (isset($this->NICKNAME)) {
                    $this->FN = (string) $this->NICKNAME;
                    $repaired = true;

                // Otherwise, the EMAIL property may work
                } elseif (isset($this->EMAIL)) {
                    $this->FN = (string) $this->EMAIL;
                    $repaired = true;
                }
            }
            $warnings[] = [
                'level' => $repaired ? 1 : 3,
                'message' => 'The FN property must appear in the VCARD component exactly 1 time',
                'node' => $this,
            ];
        }

        return array_merge(
            parent::validate($options),
            $warnings
        );
    }

    /**
     * A simple list of validation rules.
     *
     * This is simply a list of properties, and how many times they either
     * must or must not appear.
     *
     * Possible values per property:
     *   * 0 - Must not appear.
     *   * 1 - Must appear exactly once.
     *   * + - Must appear at least once.
     *   * * - Can appear any number of times.
     *   * ? - May appear, but not more than once.
     *
     * @var array
     */
    public function getValidationRules()
    {
        return [
            'ADR' => '*',
            'ANNIVERSARY' => '?',
            'BDAY' => '?',
            'CALADRURI' => '*',
            'CALURI' => '*',
            'CATEGORIES' => '*',
            'CLIENTPIDMAP' => '*',
            'EMAIL' => '*',
            'FBURL' => '*',
            'IMPP' => '*',
            'GENDER' => '?',
            'GEO' => '*',
            'KEY' => '*',
            'KIND' => '?',
            'LANG' => '*',
            'LOGO' => '*',
            'MEMBER' => '*',
            'N' => '?',
            'NICKNAME' => '*',
            'NOTE' => '*',
            'ORG' => '*',
            'PHOTO' => '*',
            'PRODID' => '?',
            'RELATED' => '*',
            'REV' => '?',
            'ROLE' => '*',
            'SOUND' => '*',
            'SOURCE' => '*',
            'TEL' => '*',
            'TITLE' => '*',
            'TZ' => '*',
            'URL' => '*',
            'VERSION' => '1',
            'XML' => '*',

            // FN is commented out, because it's already handled by the
            // validate function, which may also try to repair it.
            // 'FN'           => '+',
            'UID' => '?',
        ];
    }

    /**
     * Returns a preferred field.
     *
     * VCards can indicate whether a field such as ADR, TEL or EMAIL is
     * preferred by specifying TYPE=PREF (vcard 2.1, 3) or PREF=x (vcard 4, x
     * being a number between 1 and 100).
     *
     * If neither of those parameters are specified, the first is returned, if
     * a field with that name does not exist, null is returned.
     *
     * @param string $fieldName
     *
     * @return VObject\Property|null
     */
    public function preferred($propertyName)
    {
        $preferred = null;
        $lastPref = 101;
        foreach ($this->select($propertyName) as $field) {
            $pref = 101;
            if (isset($field['TYPE']) && $field['TYPE']->has('PREF')) {
                $pref = 1;
            } elseif (isset($field['PREF'])) {
                $pref = $field['PREF']->getValue();
            }

            if ($pref < $lastPref || is_null($preferred)) {
                $preferred = $field;
                $lastPref = $pref;
            }
        }

        return $preferred;
    }

    /**
     * Returns a property with a specific TYPE value (ADR, TEL, or EMAIL).
     *
     * This function will return null if the property does not exist. If there are
     * multiple properties with the same TYPE value, only one will be returned.
     *
     * @param string $propertyName
     * @param string $type
     *
     * @return VObject\Property|null
     */
    public function getByType($propertyName, $type)
    {
        foreach ($this->select($propertyName) as $field) {
            if (isset($field['TYPE']) && $field['TYPE']->has($type)) {
                return $field;
            }
        }
    }

    /**
     * This method should return a list of default property values.
     *
     * @return array
     */
    protected function getDefaults()
    {
        return [
            'VERSION' => '4.0',
            'PRODID' => '-//Sabre//Sabre VObject '.VObject\Version::VERSION.'//EN',
            'UID' => 'sabre-vobject-'.VObject\UUIDUtil::getUUID(),
        ];
    }

    /**
     * This method returns an array, with the representation as it should be
     * encoded in json. This is used to create jCard or jCal documents.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        // A vcard does not have sub-components, so we're overriding this
        // method to remove that array element.
        $properties = [];

        foreach ($this->children() as $child) {
            $properties[] = $child->jsonSerialize();
        }

        return [
            strtolower($this->name),
            $properties,
        ];
    }

    /**
     * This method serializes the data into XML. This is used to create xCard or
     * xCal documents.
     *
     * @param Xml\Writer $writer XML writer
     */
    public function xmlSerialize(Xml\Writer $writer): void
    {
        $propertiesByGroup = [];

        foreach ($this->children() as $property) {
            $group = $property->group;

            if (!isset($propertiesByGroup[$group])) {
                $propertiesByGroup[$group] = [];
            }

            $propertiesByGroup[$group][] = $property;
        }

        $writer->startElement(strtolower($this->name));

        foreach ($propertiesByGroup as $group => $properties) {
            if (!empty($group)) {
                $writer->startElement('group');
                $writer->writeAttribute('name', strtolower($group));
            }

            foreach ($properties as $property) {
                switch ($property->name) {
                    case 'VERSION':
                        break;

                    case 'XML':
                        $value = $property->getParts();
                        $fragment = new Xml\Element\XmlFragment($value[0]);
                        $writer->write($fragment);
                        break;

                    default:
                        $property->xmlSerialize($writer);
                        break;
                }
            }

            if (!empty($group)) {
                $writer->endElement();
            }
        }

        $writer->endElement();
    }

    /**
     * Returns the default class for a property name.
     *
     * @param string $propertyName
     *
     * @return string
     */
    public function getClassNameForPropertyName($propertyName)
    {
        $className = parent::getClassNameForPropertyName($propertyName);

        // In vCard 4, BINARY no longer exists, and we need URI instead.
        if (VObject\Property\Binary::class == $className && self::VCARD40 === $this->getDocumentType()) {
            return VObject\Property\Uri::class;
        }

        return $className;
    }
}
