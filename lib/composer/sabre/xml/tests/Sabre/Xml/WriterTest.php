<?php

declare(strict_types=1);

namespace Sabre\Xml;

class WriterTest extends \PHPUnit\Framework\TestCase
{
    protected $writer;

    public function setUp(): void
    {
        $this->writer = new Writer();
        $this->writer->namespaceMap = [
            'http://sabredav.org/ns' => 's',
        ];
        $this->writer->openMemory();
        $this->writer->setIndent(true);
        $this->writer->startDocument();
    }

    public function compare($input, $output)
    {
        $this->writer->write($input);
        $this->assertEquals($output, $this->writer->outputMemory());
    }

    public function testSimple()
    {
        $this->compare([
            '{http://sabredav.org/ns}root' => 'text',
        ], <<<HI
<?xml version="1.0"?>
<s:root xmlns:s="http://sabredav.org/ns">text</s:root>

HI
        );
    }

    /**
     * @depends testSimple
     */
    public function testSimpleQuotes()
    {
        $this->compare([
            '{http://sabredav.org/ns}root' => '"text"',
        ], <<<HI
<?xml version="1.0"?>
<s:root xmlns:s="http://sabredav.org/ns">&quot;text&quot;</s:root>

HI
        );
    }

    public function testSimpleAttributes()
    {
        $this->compare([
            '{http://sabredav.org/ns}root' => [
                'value' => 'text',
                'attributes' => [
                    'attr1' => 'attribute value',
                ],
            ],
        ], <<<HI
<?xml version="1.0"?>
<s:root xmlns:s="http://sabredav.org/ns" attr1="attribute value">text</s:root>

HI
        );
    }

    public function testMixedSyntax()
    {
        $this->compare([
            '{http://sabredav.org/ns}root' => [
                '{http://sabredav.org/ns}single' => 'value',
                '{http://sabredav.org/ns}multiple' => [
                    [
                        'name' => '{http://sabredav.org/ns}foo',
                        'value' => 'bar',
                    ],
                    [
                        'name' => '{http://sabredav.org/ns}foo',
                        'value' => 'foobar',
                    ],
                ],
                [
                    'name' => '{http://sabredav.org/ns}attributes',
                    'value' => null,
                    'attributes' => [
                        'foo' => 'bar',
                    ],
                ],
                [
                    'name' => '{http://sabredav.org/ns}verbose',
                    'value' => 'syntax',
                    'attributes' => [
                        'foo' => 'bar',
                    ],
                ],
            ],
        ], <<<HI
<?xml version="1.0"?>
<s:root xmlns:s="http://sabredav.org/ns">
 <s:single>value</s:single>
 <s:multiple>
  <s:foo>bar</s:foo>
  <s:foo>foobar</s:foo>
 </s:multiple>
 <s:attributes foo="bar"/>
 <s:verbose foo="bar">syntax</s:verbose>
</s:root>

HI
        );
    }

    public function testNull()
    {
        $this->compare([
            '{http://sabredav.org/ns}root' => null,
        ], <<<HI
<?xml version="1.0"?>
<s:root xmlns:s="http://sabredav.org/ns"/>

HI
        );
    }

    public function testArrayFormat2()
    {
        $this->compare([
            '{http://sabredav.org/ns}root' => [
                [
                    'name' => '{http://sabredav.org/ns}elem1',
                    'value' => 'text',
                    'attributes' => [
                        'attr1' => 'attribute value',
                    ],
                ],
            ],
        ], <<<HI
<?xml version="1.0"?>
<s:root xmlns:s="http://sabredav.org/ns">
 <s:elem1 attr1="attribute value">text</s:elem1>
</s:root>

HI
        );
    }

    public function testArrayOfValues()
    {
        $this->compare([
            '{http://sabredav.org/ns}root' => [
                [
                    'name' => '{http://sabredav.org/ns}elem1',
                    'value' => [
                        'foo',
                        'bar',
                        'baz',
                    ],
                ],
            ],
        ], <<<HI
<?xml version="1.0"?>
<s:root xmlns:s="http://sabredav.org/ns">
 <s:elem1>foobarbaz</s:elem1>
</s:root>

HI
        );
    }

    /**
     * @depends testArrayFormat2
     */
    public function testArrayFormat2NoValue()
    {
        $this->compare([
            '{http://sabredav.org/ns}root' => [
                [
                    'name' => '{http://sabredav.org/ns}elem1',
                    'attributes' => [
                        'attr1' => 'attribute value',
                    ],
                ],
            ],
        ], <<<HI
<?xml version="1.0"?>
<s:root xmlns:s="http://sabredav.org/ns">
 <s:elem1 attr1="attribute value"/>
</s:root>

HI
        );
    }

    public function testCustomNamespace()
    {
        $this->compare([
            '{http://sabredav.org/ns}root' => [
                '{urn:foo}elem1' => 'bar',
            ],
        ], <<<HI
<?xml version="1.0"?>
<s:root xmlns:s="http://sabredav.org/ns">
 <x1:elem1 xmlns:x1="urn:foo">bar</x1:elem1>
</s:root>

HI
        );
    }

    public function testEmptyNamespace()
    {
        // Empty namespaces are allowed, so we should support this.
        $this->compare([
            '{http://sabredav.org/ns}root' => [
                '{}elem1' => 'bar',
            ],
        ], <<<HI
<?xml version="1.0"?>
<s:root xmlns:s="http://sabredav.org/ns">
 <elem1 xmlns="">bar</elem1>
</s:root>

HI
        );
    }

    public function testAttributes()
    {
        $this->compare([
            '{http://sabredav.org/ns}root' => [
                [
                    'name' => '{http://sabredav.org/ns}elem1',
                    'value' => 'text',
                    'attributes' => [
                        'attr1' => 'val1',
                        '{http://sabredav.org/ns}attr2' => 'val2',
                        '{urn:foo}attr3' => 'val3',
                    ],
                ],
            ],
        ], <<<HI
<?xml version="1.0"?>
<s:root xmlns:s="http://sabredav.org/ns">
 <s:elem1 attr1="val1" s:attr2="val2" x1:attr3="val3" xmlns:x1="urn:foo">text</s:elem1>
</s:root>

HI
        );
    }

    public function testBaseElement()
    {
        $this->compare([
            '{http://sabredav.org/ns}root' => new Element\Base('hello'),
        ], <<<HI
<?xml version="1.0"?>
<s:root xmlns:s="http://sabredav.org/ns">hello</s:root>

HI
        );
    }

    public function testElementObj()
    {
        $this->compare([
            '{http://sabredav.org/ns}root' => new Element\Mock(),
        ], <<<HI
<?xml version="1.0"?>
<s:root xmlns:s="http://sabredav.org/ns">
 <s:elem1>hiiii!</s:elem1>
</s:root>

HI
        );
    }

    public function testEmptyNamespacePrefix()
    {
        $this->writer->namespaceMap['http://sabredav.org/ns'] = null;
        $this->compare([
            '{http://sabredav.org/ns}root' => new Element\Mock(),
        ], <<<HI
<?xml version="1.0"?>
<root xmlns="http://sabredav.org/ns">
 <elem1>hiiii!</elem1>
</root>

HI
        );
    }

    public function testEmptyNamespacePrefixEmptyString()
    {
        $this->writer->namespaceMap['http://sabredav.org/ns'] = '';
        $this->compare([
            '{http://sabredav.org/ns}root' => new Element\Mock(),
        ], <<<HI
<?xml version="1.0"?>
<root xmlns="http://sabredav.org/ns">
 <elem1>hiiii!</elem1>
</root>

HI
        );
    }

    public function testWriteElement()
    {
        $this->writer->writeElement('{http://sabredav.org/ns}foo', 'content');

        $output = <<<HI
<?xml version="1.0"?>
<s:foo xmlns:s="http://sabredav.org/ns">content</s:foo>

HI;

        $this->assertEquals($output, $this->writer->outputMemory());
    }

    public function testWriteElementComplex()
    {
        $this->writer->writeElement('{http://sabredav.org/ns}foo', new Element\KeyValue(['{http://sabredav.org/ns}bar' => 'test']));

        $output = <<<HI
<?xml version="1.0"?>
<s:foo xmlns:s="http://sabredav.org/ns">
 <s:bar>test</s:bar>
</s:foo>

HI;

        $this->assertEquals($output, $this->writer->outputMemory());
    }

    public function testWriteBadObject()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->writer->write(new \StdClass());
    }

    public function testStartElementSimple()
    {
        $this->writer->startElement('foo');
        $this->writer->endElement();

        $output = <<<HI
<?xml version="1.0"?>
<foo xmlns:s="http://sabredav.org/ns"/>

HI;

        $this->assertEquals($output, $this->writer->outputMemory());
    }

    public function testCallback()
    {
        $this->compare([
            '{http://sabredav.org/ns}root' => function (Writer $writer) {
                $writer->text('deferred writer');
            },
        ], <<<HI
<?xml version="1.0"?>
<s:root xmlns:s="http://sabredav.org/ns">deferred writer</s:root>

HI
        );
    }

    public function testResource()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->compare([
            '{http://sabredav.org/ns}root' => fopen('php://memory', 'r'),
        ], <<<HI
<?xml version="1.0"?>
<s:root xmlns:s="http://sabredav.org/ns">deferred writer</s:root>

HI
        );
    }

    public function testClassMap()
    {
        $obj = (object) [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $this->writer->classMap['stdClass'] = function (Writer $writer, $value) {
            foreach (get_object_vars($value) as $key => $val) {
                $writer->writeElement('{http://sabredav.org/ns}'.$key, $val);
            }
        };

        $this->compare([
            '{http://sabredav.org/ns}root' => $obj,
        ], <<<HI
<?xml version="1.0"?>
<s:root xmlns:s="http://sabredav.org/ns">
 <s:key1>value1</s:key1>
 <s:key2>value2</s:key2>
</s:root>

HI
        );
    }
}
