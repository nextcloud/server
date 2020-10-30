<?php

namespace Amp\ByteStream;

use Amp\Promise;
use Amp\Success;
use function Amp\call;

final class InputStreamChain implements InputStream
{
    /** @var InputStream[] */
    private $streams;
    /** @var bool */
    private $reading = false;

    public function __construct(InputStream ...$streams)
    {
        $this->streams = $streams;
    }

    /** @inheritDoc */
    public function read(): Promise
    {
        if ($this->reading) {
            throw new PendingReadError;
        }

        if (!$this->streams) {
            return new Success(null);
        }

        return call(function () {
            $this->reading = true;

            try {
                while ($this->streams) {
                    $chunk = yield $this->streams[0]->read();
                    if ($chunk === null) {
                        \array_shift($this->streams);
                        continue;
                    }

                    return $chunk;
                }

                return null;
            } finally {
                $this->reading = false;
            }
        });
    }
}
