<?php

namespace Aws\S3\Parser;

use Aws\Api\ErrorParser\XmlErrorParser;
use Aws\Api\Parser\AbstractParser;
use Aws\Api\Parser\Exception\ParserException;
use Aws\Api\Service;
use Aws\Api\StructureShape;
use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\ResultInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Custom S3 parser on top of the S3 protocol parser
 * for handling specific S3 parsing scenarios.
 *
 * @internal
 */
final class S3Parser extends AbstractParser
{
     /** @var AbstractParser */
    private $protocolParser;
    /**  @var XmlErrorParser */
    private $errorParser;
    /** @var string */
    private $exceptionClass;
    /** @var array */
    private $s3ResultMutators;

    /**
     * @param AbstractParser $protocolParser
     * @param XmlErrorParser $errorParser
     * @param Service $api
     * @param string $exceptionClass
     */
    public function __construct(
        AbstractParser $protocolParser,
        XmlErrorParser $errorParser,
        Service $api,
        string $exceptionClass = AwsException::class
    )
    {
        parent::__construct($api);
        $this->protocolParser = $protocolParser;
        $this->errorParser = $errorParser;
        $this->exceptionClass = $exceptionClass;
        $this->s3ResultMutators = [];
    }

    /**
     * Parses a S3 response.
     *
     * @param CommandInterface $command The command that originated the request.
     * @param ResponseInterface $response The response received from the service.
     *
     * @return ResultInterface|null
     */
    public function __invoke(
        CommandInterface $command,
        ResponseInterface $response
    ):? ResultInterface
    {
        // Check first if the response is an error
        $this->parse200Error($command, $response);

        try {
            $parseFn = $this->protocolParser;
            $result = $parseFn($command, $response);
        } catch (ParserException $e) {
            // Parsing errors will be considered retryable.
            throw new $this->exceptionClass(
                "Error parsing response for {$command->getName()}:"
                . " AWS parsing error: {$e->getMessage()}",
                $command,
                ['connection_error' => true, 'exception' => $e],
                $e
            );
        }

        return $this->executeS3ResultMutators($result, $command, $response);
    }

    /**
     * Tries to parse a 200 response as an error from S3.
     * If the parsed result contains a code and message then that means an error
     * was found, and hence an exception is thrown with that error.
     *
     * @param CommandInterface $command
     * @param ResponseInterface $response
     *
     * @return void
     */
    private function parse200Error(
        CommandInterface $command,
        ResponseInterface $response
    ): void
    {
        // This error parsing should be just for 200 error responses
        // and operations where its output shape does not have a streaming
        // member and the body of the response is seekable.
        if (200 !== $response->getStatusCode()
            || !$this->shouldBeConsidered200Error($command->getName())
            || !$response->getBody()->isSeekable()) {
            return;
        }

        // To guarantee we try the error parsing just for an Error xml response.
        if (!$this->isFirstRootElementError($response->getBody())) {
            return;
        }

        try {
            $errorParserFn = $this->errorParser;
            $parsedError = $errorParserFn($response, $command);
        } catch (ParserException $e) {
            // Parsing errors will be considered retryable.
            $parsedError = [
                'code' => 'ConnectionError',
                'message' => "An error connecting to the service occurred"
                    . " while performing the " . $command->getName()
                    . " operation."
            ];
        }

        if (isset($parsedError['code']) && isset($parsedError['message'])) {
            throw new $this->exceptionClass(
                $parsedError['message'],
                $command,
                [
                    'connection_error' => true,
                    'code' => $parsedError['code'],
                    'message' => $parsedError['message']
                ]
            );
        }
    }

    /**
     * Checks if a specific operation should be considered
     * a s3 200 error. Operations where any of its output members
     * has a streaming or httpPayload trait should be not considered.
     *
     * @param $commandName
     *
     * @return bool
     */
    private function shouldBeConsidered200Error($commandName): bool
    {
        $operation = $this->api->getOperation($commandName);
        $output = $operation->getOutput();
        foreach ($output->getMembers() as $_ => $memberProps) {
            if (!empty($memberProps['eventstream']) || !empty($memberProps['streaming'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if the root element of the response body is "Error", which is
     * when we should try to parse an error from a 200 response from s3.
     * It is recommended to make sure the stream given is seekable, otherwise
     * the rewind call will cause a user warning.
     *
     * @param StreamInterface $responseBody
     *
     * @return bool
     */
    private function isFirstRootElementError(StreamInterface $responseBody): bool
    {
        static $pattern = '/<\?xml version="1\.0" encoding="UTF-8"\?>\s*<Error>/';
        // To avoid performance overhead in large streams
        $reducedBodyContent = $responseBody->read(64);
        $foundErrorElement = preg_match($pattern, $reducedBodyContent);
        // A rewind is needed because the stream is partially or entirely consumed
        // in the previous read operation.
        $responseBody->rewind();

        return $foundErrorElement;
    }

    /**
     * Execute mutator implementations over a result.
     * Mutators are logics that modifies a result.
     *
     * @param ResultInterface $result
     * @param CommandInterface $command
     * @param ResponseInterface $response
     *
     * @return ResultInterface
     */
    private function executeS3ResultMutators(
        ResultInterface $result,
        CommandInterface $command,
        ResponseInterface $response
    ): ResultInterface
    {
        foreach ($this->s3ResultMutators as $mutator) {
            $result = $mutator($result, $command, $response);
        }

        return $result;
    }

    /**
     * Adds a mutator into the list of mutators.
     *
     * @param string $mutatorName
     * @param S3ResultMutator $s3ResultMutator
     * @return void
     */
    public function addS3ResultMutator(
        string $mutatorName,
        S3ResultMutator $s3ResultMutator
    ): void
    {
        if (isset($this->s3ResultMutators[$mutatorName])) {
            trigger_error(
                "The S3 Result Mutator {$mutatorName} already exists!",
                E_USER_WARNING
            );

            return;
        }

        $this->s3ResultMutators[$mutatorName] = $s3ResultMutator;
    }

    /**
     * Removes a mutator from the mutator list.
     *
     * @param string $mutatorName
     * @return void
     */
    public function removeS3ResultMutator(string $mutatorName): void
    {
        if (!isset($this->s3ResultMutators[$mutatorName])) {
            trigger_error(
                "The S3 Result Mutator {$mutatorName} does not exist!",
                E_USER_WARNING
            );

            return;
        }

        unset($this->s3ResultMutators[$mutatorName]);
    }

    /**
     * Returns the list of result mutators available.
     *
     * @return array
     */
    public function getS3ResultMutators(): array
    {
        return $this->s3ResultMutators;
    }

    public function parseMemberFromStream(
        StreamInterface $stream,
        StructureShape $member,
        $response
    )
    {
        return $this->protocolParser->parseMemberFromStream(
            $stream,
            $member,
            $response
        );
    }
}
