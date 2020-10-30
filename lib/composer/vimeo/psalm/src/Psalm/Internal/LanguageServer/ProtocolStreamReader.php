<?php
declare(strict_types = 1);
namespace Psalm\Internal\LanguageServer;

use AdvancedJsonRpc\Message as MessageBody;
use function Amp\asyncCall;
use Amp\Promise;
use Amp\ByteStream\ResourceInputStream;
use Exception;
use function explode;
use function strlen;
use function substr;
use function trim;

/**
 * Source: https://github.com/felixfbecker/php-language-server/tree/master/src/ProtocolStreamReader.php
 */
class ProtocolStreamReader implements ProtocolReader
{
    use EmitterTrait;

    private const PARSE_HEADERS = 1;
    private const PARSE_BODY = 2;

    /**
     * This is checked by ProtocolStreamReader so that it will stop reading from streams in the forked process.
     * There could be buffered bytes in stdin/over TCP, those would be processed by TCP if it were not for this check.
     *
     * @var bool
     */
    private $is_accepting_new_requests = true;
    /** @var int */
    private $parsing_mode = self::PARSE_HEADERS;
    /** @var string */
    private $buffer = '';
    /** @var string[] */
    private $headers = [];
    /** @var ?int */
    private $content_length = null;
    /** @var bool */
    private $did_emit_close = false;

    /**
     * @param resource $input
     */
    public function __construct($input)
    {
        $input = new ResourceInputStream($input);
        asyncCall(
            /**
             * @return \Generator<int, Promise<?string>, ?string, void>
             * @psalm-suppress MixedReturnTypeCoercion
             * @psalm-suppress MixedArgument in old Amp versions
             * @psalm-suppress MixedAssignment in old Amp versions
             */
            function () use ($input) : \Generator {
                while ($this->is_accepting_new_requests) {
                    $read_promise = $input->read();

                    $chunk = yield $read_promise;

                    if ($chunk === null) {
                        break;
                    }

                    if ($this->readMessages($chunk) > 0) {
                        $this->emit('readMessageGroup');
                    }
                }

                $this->emitClose();
            }
        );

        $this->on(
            'close',
            static function () use ($input): void {
                $input->close();
            }
        );
    }

    private function readMessages(string $buffer) : int
    {
        $emitted_messages = 0;
        $i = 0;
        while (($buffer[$i] ?? '') !== '') {
            $this->buffer .= $buffer[$i++];
            switch ($this->parsing_mode) {
                case self::PARSE_HEADERS:
                    if ($this->buffer === "\r\n") {
                        $this->parsing_mode = self::PARSE_BODY;
                        $this->content_length = (int) ($this->headers['Content-Length'] ?? 0);
                        $this->buffer = '';
                    } elseif (substr($this->buffer, -2) === "\r\n") {
                        $parts = explode(':', $this->buffer);
                        $this->headers[$parts[0]] = trim($parts[1]);
                        $this->buffer = '';
                    }
                    break;
                case self::PARSE_BODY:
                    if (strlen($this->buffer) === $this->content_length) {
                        if (!$this->is_accepting_new_requests) {
                            // If we fork, don't read any bytes in the input buffer from the worker process.
                            $this->emitClose();

                            return $emitted_messages;
                        }
                        // MessageBody::parse can throw an Error, maybe log an error?
                        try {
                            $msg = new Message(MessageBody::parse($this->buffer), $this->headers);
                        } catch (Exception $_) {
                            $msg = null;
                        }
                        if ($msg) {
                            ++$emitted_messages;
                            $this->emit('message', [$msg]);
                            /** @psalm-suppress DocblockTypeContradiction */
                            if (!$this->is_accepting_new_requests) {
                                // If we fork, don't read any bytes in the input buffer from the worker process.
                                $this->emitClose();

                                return $emitted_messages;
                            }
                        }
                        $this->parsing_mode = self::PARSE_HEADERS;
                        $this->headers = [];
                        $this->buffer = '';
                    }
                    break;
            }
        }

        return $emitted_messages;
    }

    private function emitClose(): void
    {
        if ($this->did_emit_close) {
            return;
        }
        $this->did_emit_close = true;
        $this->emit('close');
    }
}
