<?php

declare(strict_types=1);

namespace Sabre\Xml;

use Sabre\Xml\Element\KeyValue;

class ServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testGetReader()
    {
        $elems = [
            '{http://sabre.io/ns}test' => 'Test!',
        ];

        $util = new Service();
        $util->elementMap = $elems;

        $reader = $util->getReader();
        $this->assertInstanceOf('Sabre\\Xml\\Reader', $reader);
        $this->assertEquals($elems, $reader->elementMap);
    }

    public function testGetWriter()
    {
        $ns = [
            'http://sabre.io/ns' => 's',
        ];

        $util = new Service();
        $util->namespaceMap = $ns;

        $writer = $util->getWriter();
        $this->assertInstanceOf('Sabre\\Xml\\Writer', $writer);
        $this->assertEquals($ns, $writer->namespaceMap);
    }

    /**
     * @dataProvider providesEmptyInput
     *
     * @param string|resource $input
     */
    public function testEmptyInputParse($input)
    {
        $this->expectException('\Sabre\Xml\ParseException');
        $this->expectExceptionMessage('The input element to parse is empty. Do not attempt to parse');

        $util = new Service();
        $util->parse($input, '/sabre.io/ns');
    }

    /**
     * @depends testGetReader
     */
    public function testParse()
    {
        $xml = <<<XML
<root xmlns="http://sabre.io/ns">
  <child>value</child>
</root>
XML;
        $util = new Service();
        $result = $util->parse($xml, null, $rootElement);
        $this->assertEquals('{http://sabre.io/ns}root', $rootElement);

        $expected = [
            [
                'name' => '{http://sabre.io/ns}child',
                'value' => 'value',
                'attributes' => [],
            ],
        ];

        $this->assertEquals(
            $expected,
            $result
        );
    }

    /**
     * @depends testGetReader
     */
    public function testParseStream()
    {
        $xml = <<<XML
<root xmlns="http://sabre.io/ns">
  <child>value</child>
</root>
XML;
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $xml);
        rewind($stream);

        $util = new Service();
        $result = $util->parse($stream, null, $rootElement);
        $this->assertEquals('{http://sabre.io/ns}root', $rootElement);

        $expected = [
            [
                'name' => '{http://sabre.io/ns}child',
                'value' => 'value',
                'attributes' => [],
            ],
        ];

        $this->assertEquals(
            $expected,
            $result
        );
    }

    /**
     * @dataProvider providesEmptyInput
     *
     * @param string|resource $input
     */
    public function testEmptyInputExpect($input)
    {
        $this->expectException('\Sabre\Xml\ParseException');
        $this->expectExceptionMessage('The input element to parse is empty. Do not attempt to parse');

        $util = new Service();
        $util->expect('foo', $input, '/sabre.io/ns');
    }

    /**
     * @depends testGetReader
     */
    public function testExpect()
    {
        $xml = <<<XML
<root xmlns="http://sabre.io/ns">
  <child>value</child>
</root>
XML;
        $util = new Service();
        $result = $util->expect('{http://sabre.io/ns}root', $xml);

        $expected = [
            [
                'name' => '{http://sabre.io/ns}child',
                'value' => 'value',
                'attributes' => [],
            ],
        ];

        $this->assertEquals(
            $expected,
            $result
        );
    }

    public function testInvalidNameSpace()
    {
        $this->expectException(LibXMLException::class);
        $xml = '<D:propfind xmlns:D="DAV:"><D:prop><bar:foo xmlns:bar=""/></D:prop></D:propfind>';

        $util = new Service();
        $util->elementMap = [
            '{DAV:}propfind' => PropFindTestAsset::class,
        ];
        $util->namespaceMap = [
            'http://sabre.io/ns' => 's',
        ];
        $result = $util->expect('{DAV:}propfind', $xml);
    }

    /**
     * @dataProvider providesEmptyPropfinds
     */
    public function testEmptyPropfind($xml)
    {
        $util = new Service();
        $util->elementMap = [
            '{DAV:}propfind' => PropFindTestAsset::class,
        ];
        $util->namespaceMap = [
            'http://sabre.io/ns' => 's',
        ];
        /**
         * @var PropFindTestAsset
         */
        $result = $util->expect('{DAV:}propfind', $xml);
        $this->assertIsObject($result);
        $this->assertInstanceOf(PropFindTestAsset::class, $result);
        $this->assertEquals(false, $result->allProp);
        $this->assertEquals([], $result->properties);
    }

    /**
     * @depends testGetReader
     */
    public function testExpectStream()
    {
        $xml = <<<XML
<root xmlns="http://sabre.io/ns">
  <child>value</child>
</root>
XML;

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $xml);
        rewind($stream);

        $util = new Service();
        $result = $util->expect('{http://sabre.io/ns}root', $stream);

        $expected = [
            [
                'name' => '{http://sabre.io/ns}child',
                'value' => 'value',
                'attributes' => [],
            ],
        ];

        $this->assertEquals(
            $expected,
            $result
        );
    }

    /**
     * @depends testGetReader
     */
    public function testExpectWrong()
    {
        $this->expectException(ParseException::class);
        $xml = <<<XML
<root xmlns="http://sabre.io/ns">
  <child>value</child>
</root>
XML;
        $util = new Service();
        $util->expect('{http://sabre.io/ns}error', $xml);
    }

    /**
     * @depends testGetWriter
     */
    public function testWrite()
    {
        $util = new Service();
        $util->namespaceMap = [
            'http://sabre.io/ns' => 's',
        ];
        $result = $util->write('{http://sabre.io/ns}root', [
            '{http://sabre.io/ns}child' => 'value',
        ]);

        $expected = <<<XML
<?xml version="1.0"?>
<s:root xmlns:s="http://sabre.io/ns">
 <s:child>value</s:child>
</s:root>

XML;
        $this->assertEquals(
            $expected,
            $result
        );
    }

    public function testMapValueObject()
    {
        $input = <<<XML
<?xml version="1.0"?>
<order xmlns="http://sabredav.org/ns">
 <id>1234</id>
 <amount>99.99</amount>
 <description>black friday deal</description>
 <status>
  <id>5</id>
  <label>processed</label>
 </status>
</order>

XML;

        $ns = 'http://sabredav.org/ns';
        $orderService = new \Sabre\Xml\Service();
        $orderService->mapValueObject('{'.$ns.'}order', 'Sabre\Xml\Order');
        $orderService->mapValueObject('{'.$ns.'}status', 'Sabre\Xml\OrderStatus');
        $orderService->namespaceMap[$ns] = null;

        $order = $orderService->parse($input);
        $expected = new Order();
        $expected->id = 1234;
        $expected->amount = 99.99;
        $expected->description = 'black friday deal';
        $expected->status = new OrderStatus();
        $expected->status->id = 5;
        $expected->status->label = 'processed';

        $this->assertEquals($expected, $order);

        $writtenXml = $orderService->writeValueObject($order);
        $this->assertEquals($input, $writtenXml);
    }

    public function testMapValueObjectArrayProperty()
    {
        $input = <<<XML
<?xml version="1.0"?>
<order xmlns="http://sabredav.org/ns">
 <id>1234</id>
 <amount>99.99</amount>
 <description>black friday deal</description>
 <status>
  <id>5</id>
  <label>processed</label>
 </status>
 <link>http://example.org/</link>
 <link>http://example.com/</link>
</order>

XML;

        $ns = 'http://sabredav.org/ns';
        $orderService = new \Sabre\Xml\Service();
        $orderService->mapValueObject('{'.$ns.'}order', 'Sabre\Xml\Order');
        $orderService->mapValueObject('{'.$ns.'}status', 'Sabre\Xml\OrderStatus');
        $orderService->namespaceMap[$ns] = null;

        $order = $orderService->parse($input);
        $expected = new Order();
        $expected->id = 1234;
        $expected->amount = 99.99;
        $expected->description = 'black friday deal';
        $expected->status = new OrderStatus();
        $expected->status->id = 5;
        $expected->status->label = 'processed';
        $expected->link = ['http://example.org/', 'http://example.com/'];

        $this->assertEquals($expected, $order);

        $writtenXml = $orderService->writeValueObject($order);
        $this->assertEquals($input, $writtenXml);
    }

    public function testWriteVoNotFound()
    {
        $this->expectException(\InvalidArgumentException::class);
        $service = new Service();
        $service->writeValueObject(new \StdClass());
    }

    public function testParseClarkNotation()
    {
        $this->assertEquals([
            'http://sabredav.org/ns',
            'elem',
        ], Service::parseClarkNotation('{http://sabredav.org/ns}elem'));
    }

    public function testParseClarkNotationFail()
    {
        $this->expectException(\InvalidArgumentException::class);
        Service::parseClarkNotation('http://sabredav.org/ns}elem');
    }

    public function providesEmptyInput()
    {
        $emptyResource = fopen('php://input', 'r');
        $data[] = [$emptyResource];
        $data[] = [''];

        return $data;
    }

    public function providesEmptyPropfinds()
    {
        return [
            ['<D:propfind xmlns:D="DAV:"><D:prop></D:prop></D:propfind>'],
            ['<D:propfind xmlns:D="DAV:"><D:prop xmlns:s="http://sabredav.org/ns"></D:prop></D:propfind>'],
            ['<D:propfind xmlns:D="DAV:"><D:prop/></D:propfind>'],
            ['<D:propfind xmlns:D="DAV:"><D:prop xmlns:s="http://sabredav.org/ns"/></D:propfind>'],
            ['<D:propfind xmlns:D="DAV:"><D:prop>     </D:prop></D:propfind>'],
        ];
    }
}

/**
 * asset for testMapValueObject().
 *
 * @internal
 */
class Order
{
    public $id;
    public $amount;
    public $description;
    public $status;
    public $empty;
    public $link = [];
}

/**
 * asset for testMapValueObject().
 *
 * @internal
 */
class OrderStatus
{
    public $id;
    public $label;
}

/**
 * asset for testInvalidNameSpace.
 *
 * @internal
 */
class PropFindTestAsset implements XmlDeserializable
{
    public $allProp = false;

    public $properties;

    public static function xmlDeserialize(Reader $reader)
    {
        $self = new self();

        $reader->pushContext();
        $reader->elementMap['{DAV:}prop'] = 'Sabre\Xml\Element\Elements';

        foreach (KeyValue::xmlDeserialize($reader) as $k => $v) {
            switch ($k) {
                case '{DAV:}prop':
                    $self->properties = $v;
                    break;
                case '{DAV:}allprop':
                    $self->allProp = true;
            }
        }

        $reader->popContext();

        return $self;
    }
}
