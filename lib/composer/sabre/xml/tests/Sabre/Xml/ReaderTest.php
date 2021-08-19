<?php

declare(strict_types=1);

namespace Sabre\Xml;

class ReaderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetClark()
    {
        $input = <<<BLA
<?xml version="1.0"?>
<root xmlns="http://sabredav.org/ns" />
BLA;
        $reader = new Reader();
        $reader->xml($input);

        $reader->next();

        $this->assertEquals('{http://sabredav.org/ns}root', $reader->getClark());
    }

    public function testGetClarkNoNS()
    {
        $input = <<<BLA
<?xml version="1.0"?>
<root />
BLA;
        $reader = new Reader();
        $reader->xml($input);

        $reader->next();

        $this->assertEquals('{}root', $reader->getClark());
    }

    public function testGetClarkNotOnAnElement()
    {
        $input = <<<BLA
<?xml version="1.0"?>
<root />
BLA;
        $reader = new Reader();
        $reader->xml($input);

        $this->assertNull($reader->getClark());
    }

    public function testSimple()
    {
        $input = <<<BLA
<?xml version="1.0"?>
<root xmlns="http://sabredav.org/ns">
  <elem1 attr="val" />
  <elem2>
    <elem3>Hi!</elem3>
  </elem2>
</root>
BLA;

        $reader = new Reader();
        $reader->xml($input);

        $output = $reader->parse();

        $expected = [
            'name' => '{http://sabredav.org/ns}root',
            'value' => [
                [
                    'name' => '{http://sabredav.org/ns}elem1',
                    'value' => null,
                    'attributes' => [
                        'attr' => 'val',
                    ],
                ],
                [
                    'name' => '{http://sabredav.org/ns}elem2',
                    'value' => [
                        [
                            'name' => '{http://sabredav.org/ns}elem3',
                            'value' => 'Hi!',
                            'attributes' => [],
                        ],
                    ],
                    'attributes' => [],
                ],
            ],
            'attributes' => [],
        ];

        $this->assertEquals($expected, $output);
    }

    public function testCDATA()
    {
        $input = <<<BLA
<?xml version="1.0"?>
<root xmlns="http://sabredav.org/ns">
  <foo><![CDATA[bar]]></foo>
</root>
BLA;

        $reader = new Reader();
        $reader->xml($input);

        $output = $reader->parse();

        $expected = [
            'name' => '{http://sabredav.org/ns}root',
            'value' => [
                [
                    'name' => '{http://sabredav.org/ns}foo',
                    'value' => 'bar',
                    'attributes' => [],
                ],
            ],
            'attributes' => [],
        ];

        $this->assertEquals($expected, $output);
    }

    public function testSimpleNamespacedAttribute()
    {
        $input = <<<BLA
<?xml version="1.0"?>
<root xmlns="http://sabredav.org/ns" xmlns:foo="urn:foo">
  <elem1 foo:attr="val" />
</root>
BLA;

        $reader = new Reader();
        $reader->xml($input);

        $output = $reader->parse();

        $expected = [
            'name' => '{http://sabredav.org/ns}root',
            'value' => [
                [
                    'name' => '{http://sabredav.org/ns}elem1',
                    'value' => null,
                    'attributes' => [
                        '{urn:foo}attr' => 'val',
                    ],
                ],
            ],
            'attributes' => [],
        ];

        $this->assertEquals($expected, $output);
    }

    public function testMappedElement()
    {
        $input = <<<BLA
<?xml version="1.0"?>
<root xmlns="http://sabredav.org/ns">
  <elem1 />
</root>
BLA;

        $reader = new Reader();
        $reader->elementMap = [
            '{http://sabredav.org/ns}elem1' => 'Sabre\\Xml\\Element\\Mock',
        ];
        $reader->xml($input);

        $output = $reader->parse();

        $expected = [
            'name' => '{http://sabredav.org/ns}root',
            'value' => [
                [
                    'name' => '{http://sabredav.org/ns}elem1',
                    'value' => 'foobar',
                    'attributes' => [],
                ],
            ],
            'attributes' => [],
        ];

        $this->assertEquals($expected, $output);
    }

    public function testMappedElementBadClass()
    {
        $this->expectException(\LogicException::class);
        $input = <<<BLA
<?xml version="1.0"?>
<root xmlns="http://sabredav.org/ns">
  <elem1 />
</root>
BLA;

        $reader = new Reader();
        $reader->elementMap = [
            '{http://sabredav.org/ns}elem1' => new \StdClass(),
        ];
        $reader->xml($input);

        $reader->parse();
    }

    /**
     * @depends testMappedElement
     */
    public function testMappedElementCallBack()
    {
        $input = <<<BLA
<?xml version="1.0"?>
<root xmlns="http://sabredav.org/ns">
  <elem1 />
</root>
BLA;

        $reader = new Reader();
        $reader->elementMap = [
            '{http://sabredav.org/ns}elem1' => function (Reader $reader) {
                $reader->next();

                return 'foobar';
            },
        ];
        $reader->xml($input);

        $output = $reader->parse();

        $expected = [
            'name' => '{http://sabredav.org/ns}root',
            'value' => [
                [
                    'name' => '{http://sabredav.org/ns}elem1',
                    'value' => 'foobar',
                    'attributes' => [],
                ],
            ],
            'attributes' => [],
        ];

        $this->assertEquals($expected, $output);
    }

    /**
     * @depends testMappedElementCallBack
     */
    public function testMappedElementCallBackNoNamespace()
    {
        $input = <<<BLA
<?xml version="1.0"?>
<root>
  <elem1 />
</root>
BLA;

        $reader = new Reader();
        $reader->elementMap = [
            'elem1' => function (Reader $reader) {
                $reader->next();

                return 'foobar';
            },
        ];
        $reader->xml($input);

        $output = $reader->parse();

        $expected = [
            'name' => '{}root',
            'value' => [
                [
                    'name' => '{}elem1',
                    'value' => 'foobar',
                    'attributes' => [],
                ],
            ],
            'attributes' => [],
        ];

        $this->assertEquals($expected, $output);
    }

    /**
     * @depends testMappedElementCallBack
     */
    public function testReadText()
    {
        $input = <<<BLA
<?xml version="1.0"?>
<root xmlns="http://sabredav.org/ns">
  <elem1>
    <elem2>hello </elem2>
    <elem2>world</elem2>
  </elem1>
</root>
BLA;

        $reader = new Reader();
        $reader->elementMap = [
            '{http://sabredav.org/ns}elem1' => function (Reader $reader) {
                return $reader->readText();
            },
        ];
        $reader->xml($input);

        $output = $reader->parse();

        $expected = [
            'name' => '{http://sabredav.org/ns}root',
            'value' => [
                [
                    'name' => '{http://sabredav.org/ns}elem1',
                    'value' => 'hello world',
                    'attributes' => [],
                ],
            ],
            'attributes' => [],
        ];

        $this->assertEquals($expected, $output);
    }

    public function testParseProblem()
    {
        $input = <<<BLA
<?xml version="1.0"?>
<root xmlns="http://sabredav.org/ns">
BLA;

        $reader = new Reader();
        $reader->elementMap = [
            '{http://sabredav.org/ns}elem1' => 'Sabre\\Xml\\Element\\Mock',
        ];
        $reader->xml($input);

        try {
            $output = $reader->parse();
            $this->fail('We expected a ParseException to be thrown');
        } catch (LibXMLException $e) {
            $this->assertIsArray($e->getErrors());
        }
    }

    public function testBrokenParserClass()
    {
        $this->expectException(ParseException::class);
        $input = <<<BLA
<?xml version="1.0"?>
<root xmlns="http://sabredav.org/ns">
<elem1 />
</root>
BLA;

        $reader = new Reader();
        $reader->elementMap = [
            '{http://sabredav.org/ns}elem1' => 'Sabre\\Xml\\Element\\Eater',
        ];
        $reader->xml($input);
        $reader->parse();
    }

    /**
     * Test was added for Issue #10.
     */
    public function testBrokenXml()
    {
        $this->expectException(LibXMLException::class);
        $input = <<<BLA
<test>
<hello>
</hello>
</sffsdf>
BLA;

        $reader = new Reader();
        $reader->xml($input);
        $reader->parse();
    }

    /**
     * Test was added for Issue #45.
     */
    public function testBrokenXml2()
    {
        $this->expectException(LibXMLException::class);
        $input = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<definitions>
    <collaboration>
        <participant id="sid-A33D08EB-A2DE-448F-86FE-A2B62E98818" name="Company" processRef="sid-A0A6A196-3C9A-4C69-88F6-7ED7DDFDD264">
            <extensionElements>
                <signavio:signavioMetaData metaKey="bgcolor" />
                ""Administrative w">
                <extensionElements>
                    <signavio:signavioMetaData metaKey="bgcolor" metaValue=""/>
                </extensionElements>
                </lan
XML;
        $reader = new Reader();
        $reader->xml($input);
        $reader->parse();
    }

    /**
     * @depends testMappedElement
     */
    public function testParseInnerTree()
    {
        $input = <<<BLA
<?xml version="1.0"?>
<root xmlns="http://sabredav.org/ns">
  <elem1>
     <elem1 />
  </elem1>
</root>
BLA;

        $reader = new Reader();
        $reader->elementMap = [
            '{http://sabredav.org/ns}elem1' => function (Reader $reader) {
                $innerTree = $reader->parseInnerTree(['{http://sabredav.org/ns}elem1' => function (Reader $reader) {
                    $reader->next();

                    return 'foobar';
                }]);

                return $innerTree;
            },
        ];
        $reader->xml($input);

        $output = $reader->parse();

        $expected = [
            'name' => '{http://sabredav.org/ns}root',
            'value' => [
                [
                    'name' => '{http://sabredav.org/ns}elem1',
                    'value' => [
                        [
                            'name' => '{http://sabredav.org/ns}elem1',
                            'value' => 'foobar',
                            'attributes' => [],
                        ],
                    ],
                    'attributes' => [],
                ],
            ],
            'attributes' => [],
        ];

        $this->assertEquals($expected, $output);
    }

    /**
     * @depends testParseInnerTree
     */
    public function testParseGetElements()
    {
        $input = <<<BLA
<?xml version="1.0"?>
<root xmlns="http://sabredav.org/ns">
  <elem1>
     <elem1 />
  </elem1>
</root>
BLA;

        $reader = new Reader();
        $reader->elementMap = [
            '{http://sabredav.org/ns}elem1' => function (Reader $reader) {
                $innerTree = $reader->parseGetElements(['{http://sabredav.org/ns}elem1' => function (Reader $reader) {
                    $reader->next();

                    return 'foobar';
                }]);

                return $innerTree;
            },
        ];
        $reader->xml($input);

        $output = $reader->parse();

        $expected = [
            'name' => '{http://sabredav.org/ns}root',
            'value' => [
                [
                    'name' => '{http://sabredav.org/ns}elem1',
                    'value' => [
                        [
                            'name' => '{http://sabredav.org/ns}elem1',
                            'value' => 'foobar',
                            'attributes' => [],
                        ],
                    ],
                    'attributes' => [],
                ],
            ],
            'attributes' => [],
        ];

        $this->assertEquals($expected, $output);
    }

    /**
     * @depends testParseInnerTree
     */
    public function testParseGetElementsNoElements()
    {
        $input = <<<BLA
<?xml version="1.0"?>
<root xmlns="http://sabredav.org/ns">
  <elem1>
    hi
  </elem1>
</root>
BLA;

        $reader = new Reader();
        $reader->elementMap = [
            '{http://sabredav.org/ns}elem1' => function (Reader $reader) {
                $innerTree = $reader->parseGetElements(['{http://sabredav.org/ns}elem1' => function (Reader $reader) {
                    $reader->next();

                    return 'foobar';
                }]);

                return $innerTree;
            },
        ];
        $reader->xml($input);

        $output = $reader->parse();

        $expected = [
            'name' => '{http://sabredav.org/ns}root',
            'value' => [
                [
                    'name' => '{http://sabredav.org/ns}elem1',
                    'value' => [],
                    'attributes' => [],
                ],
            ],
            'attributes' => [],
        ];

        $this->assertEquals($expected, $output);
    }
}
