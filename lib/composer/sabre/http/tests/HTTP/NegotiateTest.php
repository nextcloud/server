<?php

declare(strict_types=1);

namespace Sabre\HTTP;

class NegotiateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider negotiateData
     */
    public function testNegotiate($acceptHeader, $available, $expected)
    {
        $this->assertEquals(
            $expected,
            negotiateContentType($acceptHeader, $available)
        );
    }

    public function negotiateData()
    {
        return [
            [ // simple
                'application/xml',
                ['application/xml'],
                'application/xml',
            ],
            [ // no header
                null,
                ['application/xml'],
                'application/xml',
            ],
            [ // 2 options
                'application/json',
                ['application/xml', 'application/json'],
                'application/json',
            ],
            [ // 2 choices
                'application/json, application/xml',
                ['application/xml'],
                'application/xml',
            ],
            [ // quality
                'application/xml;q=0.2, application/json',
                ['application/xml', 'application/json'],
                'application/json',
            ],
            [ // wildcard
                'image/jpeg, image/png, */*',
                ['application/xml', 'application/json'],
                'application/xml',
            ],
            [ // wildcard + quality
                'image/jpeg, image/png; q=0.5, */*',
                ['application/xml', 'application/json', 'image/png'],
                'application/xml',
            ],
            [ // no match
                'image/jpeg',
                ['application/xml'],
                null,
            ],
            [ // This is used in sabre/dav
                'text/vcard; version=4.0',
                [
                    // Most often used mime-type. Version 3
                    'text/x-vcard',
                    // The correct standard mime-type. Defaults to version 3 as
                    // well.
                    'text/vcard',
                    // vCard 4
                    'text/vcard; version=4.0',
                    // vCard 3
                    'text/vcard; version=3.0',
                    // jCard
                    'application/vcard+json',
                ],
                'text/vcard; version=4.0',
            ],
            [ // rfc7231 example 1
                'audio/*; q=0.2, audio/basic',
                [
                    'audio/pcm',
                    'audio/basic',
                ],
                'audio/basic',
            ],
            [ // Lower quality after
                'audio/pcm; q=0.2, audio/basic; q=0.1',
                [
                    'audio/pcm',
                    'audio/basic',
                ],
                'audio/pcm',
            ],
            [ // Random parameter, should be ignored
                'audio/pcm; hello; q=0.2, audio/basic; q=0.1',
                [
                    'audio/pcm',
                    'audio/basic',
                ],
                'audio/pcm',
            ],
            [ // No whitepace after type, should pick the one that is the most specific.
                'text/vcard;version=3.0, text/vcard',
                [
                    'text/vcard',
                    'text/vcard; version=3.0',
                ],
                'text/vcard; version=3.0',
            ],
            [ // Same as last one, but order is different
                'text/vcard, text/vcard;version=3.0',
                [
                    'text/vcard; version=3.0',
                    'text/vcard',
                ],
                'text/vcard; version=3.0',
            ],
            [ // Charset should be ignored here.
                'text/vcard; charset=utf-8; version=3.0, text/vcard',
                [
                    'text/vcard',
                    'text/vcard; version=3.0',
                ],
                'text/vcard; version=3.0',
            ],
            [ // Undefined offset issue.
                'text/html, image/gif, image/jpeg, *; q=.2, */*; q=.2',
                ['application/xml', 'application/json', 'image/png'],
                'application/xml',
            ],
        ];
    }
}
