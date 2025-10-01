<?php
namespace Aws;

use Aws\Api\Service;
use Psr\Http\Message\RequestInterface;

/**
 * @internal Middleware that auto fills parameters with `idempotencyToken` trait
 */
class IdempotencyTokenMiddleware
{
    /** @var Service */
    private $service;

    /** @var string */
    private $bytesGenerator;

    /** @var callable */
    private $nextHandler;

    /**
     * Creates a middleware that populates operation parameter
     * with trait 'idempotencyToken' enabled with a random UUIDv4
     *
     * One of following functions needs to be available
     * in order to generate random bytes used for UUID
     * (SDK will attempt to utilize function in following order):
     *  - random_bytes (requires PHP 7.0 or above)
     *  - openssl_random_pseudo_bytes (requires 'openssl' module enabled)
     *  - mcrypt_create_iv (requires 'mcrypt' module enabled)
     *
     * You may also supply a custom bytes generator as an optional second
     * parameter.
     *
     * @param \Aws\Api\Service $service
     * @param callable|null $bytesGenerator
     *
     * @return callable
     */
    public static function wrap(
        Service $service,
        ?callable $bytesGenerator = null
    ) {
        return function (callable $handler) use ($service, $bytesGenerator) {
            return new self($handler, $service, $bytesGenerator);
        };
    }

    public function __construct(
        callable $nextHandler,
        Service $service,
        ?callable $bytesGenerator = null
    ) {
        $this->bytesGenerator = $bytesGenerator
            ?: $this->findCompatibleRandomSource();
        $this->service = $service;
        $this->nextHandler = $nextHandler;
    }

    public function __invoke(
        CommandInterface $command,
        ?RequestInterface $request = null
    ) {
        $handler = $this->nextHandler;
        if ($this->bytesGenerator) {
            $operation = $this->service->getOperation($command->getName());
            $members = $operation->getInput()->getMembers();
            foreach ($members as $member => $value) {
                if ($value['idempotencyToken']) {
                    $bytes = call_user_func($this->bytesGenerator, 16);
                    // populating UUIDv4 only when the parameter is not set
                    $command[$member] = $command[$member]
                        ?: $this->getUuidV4($bytes);
                    // only one member could have the trait enabled
                    break;
                }
            }
        }
        return $handler($command, $request);
    }

    /**
     * This function generates a random UUID v4 string,
     * which is used as auto filled token value.
     *
     * @param string $bytes 16 bytes of pseudo-random bytes
     * @return string
     * More information about UUID v4, see:
     * https://en.wikipedia.org/wiki/Universally_unique_identifier#Version_4_.28random.29
     * https://tools.ietf.org/html/rfc4122#page-14
     */
    private static function getUuidV4($bytes)
    {
        // set version to 0100
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        // set bits 6-7 to 10
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * This function decides the PHP function used in generating random bytes.
     *
     * @return callable|null
     */
    private function findCompatibleRandomSource()
    {
        if (function_exists('random_bytes')) {
            return 'random_bytes';
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            return 'openssl_random_pseudo_bytes';
        }

        if (function_exists('mcrypt_create_iv')) {
            return 'mcrypt_create_iv';
        }
    }
}
