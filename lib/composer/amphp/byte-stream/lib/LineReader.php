<?php

namespace Amp\ByteStream;

use Amp\Promise;
use function Amp\call;

final class LineReader
{
    /** @var string */
    private $delimiter;

    /** @var bool */
    private $lineMode;

    /** @var string */
    private $buffer = "";

    /** @var InputStream */
    private $source;

    public function __construct(InputStream $inputStream, string $delimiter = null)
    {
        $this->source = $inputStream;
        $this->delimiter = $delimiter === null ? "\n" : $delimiter;
        $this->lineMode = $delimiter === null;
    }

    /**
     * @return Promise<string|null>
     */
    public function readLine(): Promise
    {
        return call(function () {
            if (false !== \strpos($this->buffer, $this->delimiter)) {
                list($line, $this->buffer) = \explode($this->delimiter, $this->buffer, 2);
                return $this->lineMode ? \rtrim($line, "\r") : $line;
            }

            while (null !== $chunk = yield $this->source->read()) {
                $this->buffer .= $chunk;

                if (false !== \strpos($this->buffer, $this->delimiter)) {
                    list($line, $this->buffer) = \explode($this->delimiter, $this->buffer, 2);
                    return $this->lineMode ? \rtrim($line, "\r") : $line;
                }
            }

            if ($this->buffer === "") {
                return null;
            }

            $line = $this->buffer;
            $this->buffer = "";
            return $this->lineMode ? \rtrim($line, "\r") : $line;
        });
    }

    public function getBuffer(): string
    {
        return $this->buffer;
    }

    /**
     * @return void
     */
    public function clearBuffer()
    {
        $this->buffer = "";
    }
}
