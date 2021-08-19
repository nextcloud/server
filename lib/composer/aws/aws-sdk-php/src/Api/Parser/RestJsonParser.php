<?php
namespace Aws\Api\Parser;

use Aws\Api\Service;
use Aws\Api\StructureShape;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @internal Implements REST-JSON parsing (e.g., Glacier, Elastic Transcoder)
 */
class RestJsonParser extends AbstractRestParser
{
    use PayloadParserTrait;

    /**
     * @param Service    $api    Service description
     * @param JsonParser $parser JSON body builder
     */
    public function __construct(Service $api, JsonParser $parser = null)
    {
        parent::__construct($api);
        $this->parser = $parser ?: new JsonParser();
    }

    protected function payload(
        ResponseInterface $response,
        StructureShape $member,
        array &$result
    ) {
        $jsonBody = $this->parseJson($response->getBody(), $response);

        if ($jsonBody) {
            $result += $this->parser->parse($member, $jsonBody);
        }
    }

    public function parseMemberFromStream(
        StreamInterface $stream,
        StructureShape $member,
        $response
    ) {
        $jsonBody = $this->parseJson($stream, $response);
        if ($jsonBody) {
            return $this->parser->parse($member, $jsonBody);
        }
        return [];
    }
}
