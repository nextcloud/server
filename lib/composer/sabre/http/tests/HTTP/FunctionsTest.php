<?php

declare(strict_types=1);

namespace Sabre\HTTP;

class FunctionsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getHeaderValuesData
     */
    public function testGetHeaderValues($input, $output)
    {
        $this->assertEquals(
            $output,
            getHeaderValues($input)
        );
    }

    public function getHeaderValuesData()
    {
        return [
            [
                'a',
                ['a'],
            ],
            [
                'a,b',
                ['a', 'b'],
            ],
            [
                'a, b',
                ['a', 'b'],
            ],
            [
                ['a, b'],
                ['a', 'b'],
            ],
            [
                ['a, b', 'c', 'd,e'],
                ['a', 'b', 'c', 'd', 'e'],
            ],
        ];
    }

    /**
     * @dataProvider preferData
     */
    public function testPrefer($input, $output)
    {
        $this->assertEquals(
            $output,
            parsePrefer($input)
        );
    }

    public function preferData()
    {
        return [
            [
                'foo; bar',
                ['foo' => true],
            ],
            [
                'foo; bar=""',
                ['foo' => true],
            ],
            [
                'foo=""; bar',
                ['foo' => true],
            ],
            [
                'FOO',
                ['foo' => true],
            ],
            [
                'respond-async',
                ['respond-async' => true],
            ],
            [
                ['respond-async, wait=100', 'handling=lenient'],
                ['respond-async' => true, 'wait' => 100, 'handling' => 'lenient'],
            ],
            [
                ['respond-async, wait=100, handling=lenient'],
                ['respond-async' => true, 'wait' => 100, 'handling' => 'lenient'],
            ],
            // Old values
            [
                'return-asynch, return-representation',
                ['respond-async' => true, 'return' => 'representation'],
            ],
            [
                'return-minimal',
                ['return' => 'minimal'],
            ],
            [
                'strict',
                ['handling' => 'strict'],
            ],
            [
                'lenient',
                ['handling' => 'lenient'],
            ],
            // Invalid token
            [
                ['foo=%bar%'],
                [],
            ],
        ];
    }

    public function testParseHTTPDate()
    {
        $times = [
            'Wed, 13 Oct 2010 10:26:00 GMT',
            'Wednesday, 13-Oct-10 10:26:00 GMT',
            'Wed Oct 13 10:26:00 2010',
        ];

        $expected = 1286965560;

        foreach ($times as $time) {
            $result = parseDate($time);
            $this->assertEquals($expected, $result->format('U'));
        }

        $result = parseDate('Wed Oct  6 10:26:00 2010');
        $this->assertEquals(1286360760, $result->format('U'));
    }

    public function testParseHTTPDateFail()
    {
        $times = [
            //random string
            'NOW',
            // not-GMT timezone
            'Wednesday, 13-Oct-10 10:26:00 UTC',
            // No space before the 6
            'Wed Oct 6 10:26:00 2010',
            // Invalid day
            'Wed Oct  0 10:26:00 2010',
            'Wed Oct 32 10:26:00 2010',
            'Wed, 0 Oct 2010 10:26:00 GMT',
            'Wed, 32 Oct 2010 10:26:00 GMT',
            'Wednesday, 32-Oct-10 10:26:00 GMT',
            // Invalid hour
            'Wed, 13 Oct 2010 24:26:00 GMT',
            'Wednesday, 13-Oct-10 24:26:00 GMT',
            'Wed Oct 13 24:26:00 2010',
        ];

        foreach ($times as $time) {
            $this->assertFalse(parseDate($time), 'We used the string: '.$time);
        }
    }

    public function testTimezones()
    {
        $default = date_default_timezone_get();
        date_default_timezone_set('Europe/Amsterdam');

        $this->testParseHTTPDate();

        date_default_timezone_set($default);
    }

    public function testToHTTPDate()
    {
        $dt = new \DateTime('2011-12-10 12:00:00 +0200');

        $this->assertEquals(
            'Sat, 10 Dec 2011 10:00:00 GMT',
            toDate($dt)
        );
    }
}
