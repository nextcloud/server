<?php

namespace Sabre\VObject\Property;

use Sabre\VObject\Parameter;
use Sabre\VObject\Property;

/**
 * URI property.
 *
 * This object encodes URI values. vCard 2.1 calls these URL.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Uri extends Text
{
    /**
     * In case this is a multi-value property. This string will be used as a
     * delimiter.
     *
     * @var string
     */
    public $delimiter = '';

    /**
     * Returns the type of value.
     *
     * This corresponds to the VALUE= parameter. Every property also has a
     * 'default' valueType.
     *
     * @return string
     */
    public function getValueType()
    {
        return 'URI';
    }

    /**
     * Returns an iterable list of children.
     *
     * @return array
     */
    public function parameters()
    {
        $parameters = parent::parameters();
        if (!isset($parameters['VALUE']) && in_array($this->name, ['URL', 'PHOTO'])) {
            // If we are encoding a URI value, and this URI value has no
            // VALUE=URI parameter, we add it anyway.
            //
            // This is not required by any spec, but both Apple iCal and Apple
            // AddressBook (at least in version 10.8) will trip over this if
            // this is not set, and so it improves compatibility.
            //
            // See Issue #227 and #235
            $parameters['VALUE'] = new Parameter($this->root, 'VALUE', 'URI');
        }

        return $parameters;
    }

    /**
     * Sets a raw value coming from a mimedir (iCalendar/vCard) file.
     *
     * This has been 'unfolded', so only 1 line will be passed. Unescaping is
     * not yet done, but parameters are not included.
     *
     * @param string $val
     */
    public function setRawMimeDirValue($val)
    {
        // Normally we don't need to do any type of unescaping for these
        // properties, however.. we've noticed that Google Contacts
        // specifically escapes the colon (:) with a backslash. While I have
        // no clue why they thought that was a good idea, I'm unescaping it
        // anyway.
        //
        // Good thing backslashes are not allowed in urls. Makes it easy to
        // assume that a backslash is always intended as an escape character.
        if ('URL' === $this->name) {
            $regex = '#  (?: (\\\\ (?: \\\\ | : ) ) ) #x';
            $matches = preg_split($regex, $val, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            $newVal = '';
            foreach ($matches as $match) {
                switch ($match) {
                    case '\:':
                        $newVal .= ':';
                        break;
                    default:
                        $newVal .= $match;
                        break;
                }
            }
            $this->value = $newVal;
        } else {
            $this->value = strtr($val, ['\,' => ',']);
        }
    }

    /**
     * Returns a raw mime-dir representation of the value.
     *
     * @return string
     */
    public function getRawMimeDirValue()
    {
        if (is_array($this->value)) {
            $value = $this->value[0];
        } else {
            $value = $this->value;
        }

        return strtr($value, [',' => '\,']);
    }
}
