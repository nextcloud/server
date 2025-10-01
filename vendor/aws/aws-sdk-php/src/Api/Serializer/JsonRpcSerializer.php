<?php
namespace Aws\Api\Serializer;

use Aws\Api\Service;
use Aws\CommandInterface;
use Aws\EndpointV2\EndpointV2SerializerTrait;
use Aws\EndpointV2\Ruleset\RulesetEndpoint;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;

/**
 * Prepares a JSON-RPC request for transfer.
 * @internal
 */
class JsonRpcSerializer
{
    use EndpointV2SerializerTrait;

    /** @var JsonBody */
    private $jsonFormatter;

    /** @var string */
    private $endpoint;

    /** @var Service */
    private $api;

    /** @var string */
    private $contentType;

    /**
     * @param Service  $api           Service description
     * @param string   $endpoint      Endpoint to connect to
     * @param JsonBody $jsonFormatter Optional JSON formatter to use
     */
    public function __construct(
        Service $api,
        $endpoint,
        ?JsonBody $jsonFormatter = null
    ) {
        $this->endpoint = $endpoint;
        $this->api = $api;
        $this->jsonFormatter = $jsonFormatter ?: new JsonBody($this->api);
        $this->contentType = JsonBody::getContentType($api);
    }

    /**
     * When invoked with an AWS command, returns a serialization array
     * containing "method", "uri", "headers", and "body" key value pairs.
     *
     * @param CommandInterface $command Command to serialize into a request.
     * @param $endpointProvider Provider used for dynamic endpoint resolution.
     * @param $clientArgs Client arguments used for dynamic endpoint resolution.
     *
     * @return RequestInterface
     */
    public function __invoke(
        CommandInterface $command,
        $endpoint = null
    )
    {
        $operationName = $command->getName();
        $operation = $this->api->getOperation($operationName);
        $commandArgs = $command->toArray();
        $headers = [
                'X-Amz-Target' => $this->api->getMetadata('targetPrefix') . '.' . $operationName,
                'Content-Type' => $this->contentType
            ];

        if ($endpoint instanceof RulesetEndpoint) {
            $this->setEndpointV2RequestOptions($endpoint, $headers);
        }

        return new Request(
            $operation['http']['method'],
            $this->endpoint,
            $headers,
            $this->jsonFormatter->build(
                $operation->getInput(),
                $commandArgs
            )
        );
    }
}
