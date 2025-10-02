<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Process\Pipes;

use Symfony\Component\Process\Exception\InvalidArgumentException;

/**
 * @author Romain Neutron <imprec@gmail.com>
 *
 * @internal
 */
abstract class AbstractPipes implements PipesInterface
{
    public array $pipes = [];

    private string $inputBuffer = '';
    /** @var resource|string|\Iterator */
    private $input;
    private bool $blocked = true;
    private ?string $lastError = null;

    /**
     * @param resource|string|\Iterator $input
     */
    public function __construct($input)
    {
        if (\is_resource($input) || $input instanceof \Iterator) {
            $this->input = $input;
        } else {
            $this->inputBuffer = (string) $input;
        }
    }

    public function close(): void
    {
        foreach ($this->pipes as $pipe) {
            if (\is_resource($pipe)) {
                fclose($pipe);
            }
        }
        $this->pipes = [];
    }

    /**
     * Returns true if a system call has been interrupted.
     */
    protected function hasSystemCallBeenInterrupted(): bool
    {
        $lastError = $this->lastError;
        $this->lastError = null;

        // stream_select returns false when the `select` system call is interrupted by an incoming signal
        return null !== $lastError && false !== stripos($lastError, 'interrupted system call');
    }

    /**
     * Unblocks streams.
     */
    protected function unblock(): void
    {
        if (!$this->blocked) {
            return;
        }

        foreach ($this->pipes as $pipe) {
            stream_set_blocking($pipe, 0);
        }
        if (\is_resource($this->input)) {
            stream_set_blocking($this->input, 0);
        }

        $this->blocked = false;
    }

    /**
     * Writes input to stdin.
     *
     * @throws InvalidArgumentException When an input iterator yields a non supported value
     */
    protected function write(): ?array
    {
        if (!isset($this->pipes[0])) {
            return null;
        }
        $input = $this->input;

        if ($input instanceof \Iterator) {
            if (!$input->valid()) {
                $input = null;
            } elseif (\is_resource($input = $input->current())) {
                stream_set_blocking($input, 0);
            } elseif (!isset($this->inputBuffer[0])) {
                if (!\is_string($input)) {
                    if (!\is_scalar($input)) {
                        throw new InvalidArgumentException(sprintf('"%s" yielded a value of type "%s", but only scalars and stream resources are supported.', get_debug_type($this->input), get_debug_type($input)));
                    }
                    $input = (string) $input;
                }
                $this->inputBuffer = $input;
                $this->input->next();
                $input = null;
            } else {
                $input = null;
            }
        }

        $r = $e = [];
        $w = [$this->pipes[0]];

        // let's have a look if something changed in streams
        if (false === @stream_select($r, $w, $e, 0, 0)) {
            return null;
        }

        foreach ($w as $stdin) {
            if (isset($this->inputBuffer[0])) {
                $written = fwrite($stdin, $this->inputBuffer);
                $this->inputBuffer = substr($this->inputBuffer, $written);
                if (isset($this->inputBuffer[0])) {
                    return [$this->pipes[0]];
                }
            }

            if ($input) {
                while (true) {
                    $data = fread($input, self::CHUNK_SIZE);
                    if (!isset($data[0])) {
                        break;
                    }
                    $written = fwrite($stdin, $data);
                    $data = substr($data, $written);
                    if (isset($data[0])) {
                        $this->inputBuffer = $data;

                        return [$this->pipes[0]];
                    }
                }
                if (feof($input)) {
                    if ($this->input instanceof \Iterator) {
                        $this->input->next();
                    } else {
                        $this->input = null;
                    }
                }
            }
        }

        // no input to read on resource, buffer is empty
        if (!isset($this->inputBuffer[0]) && !($this->input instanceof \Iterator ? $this->input->valid() : $this->input)) {
            $this->input = null;
            fclose($this->pipes[0]);
            unset($this->pipes[0]);
        } elseif (!$w) {
            return [$this->pipes[0]];
        }

        return null;
    }

    /**
     * @internal
     */
    public function handleError(int $type, string $msg): void
    {
        $this->lastError = $msg;
    }
}
