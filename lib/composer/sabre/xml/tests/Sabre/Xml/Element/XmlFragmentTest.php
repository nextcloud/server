<?php

declare(strict_types=1);

namespace Sabre\Xml\Element;

use Sabre\Xml\Reader;
use Sabre\Xml\Writer;

class XmlFragmentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider xmlProvider
     */
    public function testDeserialize($input, $expected)
    {
        $input = <<<BLA
<?xml version="1.0"?>
<root xmlns="http://sabredav.org/ns">
   <fragment>$input</fragment>
</root>
BLA;

        $reader = new Reader();
        $reader->elementMap = [
            '{http://sabredav.org/ns}fragment' => 'Sabre\\Xml\\Element\\XmlFragment',
        ];
        $reader->xml($input);

        $output = $reader->parse();

        $this->assertEquals([
            'name' => '{http://sabredav.org/ns}root',
            'value' => [
                [
                    'name' => '{http://sabredav.org/ns}fragment',
                    'value' => new XmlFragment($expected),
                    'attributes' => [],
                ],
            ],
            'attributes' => [],
        ], $output);
    }

    /**
     * Data provider for serialize and deserialize tests.
     *
     * Returns three items per test:
     *
     * 1. Input data for the reader.
     * 2. Expected output for XmlFragment deserializer
     * 3. Expected output after serializing that value again.
     *
     * If 3 is not set, use 1 for 3.
     */
    public function xmlProvider()
    {
        return [
            [
                'hello',
                'hello',
            ],
            [
                '<element>hello</element>',
                '<element xmlns="http://sabredav.org/ns">hello</element>',
            ],
            [
                '<element foo="bar">hello</element>',
                '<element xmlns="http://sabredav.org/ns" foo="bar">hello</element>',
            ],
            [
                '<element x1:foo="bar" xmlns:x1="http://example.org/ns">hello</element>',
                '<element xmlns:x1="http://example.org/ns" xmlns="http://sabredav.org/ns" x1:foo="bar">hello</element>',
            ],
            [
                '<element xmlns="http://example.org/ns">hello</element>',
                '<element xmlns="http://example.org/ns">hello</element>',
                '<x1:element xmlns:x1="http://example.org/ns">hello</x1:element>',
            ],
            [
                '<element xmlns:foo="http://example.org/ns">hello</element>',
                '<element xmlns:foo="http://example.org/ns" xmlns="http://sabredav.org/ns">hello</element>',
                '<element>hello</element>',
            ],
            [
                '<foo:element xmlns:foo="http://example.org/ns">hello</foo:element>',
                '<foo:element xmlns:foo="http://example.org/ns">hello</foo:element>',
                '<x1:element xmlns:x1="http://example.org/ns">hello</x1:element>',
            ],
            [
                '<foo:element xmlns:foo="http://example.org/ns"><child>hello</child></foo:element>',
                '<foo:element xmlns:foo="http://example.org/ns" xmlns="http://sabredav.org/ns"><child>hello</child></foo:element>',
                '<x1:element xmlns:x1="http://example.org/ns"><child>hello</child></x1:element>',
            ],
            [
                '<foo:element xmlns:foo="http://example.org/ns"><child/></foo:element>',
                '<foo:element xmlns:foo="http://example.org/ns" xmlns="http://sabredav.org/ns"><child/></foo:element>',
                '<x1:element xmlns:x1="http://example.org/ns"><child/></x1:element>',
            ],
            [
                '<foo:element xmlns:foo="http://example.org/ns"><child a="b"/></foo:element>',
                '<foo:element xmlns:foo="http://example.org/ns" xmlns="http://sabredav.org/ns"><child a="b"/></foo:element>',
                '<x1:element xmlns:x1="http://example.org/ns"><child a="b"/></x1:element>',
            ],
        ];
    }

    /**
     * @dataProvider xmlProvider
     */
    public function testSerialize($expectedFallback, $input, $expected = null)
    {
        if (is_null($expected)) {
            $expected = $expectedFallback;
        }

        $writer = new Writer();
        $writer->namespaceMap = [
            'http://sabredav.org/ns' => null,
        ];
        $writer->openMemory();
        $writer->startDocument('1.0');
        //$writer->setIndent(true);
        $writer->write([
            '{http://sabredav.org/ns}root' => [
                '{http://sabredav.org/ns}fragment' => new XmlFragment($input),
            ],
        ]);

        $output = $writer->outputMemory();

        $expected = <<<XML
<?xml version="1.0"?>
<root xmlns="http://sabredav.org/ns"><fragment>$expected</fragment></root>
XML;

        $this->assertEquals($expected, $output);
    }
}
