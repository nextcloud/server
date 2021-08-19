<?php
namespace Aws\Multipart;

use Aws\AwsClientInterface as Client;
use Aws\Exception\AwsException;
use GuzzleHttp\Psr7;
use InvalidArgumentException as IAE;
use Psr\Http\Message\StreamInterface as Stream;

abstract class AbstractUploader extends AbstractUploadManager
{
    /** @var Stream Source of the data to be uploaded. */
    protected $source;

    /**
     * @param Client $client
     * @param mixed  $source
     * @param array  $config
     */
    public function __construct(Client $client, $source, array $config = [])
    {
        $this->source = $this->determineSource($source);
        parent::__construct($client, $config);
    }

    /**
     * Create a stream for a part that starts at the current position and
     * has a length of the upload part size (or less with the final part).
     *
     * @param Stream $stream
     *
     * @return Psr7\LimitStream
     */
    protected function limitPartStream(Stream $stream)
    {
        // Limit what is read from the stream to the part size.
        return new Psr7\LimitStream(
            $stream,
            $this->state->getPartSize(),
            $this->source->tell()
        );
    }

    protected function getUploadCommands(callable $resultHandler)
    {
        // Determine if the source can be seeked.
        $seekable = $this->source->isSeekable()
            && $this->source->getMetadata('wrapper_type') === 'plainfile';

        for ($partNumber = 1; $this->isEof($seekable); $partNumber++) {
            // If we haven't already uploaded this part, yield a new part.
            if (!$this->state->hasPartBeenUploaded($partNumber)) {
                $partStartPos = $this->source->tell();
                if (!($data = $this->createPart($seekable, $partNumber))) {
                    break;
                }
                $command = $this->client->getCommand(
                    $this->info['command']['upload'],
                    $data + $this->state->getId()
                );
                $command->getHandlerList()->appendSign($resultHandler, 'mup');
                $numberOfParts = $this->getNumberOfParts($this->state->getPartSize());
                if (isset($numberOfParts) && $partNumber > $numberOfParts) {
                    throw new $this->config['exception_class'](
                        $this->state,
                        new AwsException(
                            "Maximum part number for this job exceeded, file has likely been corrupted." .
                            "  Please restart this upload.",
                            $command
                        )
                    );
                }

                yield $command;
                if ($this->source->tell() > $partStartPos) {
                    continue;
                }
            }

            // Advance the source's offset if not already advanced.
            if ($seekable) {
                $this->source->seek(min(
                    $this->source->tell() + $this->state->getPartSize(),
                    $this->source->getSize()
                ));
            } else {
                $this->source->read($this->state->getPartSize());
            }
        }
    }

    /**
     * Generates the parameters for an upload part by analyzing a range of the
     * source starting from the current offset up to the part size.
     *
     * @param bool $seekable
     * @param int  $number
     *
     * @return array|null
     */
    abstract protected function createPart($seekable, $number);

    /**
     * Checks if the source is at EOF.
     *
     * @param bool $seekable
     *
     * @return bool
     */
    private function isEof($seekable)
    {
        return $seekable
            ? $this->source->tell() < $this->source->getSize()
            : !$this->source->eof();
    }

    /**
     * Turns the provided source into a stream and stores it.
     *
     * If a string is provided, it is assumed to be a filename, otherwise, it
     * passes the value directly to `Psr7\Utils::streamFor()`.
     *
     * @param mixed $source
     *
     * @return Stream
     */
    private function determineSource($source)
    {
        // Use the contents of a file as the data source.
        if (is_string($source)) {
            $source = Psr7\Utils::tryFopen($source, 'r');
        }

        // Create a source stream.
        $stream = Psr7\Utils::streamFor($source);
        if (!$stream->isReadable()) {
            throw new IAE('Source stream must be readable.');
        }

        return $stream;
    }

    protected function getNumberOfParts($partSize)
    {
        if ($sourceSize = $this->source->getSize()) {
            return ceil($sourceSize/$partSize);
        }
        return null;
    }
}
