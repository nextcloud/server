<?php

namespace Punic;

/**
 * Currency-related stuff.
 */
class Currency
{
    /**
     * Returns all the currencies.
     *
     * @param bool $alsoUnused Set to true to receive also currencies not currently used by any country, false otherwise
     * @param bool $alsoNotTender Set to true to receive also currencies that aren't legal tender in any country
     * @param string $locale The locale to use. If empty we'll use the default locale set with {@link \Punic\Data::setDefaultLocale()}.
     *
     * @return array Array keys are the currency code, array values are the currency name. It's sorted by currency values
     */
    public static function getAllCurrencies($alsoUnused = false, $alsoNotTender = false, $locale = '')
    {
        $result = array();
        foreach (Data::get('currencies', $locale) as $code => $info) {
            $result[$code] = $info['name'];
        }
        if ((!$alsoUnused) || (!$alsoNotTender)) {
            $data = Data::getGeneric('currencyData');
            $usedCurrencies = array();
            $tenderCurrencies = array();
            foreach ($data['regions'] as $usages) {
                foreach ($usages as $usage) {
                    if (!isset($usage['to'])) {
                        $usedCurrencies[$usage['currency']] = true;
                    }
                    if ((!isset($usage['notTender'])) || (!$usage['notTender'])) {
                        $tenderCurrencies[$usage['currency']] = true;
                    }
                }
            }
            if (!$alsoUnused) {
                $result = array_intersect_key($result, $usedCurrencies);
            }
            if (!$alsoNotTender) {
                $result = array_intersect_key($result, $tenderCurrencies);
            }
        }
        $sorter = new \Punic\Comparer();
        $sorter->sort($result, true);

        return $result;
    }

    /**
     * Returns the name of a currency given its code.
     *
     * @param string $currencyCode The currency code
     * @param null|number|string The quantity identifier. Allowed values:
     * <ul>
     *     <li>`null` to return the standard name, not associated to any quantity</li>
     *     <li>`number` to return the name following the plural rule for the specified quantity</li>
     *     <li>string `'zero'|'one'|'two'|'few'|'many'|'other'` the plural rule
     * </ul>
     * @param string $locale The locale to use. If empty we'll use the default locale set with {@link \Punic\Data::setDefaultLocale()}.
     *
     * @return string Returns an empty string if $currencyCode is not valid, the localized currency name otherwise
     */
    public static function getName($currencyCode, $quantity = null, $locale = '')
    {
        $result = '';
        $data = static::getLocaleData($currencyCode, $locale);
        if (is_array($data)) {
            $result = $data['name'];
            if (($quantity !== null) && isset($data['pluralName'])) {
                if (is_string($data) && in_array($quantity, array('zero', 'one', 'two', 'few', 'many', 'other'))) {
                    $pluralRule = $quantity;
                } else {
                    $pluralRule = Plural::getRule($quantity, $locale);
                }
                if (!isset($data['pluralName'][$pluralRule])) {
                    $pluralRule = 'other';
                }
                $result = $data['pluralName'][$pluralRule];
            }
        }

        return $result;
    }

    /**
     * Returns the name of a currency given its code.
     *
     * @param string $currencyCode The currency code
     * @param string $which Which symbol flavor do you prefer? 'narrow' for narrow symbols, 'alt' for alternative. Other values: standard/default symbol
     * @param string $locale The locale to use. If empty we'll use the default locale set with {@link \Punic\Data::setDefaultLocale()}.
     *
     * @return string Returns an empty string if $currencyCode is not valid, the localized currency name otherwise
     */
    public static function getSymbol($currencyCode, $which = '', $locale = '')
    {
        $result = '';
        $data = static::getLocaleData($currencyCode, $locale);
        if (is_array($data)) {
            switch ($which) {
                case 'narrow':
                    if (isset($data['symbolNarrow'])) {
                        $result = $data['symbolNarrow'];
                    }

                    break;
                case 'alt':
                    if (isset($data['symbolAlt'])) {
                        $result = $data['symbolAlt'];
                    }
                    break;
            }
            if ((strlen($result) === 0) && ($which !== 'alt')) {
                if (isset($data['symbol'])) {
                    $result = $data['symbol'];
                }
                if (!isset($result[0])) {
                    $result = $currencyCode;
                }
            }
        }

        return $result;
    }

    /**
     * Return the history for the currencies used in a territory.
     *
     * @param string $territoryCode The territoy code
     *
     * @return array Return a list of items with these keys:
     * <ul>
     *     <li>string `currency`: the currency code (always present)</li>
     *     <li>string `from`: start date of the currency validity in the territory (not present if no start date) - Format is YYYY-MM-DD</li>
     *     <li>string `to`: end date of the currency validity in the territory (not present if no end date) - Format is YYYY-MM-DD</li>
     *     <li>bool `tender`: true if the currency was or is legal tender, false otherwise (always present)</li>
     * </ul>
     */
    public static function getCurrencyHistoryForTerritory($territoryCode)
    {
        $result = array();
        if (preg_match('/^[A-Z]{2}|[0-9]{3}$/', $territoryCode)) {
            $data = Data::getGeneric('currencyData');
            if (isset($data['regions'][$territoryCode])) {
                foreach ($data['regions'][$territoryCode] as $c) {
                    if (isset($c['notTender'])) {
                        $c['tender'] = !$c['notTender'];
                        unset($c['notTender']);
                    } else {
                        $c['tender'] = true;
                    }
                    $result[] = $c;
                }
            }
        }

        return $result;
    }

    /**
     * Return the currency to be used in a territory.
     *
     * @param string $territoryCode The territoy code
     *
     * @return string Returns an empty string if $territoryCode is not valid or we don't have info about it, the currency code otherwise
     */
    public static function getCurrencyForTerritory($territoryCode)
    {
        $result = '';
        $history = static::getCurrencyHistoryForTerritory($territoryCode);
        if (!empty($history)) {
            $today = @date('Y-m-d');
            foreach ($history as $c) {
                if ((!isset($c['to'])) || (strcmp($c['to'], $today) >= 0)) {
                    $result = $c['currency'];
                    if ($c['tender']) {
                        break;
                    }
                }
            }
        }

        return $result;
    }

    protected static function getLocaleData($currencyCode, $locale)
    {
        $result = null;
        if (is_string($currencyCode) && (strlen($currencyCode) === 3)) {
            $data = Data::get('currencies', $locale);
            if (isset($data[$currencyCode])) {
                $result = $data[$currencyCode];
            }
        }

        return $result;
    }
}
