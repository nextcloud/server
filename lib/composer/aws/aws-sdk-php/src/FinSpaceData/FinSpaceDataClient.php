<?php
namespace Aws\FinSpaceData;

use Aws\AwsClient;
use Aws\CommandInterface;
use Psr\Http\Message\RequestInterface;

/**
 * This client is used to interact with the **FinSpace Public API** service.
 * @method \Aws\Result createChangeset(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createChangesetAsync(array $args = [])
 * @method \Aws\Result getProgrammaticAccessCredentials(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getProgrammaticAccessCredentialsAsync(array $args = [])
 * @method \Aws\Result getWorkingLocation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getWorkingLocationAsync(array $args = [])
 */
class FinSpaceDataClient extends AwsClient {
    public function __construct(array $args)
    {
        parent::__construct($args);

        // Setup middleware.
        $stack = $this->getHandlerList();
        $stack->appendBuild($this->updateContentType(), 'models.finspaceData.updateContentType');
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
