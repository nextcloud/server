<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Data;

/**
 * Parser
 *
 * This class is used to parse plain text into objects. It's used by hybriauth adapters to converts
 * providers api responses to a more 'manageable' format.
 */
final class Parser
{
    /**
     * Decodes a string into an object.
     *
     * This method will first attempt to parse data as a JSON string (since most providers use this format)
     * then XML and parse_str.
     *
     * @param string $raw
     *
     * @return mixed
     */
    public function parse($raw = null)
    {
        $data = $this->parseJson($raw);

        if (!$data) {
            $data = $this->parseXml($raw);

            if (!$data) {
                $data = $this->parseQueryString($raw);
            }
        }

        return $data;
    }

    /**
     * Decodes a JSON string
     *
     * @param $result
     *
     * @return mixed
     */
    public function parseJson($result)
    {
        return json_decode($result);
    }

    /**
     * Decodes a XML string
     *
     * @param $result
     *
     * @return mixed
     */
    public function parseXml($result)
    {
        libxml_use_internal_errors(true);

        $result = preg_replace('/([<\/])([a-z0-9-]+):/i', '$1', $result);
        $xml = simplexml_load_string($result);

        libxml_use_internal_errors(false);

        if (!$xml) {
            return [];
        }

        $arr = json_decode(json_encode((array)$xml), true);
        $arr = array($xml->getName() => $arr);

        return $arr;
    }

    /**
     * Parses a string into variables
     *
     * @param $result
     *
     * @return \StdClass
     */
    public function parseQueryString($result)
    {
        parse_str($result, $output);

        if (!is_array($output)) {
            return $result;
        }

        $result = new \StdClass();

        foreach ($output as $k => $v) {
            $result->$k = $v;
        }

        return $result;
    }

    /**
     * needs to be improved
     *
     * @param $birthday
     *
     * @return array
     */
    public function parseBirthday($birthday)
    {
        $birthday = date_parse((string) $birthday);

        return [$birthday['year'], $birthday['month'], $birthday['day']];
    }
}
