<?php
namespace Aws\Api\Serializer;

use Aws\Api\MapShape;
use Aws\Api\Service;
use Aws\Api\Operation;
use Aws\Api\Shape;
use Aws\Api\StructureShape;
use Aws\Api\TimestampShape;
use Aws\CommandInterface;
use Aws\EndpointV2\EndpointV2SerializerTrait;
use Aws\EndpointV2\Ruleset\RulesetEndpoint;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\RequestInterface;

/**
 * Serializes HTTP locations like header, uri, payload, etc...
 * @internal
 */
abstract class RestSerializer
{
    use EndpointV2SerializerTrait;

    /** @var Service */
    private $api;

    /** @var Uri */
    private $endpoint;

    /** @var bool */
    private $isUseEndpointV2;

    /**
     * @param Service $api      Service API description
     * @param string  $endpoint Endpoint to connect to
     */
    public function __construct(Service $api, $endpoint)
    {
        $this->api = $api;
        $this->endpoint = Psr7\Utils::uriFor($endpoint);
    }

    /**
     * @param CommandInterface $command Command to serialize into a request.
     * @param $clientArgs Client arguments used for dynamic endpoint resolution.
     *
     * @return RequestInterface
     */
    public function __invoke(
        CommandInterface $command,
        $endpoint = null
    )
    {
        $operation = $this->api->getOperation($command->getName());
        $commandArgs = $command->toArray();
        $opts = $this->serialize($operation, $commandArgs);
        $headers = isset($opts['headers']) ? $opts['headers'] : [];

        if ($endpoint instanceof RulesetEndpoint) {
            $this->isUseEndpointV2 = true;
            $this->setEndpointV2RequestOptions($endpoint, $headers);
        }

        $uri = $this->buildEndpoint($operation, $commandArgs, $opts);

        return new Request(
            $operation['http']['method'],
            $uri,
            $headers,
            isset($opts['body']) ? $opts['body'] : null
        );
    }

    /**
     * Modifies a hash of request options for a payload body.
     *
     * @param StructureShape   $member  Member to serialize
     * @param array            $value   Value to serialize
     * @param array            $opts    Request options to modify.
     */
    abstract protected function payload(
        StructureShape $member,
        array $value,
        array &$opts
    );

    private function serialize(Operation $operation, array $args)
    {
        $opts = [];
        $input = $operation->getInput();

        // Apply the payload trait if present
        if ($payload = $input['payload']) {
            $this->applyPayload($input, $payload, $args, $opts);
        }

        foreach ($args as $name => $value) {
            if ($input->hasMember($name)) {
                $member = $input->getMember($name);
                $location = $member['location'];
                if (!$payload && !$location) {
                    $bodyMembers[$name] = $value;
                } elseif ($location == 'header') {
                    $this->applyHeader($name, $member, $value, $opts);
                } elseif ($location == 'querystring') {
                    $this->applyQuery($name, $member, $value, $opts);
                } elseif ($location == 'headers') {
                    $this->applyHeaderMap($name, $member, $value, $opts);
                }
            }
        }

        if (isset($bodyMembers)) {
            $this->payload($operation->getInput(), $bodyMembers, $opts);
        } else if (!isset($opts['body']) && $this->hasPayloadParam($input, $payload)) {
            $this->payload($operation->getInput(), [], $opts);
        }

        return $opts;
    }

    private function applyPayload(StructureShape $input, $name, array $args, array &$opts)
    {
        if (!isset($args[$name])) {
            return;
        }

        $m = $input->getMember($name);

        if ($m['streaming'] ||
           ($m['type'] == 'string' || $m['type'] == 'blob')
        ) {
            // Streaming bodies or payloads that are strings are
            // always just a stream of data.
            $opts['body'] = Psr7\Utils::streamFor($args[$name]);
            return;
        }

        $this->payload($m, $args[$name], $opts);
    }

    private function applyHeader($name, Shape $member, $value, array &$opts)
    {
        if ($member->getType() === 'timestamp') {
            $timestampFormat = !empty($member['timestampFormat'])
                ? $member['timestampFormat']
                : 'rfc822';
            $value = TimestampShape::format($value, $timestampFormat);
        } elseif ($member->getType() === 'boolean') {
            $value = $value ? 'true' : 'false';
        }

        if ($member['jsonvalue']) {
            $value = json_encode($value);
            if (empty($value) && JSON_ERROR_NONE !== json_last_error()) {
                throw new \InvalidArgumentException('Unable to encode the provided value'
                    . ' with \'json_encode\'. ' . json_last_error_msg());
            }

            $value = base64_encode($value);
        }

        $opts['headers'][$member['locationName'] ?: $name] = $value;
    }

    /**
     * Note: This is currently only present in the Amazon S3 model.
     */
    private function applyHeaderMap($name, Shape $member, array $value, array &$opts)
    {
        $prefix = $member['locationName'];
        foreach ($value as $k => $v) {
            $opts['headers'][$prefix . $k] = $v;
        }
    }

    private function applyQuery($name, Shape $member, $value, array &$opts)
    {
        if ($member instanceof MapShape) {
            $opts['query'] = isset($opts['query']) && is_array($opts['query'])
                ? $opts['query'] + $value
                : $value;
        } elseif ($value !== null) {
            $type = $member->getType();
            if ($type === 'boolean') {
                $value = $value ? 'true' : 'false';
            } elseif ($type === 'timestamp') {
                $timestampFormat = !empty($member['timestampFormat'])
                    ? $member['timestampFormat']
                    : 'iso8601';
                $value = TimestampShape::format($value, $timestampFormat);
            }

            $opts['query'][$member['locationName'] ?: $name] = $value;
        }
    }

    private function buildEndpoint(Operation $operation, array $args, array $opts)
    {
        $serviceName = $this->api->getServiceName();
        // Create an associative array of variable definitions used in expansions
        $varDefinitions = $this->getVarDefinitions($operation, $args);

        $relative = preg_replace_callback(
            '/\{([^\}]+)\}/',
            function (array $matches) use ($varDefinitions) {
                $isGreedy = substr($matches[1], -1, 1) == '+';
                $k = $isGreedy ? substr($matches[1], 0, -1) : $matches[1];
                if (!isset($varDefinitions[$k])) {
                    return '';
                }

                if ($isGreedy) {
                    return str_replace('%2F', '/', rawurlencode($varDefinitions[$k]));
                }

                return rawurlencode($varDefinitions[$k]);
            },
            $operation['http']['requestUri']
        );

        // Add the query string variables or appending to one if needed.
        if (!empty($opts['query'])) {
           $relative = $this->appendQuery($opts['query'], $relative);
        }

        $path = $this->endpoint->getPath();

        if ($this->isUseEndpointV2 && $serviceName === 's3') {
            if (substr($path, -1) === '/' && $relative[0] === '/') {
                $path = rtrim($path, '/');
            }
            $relative = $path . $relative;

            if (strpos($relative, '../') !== false
                || substr($relative, -2) === '..'
            ) {
                if ($relative[0] !== '/') {
                    $relative = '/' . $relative;
                }

                return new Uri($this->endpoint->withPath('') . $relative);
            }
        }

        if (((!empty($relative) && $relative !== '/')
            && !$this->isUseEndpointV2)
            || (isset($serviceName) && str_starts_with($serviceName, 'geo-'))
        ) {
            $this->normalizePath($path);
        }

        // If endpoint has path, remove leading '/' to preserve URI resolution.
        if ($path && $relative[0] === '/') {
            $relative = substr($relative, 1);
        }

        //Append path to endpoint when leading '//...'
        // present as uri cannot be properly resolved
        if ($this->isUseEndpointV2 && strpos($relative, '//') === 0) {
            return new Uri($this->endpoint . $relative);
        }

        // Expand path place holders using Amazon's slightly different URI
        // template syntax.
        return UriResolver::resolve($this->endpoint, new Uri($relative));
    }

    /**
     * @param StructureShape $input
     */
    private function hasPayloadParam(StructureShape $input, $payload)
    {
        if ($payload) {
            $potentiallyEmptyTypes = ['blob','string'];
            if ($this->api->getMetadata('protocol') == 'rest-xml') {
                $potentiallyEmptyTypes[] = 'structure';
            }
            $payloadMember = $input->getMember($payload);
            if (in_array($payloadMember['type'], $potentiallyEmptyTypes)) {
                return false;
            }
        }
        foreach ($input->getMembers() as $member) {
            if (!isset($member['location'])) {
                return true;
            }
        }
        return false;
    }

    private function appendQuery($query, $endpoint)
    {
        $append = Psr7\Query::build($query);
        return $endpoint .= strpos($endpoint, '?') !== false ? "&{$append}" : "?{$append}";
    }

    private function getVarDefinitions($command, $args)
    {
        $varDefinitions = [];

        foreach ($command->getInput()->getMembers() as $name => $member) {
            if ($member['location'] == 'uri') {
                $varDefinitions[$member['locationName'] ?: $name] =
                    isset($args[$name])
                        ? $args[$name]
                        : null;
            }
        }
        return $varDefinitions;
    }

    /**
     * Appends trailing slash to non-empty paths with at least one segment
     * to ensure proper URI resolution
     *
     * @param string $path
     *
     * @return void
     */
    private function normalizePath(string $path): void
    {
        if (!empty($path) && $path !== '/' && substr($path, -1) !== '/') {
            $this->endpoint = $this->endpoint->withPath($path . '/');
        }
    }
}
