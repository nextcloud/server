<?php
namespace Aws\S3Control;

use Aws\CommandInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Used to update the URL used for S3 Control requests to support S3 Control
 * DualStack. It will build to host style paths, including for S3 Control
 * DualStack.
 *
 * IMPORTANT: this middleware must be added after the "build" step.
 *
 * @internal
 */
class S3ControlEndpointMiddleware
{
    /** @var bool */
    private $dualStackByDefault;
    /** @var string */
    private $region;
    /** @var callable */
    private $nextHandler;
    /** @var string|null */
    private $endpoint;

    /**
     * Create a middleware wrapper function
     *
     * @param string $region
     * @param array  $options
     *
     * @return callable
     */
    public static function wrap($region, array $options)
    {
        return function (callable $handler) use ($region, $options) {
            return new self($handler, $region, $options);
        };
    }

    public function __construct(
        callable $nextHandler,
        $region,
        array $options
    ) {
        $this->dualStackByDefault = isset($options['dual_stack'])
            ? (bool) $options['dual_stack'] : false;
        $this->region = (string) $region;
        $this->nextHandler = $nextHandler;
    }

    public function __invoke(CommandInterface $command, RequestInterface $request)
    {
        if ($this->isDualStackRequest($command, $request)) {
            $request = $this->applyDualStackEndpoint($command, $request);
        }

        $nextHandler = $this->nextHandler;
        return $nextHandler($command, $request);
    }

    private function isDualStackRequest(
        CommandInterface $command,
        RequestInterface $request
    ) {
        return isset($command['@use_dual_stack_endpoint'])
            ? $command['@use_dual_stack_endpoint'] : $this->dualStackByDefault;
    }

    private function getDualStackHost($host)
    {
        $parts = explode(".{$this->region}.", $host);
        return $parts[0] . ".dualstack.{$this->region}." . $parts[1];
    }

    private function applyDualStackEndpoint(
        CommandInterface $command,
        RequestInterface $request
    ) {
        $uri = $request->getUri();
        return $request->withUri(
            $uri->withHost($this->getDualStackHost(
                $uri->getHost()
            ))
        );
    }
}
