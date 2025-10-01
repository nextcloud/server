<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation;

/**
 * StreamedResponse represents a streamed HTTP response.
 *
 * A StreamedResponse uses a callback for its content.
 *
 * The callback should use the standard PHP functions like echo
 * to stream the response back to the client. The flush() function
 * can also be used if needed.
 *
 * @see flush()
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class StreamedResponse extends Response
{
    protected $callback;
    protected $streamed;
    private bool $headersSent;

    /**
     * @param int $status The HTTP status code (200 "OK" by default)
     */
    public function __construct(?callable $callback = null, int $status = 200, array $headers = [])
    {
        parent::__construct(null, $status, $headers);

        if (null !== $callback) {
            $this->setCallback($callback);
        }
        $this->streamed = false;
        $this->headersSent = false;
    }

    /**
     * Sets the PHP callback associated with this Response.
     *
     * @return $this
     */
    public function setCallback(callable $callback): static
    {
        $this->callback = $callback(...);

        return $this;
    }

    public function getCallback(): ?\Closure
    {
        if (!isset($this->callback)) {
            return null;
        }

        return ($this->callback)(...);
    }

    /**
     * This method only sends the headers once.
     *
     * @param positive-int|null $statusCode The status code to use, override the statusCode property if set and not null
     *
     * @return $this
     */
    public function sendHeaders(/* int $statusCode = null */): static
    {
        if ($this->headersSent) {
            return $this;
        }

        $statusCode = \func_num_args() > 0 ? func_get_arg(0) : null;
        if ($statusCode < 100 || $statusCode >= 200) {
            $this->headersSent = true;
        }

        return parent::sendHeaders($statusCode);
    }

    /**
     * This method only sends the content once.
     *
     * @return $this
     */
    public function sendContent(): static
    {
        if ($this->streamed) {
            return $this;
        }

        $this->streamed = true;

        if (!isset($this->callback)) {
            throw new \LogicException('The Response callback must be set.');
        }

        ($this->callback)();

        return $this;
    }

    /**
     * @return $this
     *
     * @throws \LogicException when the content is not null
     */
    public function setContent(?string $content): static
    {
        if (null !== $content) {
            throw new \LogicException('The content cannot be set on a StreamedResponse instance.');
        }

        $this->streamed = true;

        return $this;
    }

    public function getContent(): string|false
    {
        return false;
    }
}
