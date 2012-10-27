<?php

namespace Sabre\VObject;

/**
 * VCALENDAR/VCARD reader
 *
 * This class reads the vobject file, and returns a full element tree.
 *
 * TODO: this class currently completely works 'statically'. This is pointless,
 * and defeats OOP principals. Needs refactoring in a future version.
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Reader {

    /**
     * If this option is passed to the reader, it will be less strict about the
     * validity of the lines.
     *
     * Currently using this option just means, that it will accept underscores
     * in property names.
     */
    const OPTION_FORGIVING = 1;

    /**
     * If this option is turned on, any lines we cannot parse will be ignored
     * by the reader.
     */
    const OPTION_IGNORE_INVALID_LINES = 2;

    /**
     * Parses the file and returns the top component
     *
     * The options argument is a bitfield. Pass any of the OPTIONS constant to
     * alter the parsers' behaviour.
     *
     * @param string $data
     * @param int $options
     * @return Node
     */
    static function read($data, $options = 0) {

        // Normalizing newlines
        $data = str_replace(array("\r","\n\n"), array("\n","\n"), $data);

        $lines = explode("\n", $data);

        // Unfolding lines
        $lines2 = array();
        foreach($lines as $line) {

            // Skipping empty lines
            if (!$line) continue;

            if ($line[0]===" " || $line[0]==="\t") {
                $lines2[count($lines2)-1].=substr($line,1);
            } else {
                $lines2[] = $line;
            }

        }

        unset($lines);

        reset($lines2);

        return self::readLine($lines2, $options);

    }

    /**
     * Reads and parses a single line.
     *
     * This method receives the full array of lines. The array pointer is used
     * to traverse.
     *
     * This method returns null if an invalid line was encountered, and the
     * IGNORE_INVALID_LINES option was turned on.
     *
     * @param array $lines
     * @param int $options See the OPTIONS constants.
     * @return Node
     */
    static private function readLine(&$lines, $options = 0) {

        $line = current($lines);
        $lineNr = key($lines);
        next($lines);

        // Components
        if (strtoupper(substr($line,0,6)) === "BEGIN:") {

            $componentName = strtoupper(substr($line,6));
            $obj = Component::create($componentName);

            $nextLine = current($lines);

            while(strtoupper(substr($nextLine,0,4))!=="END:") {

                $parsedLine = self::readLine($lines, $options);
                $nextLine = current($lines);

                if (is_null($parsedLine)) {
                    continue;
                }
                $obj->add($parsedLine);

                if ($nextLine===false)
                    throw new ParseException('Invalid VObject. Document ended prematurely.');

            }

            // Checking component name of the 'END:' line.
            if (substr($nextLine,4)!==$obj->name) {
                throw new ParseException('Invalid VObject, expected: "END:' . $obj->name . '" got: "' . $nextLine . '"');
            }
            next($lines);

            return $obj;

        }

        // Properties
        //$result = preg_match('/(?P<name>[A-Z0-9-]+)(?:;(?P<parameters>^(?<!:):))(.*)$/',$line,$matches);

        if ($options & self::OPTION_FORGIVING) {
            $token = '[A-Z0-9-\._]+';
        } else {
            $token = '[A-Z0-9-\.]+';
        }
        $parameters = "(?:;(?P<parameters>([^:^\"]|\"([^\"]*)\")*))?";
        $regex = "/^(?P<name>$token)$parameters:(?P<value>.*)$/i";

        $result = preg_match($regex,$line,$matches);

        if (!$result) {
            if ($options & self::OPTION_IGNORE_INVALID_LINES) {
                return null;
            } else {
                throw new ParseException('Invalid VObject, line ' . ($lineNr+1) . ' did not follow the icalendar/vcard format');
            }
        }

        $propertyName = strtoupper($matches['name']);
        $propertyValue = preg_replace_callback('#(\\\\(\\\\|N|n))#',function($matches) {
            if ($matches[2]==='n' || $matches[2]==='N') {
                return "\n";
            } else {
                return $matches[2];
            }
        }, $matches['value']);

        $obj = Property::create($propertyName, $propertyValue);

        if ($matches['parameters']) {

            foreach(self::readParameters($matches['parameters']) as $param) {
                $obj->add($param);
            }

        }

        return $obj;


    }

    /**
     * Reads a parameter list from a property
     *
     * This method returns an array of Parameter
     *
     * @param string $parameters
     * @return array
     */
    static private function readParameters($parameters) {

        $token = '[A-Z0-9-]+';

        $paramValue = '(?P<paramValue>[^\"^;]*|"[^"]*")';

        $regex = "/(?<=^|;)(?P<paramName>$token)(=$paramValue(?=$|;))?/i";
        preg_match_all($regex, $parameters, $matches,  PREG_SET_ORDER);

        $params = array();
        foreach($matches as $match) {

            $value = isset($match['paramValue'])?$match['paramValue']:null;

            if (isset($value[0])) {
                // Stripping quotes, if needed
                if ($value[0] === '"') $value = substr($value,1,strlen($value)-2);
            } else {
                $value = '';
            }

            $value = preg_replace_callback('#(\\\\(\\\\|N|n|;|,))#',function($matches) {
                if ($matches[2]==='n' || $matches[2]==='N') {
                    return "\n";
                } else {
                    return $matches[2];
                }
            }, $value);

            $params[] = new Parameter($match['paramName'], $value);

        }

        return $params;

    }


}
