<?php

namespace HybridauthTest\Hybridauth\Data;

use Hybridauth\Data\Parser;

class ParserTest extends \PHPUnit\Framework\TestCase
{
    public function test_instance_of()
    {
        $parser = new Parser();

        $this->assertInstanceOf('\\Hybridauth\\Data\\Parser', $parser);
    }

    /**
     * @covers Parser::parse
     * @covers Parser::parseJson
     */
    public function test_parser_json()
    {
        $parser = new Parser();

        $object = new \StdClass();
        $object->id = 69;
        $object->slugs = ['Γεια σας', 'Bonjour', '안녕하세요'];

        $json = json_encode($object);

        //

        $result = $parser->parse($json);

        $this->assertInstanceOf('\\StdClass', $result);

        $this->assertEquals($result, $object);

        //

        $result = $parser->parseJson($json);

        $this->assertInstanceOf('\\StdClass', $result);

        $this->assertEquals($result, $object);
    }

    /**
     * @covers Parser::parse
     * @covers Parser::parseQueryString
     */
    public function test_parser_querystring()
    {
        $parser = new Parser();

        $object = new \StdClass();
        $object->id = 69;
        $object->slug = 'oauth';

        $string = 'id=69&slug=oauth';

        //

        $result = $parser->parse($string);

        $this->assertInstanceOf('\\StdClass', $result);

        $this->assertEquals($result, $object);

        //

        $result = $parser->parseQueryString($string);

        $this->assertInstanceOf('\\StdClass', $result);

        $this->assertEquals($result, $object);
    }
}
