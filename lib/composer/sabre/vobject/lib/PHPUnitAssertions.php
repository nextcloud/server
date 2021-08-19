<?php

namespace Sabre\VObject;

/**
 * PHPUnit Assertions.
 *
 * This trait can be added to your unittest to make it easier to test iCalendar
 * and/or vCards.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
trait PHPUnitAssertions
{
    /**
     * This method tests whether two vcards or icalendar objects are
     * semantically identical.
     *
     * It supports objects being supplied as strings, streams or
     * Sabre\VObject\Component instances.
     *
     * PRODID is removed from both objects as this is often changes and would
     * just get in the way.
     *
     * CALSCALE will automatically get removed if it's set to GREGORIAN.
     *
     * Any property that has the value **ANY** will be treated as a wildcard.
     *
     * @param resource|string|Component $expected
     * @param resource|string|Component $actual
     * @param string                    $message
     */
    public function assertVObjectEqualsVObject($expected, $actual, $message = '')
    {
        $getObj = function ($input) {
            if (is_resource($input)) {
                $input = stream_get_contents($input);
            }
            if (is_string($input)) {
                $input = Reader::read($input);
            }
            if (!$input instanceof Component) {
                $this->fail('Input must be a string, stream or VObject component');
            }
            unset($input->PRODID);
            if ($input instanceof Component\VCalendar && 'GREGORIAN' === (string) $input->CALSCALE) {
                unset($input->CALSCALE);
            }

            return $input;
        };

        $expected = $getObj($expected)->serialize();
        $actual = $getObj($actual)->serialize();

        // Finding wildcards in expected.
        preg_match_all('|^([A-Z]+):\\*\\*ANY\\*\\*\r$|m', $expected, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $actual = preg_replace(
                '|^'.preg_quote($match[1], '|').':(.*)\r$|m',
                $match[1].':**ANY**'."\r",
                $actual
            );
        }

        $this->assertEquals(
            $expected,
            $actual,
            $message
        );
    }
}
