<?php

/*
* (c) Andreas Fischer <git@andreasfischer.net>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace bantu\IniGetWrapper;

/**
* Wrapper class around built-in ini_get() function.
*
* Provides easier handling of the different interpretations of ini values.
*/
class IniGetWrapper
{
    /**
    * Simple wrapper around ini_get()
    * See http://php.net/manual/en/function.ini-get.php
    *
    * @param string $varname  The configuration option name.
    * @return null|string     Null if configuration option does not exist.
    *                         The configuration option value (string) otherwise.
    */
    public function get($varname)
    {
        $value = $this->getPhp($varname);
        return $value === false ? null : $value;
    }

    /**
    * Gets the configuration option value as a trimmed string.
    *
    * @param string $varname  The configuration option name.
    * @return null|string     Null if configuration option does not exist.
    *                         The configuration option value (string) otherwise.
    */
    public function getString($varname)
    {
        $value = $this->get($varname);
        return $value === null ? null : trim($value);
    }

    /**
    * Gets configuration option value as a boolean.
    * Interprets the string value 'off' as false.
    *
    * @param string $varname  The configuration option name.
    * @return null|bool       Null if configuration option does not exist.
    *                         False if configuration option is disabled.
    *                         True otherwise.
    */
    public function getBool($varname)
    {
        $value = $this->getString($varname);
        return $value === null ? null : $value && strtolower($value) !== 'off';
    }

    /**
    * Gets configuration option value as an integer.
    *
    * @param string $varname  The configuration option name.
    * @return null|int|float  Null if configuration option does not exist or is not numeric.
    *                         The configuration option value (integer or float) otherwise.
    */
    public function getNumeric($varname)
    {
        $value = $this->getString($varname);
        return is_numeric($value) ? $value + 0 : null;
    }

    /**
    * Gets configuration option value in bytes.
    * Converts strings like '128M' to bytes (integer or float).
    *
    * @param string $varname  The configuration option name.
    * @return null|int|float  Null if configuration option does not exist or is not well-formed.
    *                         The configuration option value as bytes (integer or float) otherwise.
    */
    public function getBytes($varname)
    {
        $value = $this->getString($varname);

        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            // Already in bytes.
            return $value + 0;
        }

        if (strlen($value) < 2 || strlen($value) < 3 && $value[0] === '-') {
            // Either a single character
            // or two characters where the first one is a minus.
            return null;
        }

        // Split string into numeric value and unit.
        $value_numeric = substr($value, 0, -1);
        if (!is_numeric($value_numeric)) {
            return null;
        }

        switch (strtolower($value[strlen($value) - 1])) {
            case 'g':
                $value_numeric *= 1024;
                // no break
            case 'm':
                $value_numeric *= 1024;
                // no break
            case 'k':
                $value_numeric *= 1024;
                break;

            default:
                // It's not already in bytes (and thus numeric)
                // and does not carry a unit.
                return null;
        }

        return $value_numeric;
    }

    /**
    * Gets configuration option value as a list (array).
    * Converts comma-separated string into list (array).
    *
    * @param string $varname  The configuration option name.
    * @return null|array      Null if configuration option does not exist.
    *                         The configuration option value as a list (array) otherwise.
    */
    public function getList($varname)
    {
        $value = $this->getString($varname);
        return $value === null ? null : explode(',', $value);
    }

    /**
    * Checks whether a list contains a given element (string).
    *
    * @param string $varname  The configuration option name.
    * @param string $needle   The element to check whether it is contained in the list.
    * @return null|bool       Null if configuration option does not exist.
    *                         Whether $needle is contained in the list otherwise.
    */
    public function listContains($varname, $needle)
    {
        $list = $this->getList($varname);
        return $list === null ? null : in_array($needle, $list, true);
    }

    /**
    * @param string $varname  The configuration option name.
    */
    protected function getPhp($varname)
    {
        return ini_get($varname);
    }
}
