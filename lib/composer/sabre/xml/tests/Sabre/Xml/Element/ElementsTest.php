<?php

declare(strict_types=1);

namespace Sabre\Xml\Element;

use Sabre\Xml\Reader;
use Sabre\Xml\Writer;

class ElementsTest extends \PHPUnit\Framework\TestCase
{
    public function testDeserialize()
    {
        $input = <<<BLA
<?xml version="1.0"?>
<root xmlns="http://sabredav.org/ns">
  <listThingy>
    <elem1 />
    <elem2 />
    <elem3 />
    <elem4 attr="val" />
    <elem5>content</elem5>
    <elem6><subnode /></elem6>
  </listThingy>
  <listThingy />
  <otherThing>
    <elem1 />
    <elem2 />
    <elem3 />
  </otherThing>
</root>
BLA;

        $reader = new Reader();
        $reader->elementMap = [
            '{http://sabredav.org/ns}listThingy' => 'Sabre\\Xml\\Element\\Elements',
        ];
        $reader->xml($input);

        $output = $reader->parse();

        $this->assertEquals([
            'name' => '{http://sabredav.org/ns}root',
            'value' => [
                [
                    'name' => '{http://sabredav.org/ns}listThingy',
                    'value' => [
                        '{http://sabredav.org/ns}elem1',
                        '{http://sabredav.org/ns}elem2',
                        '{http://sabredav.org/ns}elem3',
                        '{http://sabredav.org/ns}elem4',
                        '{http://sabredav.org/ns}elem5',
                        '{http://sabredav.org/ns}elem6',
                    ],
                    'attributes' => [],
                ],
                [
                    'name' => '{http://sabredav.org/ns}listThingy',
                    'value' => [],
                    'attributes' => [],
                ],
                [
                    'name' => '{http://sabredav.org/ns}otherThing',
                    'value' => [
                        [
                            'name' => '{http://sabredav.org/ns}elem1',
                            'value' => null,
                            'attributes' => [],
                        ],
                        [
                            'name' => '{http://sabredav.org/ns}elem2',
                            'value' => null,
                            'attributes' => [],
                        ],
                        [
                            'name' => '{http://sabredav.org/ns}elem3',
                            'value' => null,
                            'attributes' => [],
                        ],
                    ],
                    'attributes' => [],
                ],
            ],
            'attributes' => [],
        ], $output);
    }

    public function testSerialize()
    {
        $value = [
            '{http://sabredav.org/ns}elem1',
            '{http://sabredav.org/ns}elem2',
            '{http://sabredav.org/ns}elem3',
            '{http://sabredav.org/ns}elem4',
            '{http://sabredav.org/ns}elem5',
            '{http://sabredav.org/ns}elem6',
        ];

        $writer = new Writer();
        $writer->namespaceMap = [
            'http://sabredav.org/ns' => null,
        ];
        $writer->openMemory();
        $writer->startDocument('1.0');
        $writer->setIndent(true);
        $writer->write([
            '{http://sabredav.org/ns}root' => new Elements($value),
        ]);

        $output = $writer->outputMemory();

        $expected = <<<XML
<?xml version="1.0"?>
<root xmlns="http://sabredav.org/ns">
 <elem1/>
 <elem2/>
 <elem3/>
 <elem4/>
 <elem5/>
 <elem6/>
</root>

XML;

        $this->assertEquals($expected, $output);
    }
}
