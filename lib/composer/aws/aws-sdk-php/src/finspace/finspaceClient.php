<?php
namespace Aws\finspace;

use Aws\AwsClient;
use Aws\CommandInterface;
use Psr\Http\Message\RequestInterface;

/**
 * This client is used to interact with the **FinSpace User Environment Management service** service.
 * @method \Aws\Result createEnvironment(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createEnvironmentAsync(array $args = [])
 * @method \Aws\Result deleteEnvironment(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteEnvironmentAsync(array $args = [])
 * @method \Aws\Result getEnvironment(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getEnvironmentAsync(array $args = [])
 * @method \Aws\Result listEnvironments(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listEnvironmentsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateEnvironment(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateEnvironmentAsync(array $args = [])
 */
class finspaceClient extends AwsClient {
    public function __construct(array $args)
    {
        parent::__construct($args);

        // Setup middleware.
        $stack = $this->getHandlerList();
        $stack->appendBuild($this->updateContentType(), 'models.finspace.updateContentType');
    }

    /**
     * Creates a middleware that updates the Content-Type header when it is present;
     * this is necessary because the service protocol is rest-json which by default
     * sets the content-type to 'application/json', but interacting with the service
     * requires it to be set to x-amz-json-1.1
     *
     * @return callable
     */
    private function updateContentType()
    {
        return function (callable $handler) {
            return function (
                CommandInterface $command,
                RequestInterface $request = null
            ) use ($handler) {
                $contentType = $request->getHeader('Content-Type');
                if (!empty($contentType) && $contentType[0] == 'application/json') {
                    return $handler($command, $request->withHeader(
                        'Content-Type',
                        'application/x-amz-json-1.1'
                    ));
                }
                return $handler($command, $request);
            };
        };
    }
}
