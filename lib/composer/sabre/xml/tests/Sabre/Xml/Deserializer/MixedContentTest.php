<?php

declare(strict_types=1);

namespace Sabre\Xml\Deserializer;

use Sabre\Xml\Service;

class MixedContentTest extends \PHPUnit\Framework\TestCase
{
    public function testDeserialize()
    {
        $service = new Service();
        $service->elementMap['{}p'] = 'Sabre\Xml\Deserializer\mixedContent';

        $xml = <<<XML
<?xml version="1.0"?>
<p>This is some text <extref>and a inline tag</extref>and even more text</p>
XML;

        $result = $service->parse($xml);

        $expected = [
            'This is some text ',
            [
                'name' => '{}extref',
                'value' => 'and a inline tag',
                'attributes' => [],
            ],
            'and even more text',
        ];

        $this->assertEquals($expected, $result);
    }
}
