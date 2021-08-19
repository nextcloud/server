<?php

declare(strict_types=1);

namespace Sabre\Xml\Element;

use Sabre\Xml\Reader;
use Sabre\Xml\Writer;

class KeyValueTest extends \PHPUnit\Framework\TestCase
{
    public function testDeserialize()
    {
        $input = <<<BLA
<?xml version="1.0"?>
<root xmlns="http://sabredav.org/ns">
  <struct>
    <elem1 />
    <elem2>hi</elem2>
    <elem3>
       <elem4>foo</elem4>
       <elem5>foo &amp; bar</elem5>
    </elem3>
    <elem6>Hi<!-- ignore me -->there</elem6>
  </struct>
  <struct />
  <otherThing>
    <elem1 />
  </otherThing>
</root>
BLA;

        $reader = new Reader();
        $reader->elementMap = [
            '{http://sabredav.org/ns}struct' => 'Sabre\\Xml\\Element\\KeyValue',
        ];
        $reader->xml($input);

        $output = $reader->parse();

        $this->assertEquals([
            'name' => '{http://sabredav.org/ns}root',
            'value' => [
                [
                    'name' => '{http://sabredav.org/ns}struct',
                    'value' => [
                        '{http://sabredav.org/ns}elem1' => null,
                        '{http://sabredav.org/ns}elem2' => 'hi',
                        '{http://sabredav.org/ns}elem3' => [
                            [
                                'name' => '{http://sabredav.org/ns}elem4',
                                'value' => 'foo',
                                'attributes' => [],
                            ],
                            [
                                'name' => '{http://sabredav.org/ns}elem5',
                                'value' => 'foo & bar',
                                'attributes' => [],
                            ],
                        ],
                        '{http://sabredav.org/ns}elem6' => 'Hithere',
                    ],
                    'attributes' => [],
                ],
                [
                    'name' => '{http://sabredav.org/ns}struct',
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
                    ],
                    'attributes' => [],
                ],
            ],
            'attributes' => [],
        ], $output);
    }

    /**
     * This test was added to find out why an element gets eaten by the
     * SabreDAV MKCOL parser.
     */
    public function testElementEater()
    {
        $input = <<<BLA
<?xml version="1.0"?>
<mkcol xmlns="DAV:">
  <set>
    <prop>
        <resourcetype><collection /></resourcetype>
        <displayname>bla</displayname>
    </prop>
  </set>
</mkcol>
BLA;

        $reader = new Reader();
        $reader->elementMap = [
            '{DAV:}set' => 'Sabre\\Xml\\Element\\KeyValue',
            '{DAV:}prop' => 'Sabre\\Xml\\Element\\KeyValue',
            '{DAV:}resourcetype' => 'Sabre\\Xml\\Element\\Elements',
        ];
        $reader->xml($input);

        $expected = [
            'name' => '{DAV:}mkcol',
            'value' => [
                [
                    'name' => '{DAV:}set',
                    'value' => [
                        '{DAV:}prop' => [
                            '{DAV:}resourcetype' => [
                                '{DAV:}collection',
                            ],
                            '{DAV:}displayname' => 'bla',
                        ],
                    ],
                    'attributes' => [],
                ],
            ],
            'attributes' => [],
        ];

        $this->assertEquals($expected, $reader->parse());
    }

    public function testSerialize()
    {
        $value = [
            '{http://sabredav.org/ns}elem1' => null,
            '{http://sabredav.org/ns}elem2' => 'textValue',
            '{http://sabredav.org/ns}elem3' => [
                '{http://sabredav.org/ns}elem4' => 'text2',
                '{http://sabredav.org/ns}elem5' => null,
            ],
            '{http://sabredav.org/ns}elem6' => 'text3',
        ];

        $writer = new Writer();
        $writer->namespaceMap = [
            'http://sabredav.org/ns' => null,
        ];
        $writer->openMemory();
        $writer->startDocument('1.0');
        $writer->setIndent(true);
        $writer->write([
            '{http://sabredav.org/ns}root' => new KeyValue($value),
        ]);

        $output = $writer->outputMemory();

        $expected = <<<XML
<?xml version="1.0"?>
<root xmlns="http://sabredav.org/ns">
 <elem1/>
 <elem2>textValue</elem2>
 <elem3>
  <elem4>text2</elem4>
  <elem5/>
 </elem3>
 <elem6>text3</elem6>
</root>

XML;

        $this->assertEquals($expected, $output);
    }

    /**
     * I discovered that when there's no whitespace between elements, elements
     * can get skipped.
     */
    public function testElementSkipProblem()
    {
        $input = <<<BLA
<?xml version="1.0" encoding="utf-8"?>
<root xmlns="http://sabredav.org/ns">
<elem3>val3</elem3><elem4>val4</elem4><elem5>val5</elem5></root>
BLA;

        $reader = new Reader();
        $reader->elementMap = [
            '{http://sabredav.org/ns}root' => 'Sabre\\Xml\\Element\\KeyValue',
        ];
        $reader->xml($input);

        $output = $reader->parse();

        $this->assertEquals([
            'name' => '{http://sabredav.org/ns}root',
            'value' => [
                '{http://sabredav.org/ns}elem3' => 'val3',
                '{http://sabredav.org/ns}elem4' => 'val4',
                '{http://sabredav.org/ns}elem5' => 'val5',
            ],
            'attributes' => [],
        ], $output);
    }
}
