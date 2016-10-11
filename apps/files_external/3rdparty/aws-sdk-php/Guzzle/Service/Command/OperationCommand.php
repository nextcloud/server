<?php

namespace Guzzle\Service\Command;

/**
 * A command that creates requests based on {@see Guzzle\Service\Description\OperationInterface} objects, and if the
 * matching operation uses a service description model in the responseClass attribute, then this command will marshal
 * the response into an associative array based on the JSON schema of the model.
 */
class OperationCommand extends AbstractCommand
{
    /** @var RequestSerializerInterface */
    protected $requestSerializer;

    /** @var ResponseParserInterface Response parser */
    protected $responseParser;

    /**
     * Set the response parser used with the command
     *
     * @param ResponseParserInterface $parser Response parser
     *
     * @return self
     */
    public function setResponseParser(ResponseParserInterface $parser)
    {
        $this->responseParser = $parser;

        return $this;
    }

    /**
     * Set the request serializer used with the command
     *
     * @param RequestSerializerInterface $serializer Request serializer
     *
     * @return self
     */
    public function setRequestSerializer(RequestSerializerInterface $serializer)
    {
        $this->requestSerializer = $serializer;

        return $this;
    }

    /**
     * Get the request serializer used with the command
     *
     * @return RequestSerializerInterface
     */
    public function getRequestSerializer()
    {
        if (!$this->requestSerializer) {
            // Use the default request serializer if none was found
            $this->requestSerializer = DefaultRequestSerializer::getInstance();
        }

        return $this->requestSerializer;
    }

    /**
     * Get the response parser used for the operation
     *
     * @return ResponseParserInterface
     */
    public function getResponseParser()
    {
        if (!$this->responseParser) {
            // Use the default response parser if none was found
            $this->responseParser = OperationResponseParser::getInstance();
        }

        return $this->responseParser;
    }

    protected function build()
    {
        // Prepare and serialize the request
        $this->request = $this->getRequestSerializer()->prepare($this);
    }

    protected function process()
    {
        // Do not process the response if 'command.response_processing' is set to 'raw'
        $this->result = $this[self::RESPONSE_PROCESSING] == self::TYPE_RAW
            ? $this->request->getResponse()
            : $this->getResponseParser()->parse($this);
    }
}
