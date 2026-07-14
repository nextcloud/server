<?php

/*
 * SPDX-FileCopyrightText: PHPRedis contributors <https://github.com/phpredis/phpredis>
 * SPDX-License-Identifier: PHP-3.01
 * Source: https://raw.githubusercontent.com/phpredis/phpredis/refs/heads/develop/redis.stub.php
 */

/**
 * @generate-function-entries
 * @generate-legacy-arginfo
 * @generate-class-entries
 */

class Redis {
    /**
     * Returned by `\Redis::type()` when the key does not exist or has a type
     * we are not familiar with.
     *
     * @var int
     * @cvalue REDIS_NOT_FOUND
     *
     */
    public const REDIS_NOT_FOUND = UNKNOWN;

    /**
     * Returned by `\Redis::type()` when the key is a string.
     *
     * @var int
     * @cvalue REDIS_STRING
     *
     */
    public const REDIS_STRING = UNKNOWN;

    /**
     * Returned by `\Redis::type()` when the key is a set.
     *
     * @var int
     * @cvalue REDIS_SET
     *
     */
    public const REDIS_SET = UNKNOWN;

    /**
     * Returned by `\Redis::type()` when the key is a list.
     *
     * @var int
     * @cvalue REDIS_LIST
     *
     */
    public const REDIS_LIST = UNKNOWN;

    /**
     * Returned by `\Redis::type()` when the key is a sorted set.
     *
     * @var int
     * @cvalue REDIS_ZSET
     *
     */
    public const REDIS_ZSET = UNKNOWN;

    /**
     * Returned by `\Redis::type()` when the key is a hash.
     *
     * @var int
     * @cvalue REDIS_HASH
     *
     */
    public const REDIS_HASH = UNKNOWN;

    /**
     * Returned by `\Redis::type()` when the key is a stream.
     *
     * @var int
     * @cvalue REDIS_STREAM
     *
     */
    public const REDIS_STREAM = UNKNOWN;

    /**
     * Returned by `\Redis::type()` when the key is a vector set.
     *
     * @var int
     * @cvalue REDIS_VECTORSET
     *
     */
    public const REDIS_VECTORSET = UNKNOWN;

    /**
     * Returned from `\Redis::getMode()` when we're not in a multi or pipeline
     * transaction.
     *
     * @var int
     * @cvalue ATOMIC
     *
     */
    public const ATOMIC = UNKNOWN;

    /**
     * Returned from `\Redis::getMode()` when we're in a multi transaction.
     *
     * @var int
     * @cvalue MULTI
     *
     */
    public const MULTI = UNKNOWN;

    /**
     * Returned from `\Redis::getMode()` when we're in a pipeline transaction.
     *
     * @var int
     * @cvalue PIPELINE
     *
     */
    public const PIPELINE = UNKNOWN;

    /**
     * Used with `\Redis::setOption()` to specify the serializer to use
     *
     * @var int
     * @cvalue REDIS_OPT_SERIALIZER
     *
     */
    public const OPT_SERIALIZER = UNKNOWN;

    /**
     * Used to set an automatic prefix for keys used in commands.
     *
     * @var int
     * @cvalue REDIS_OPT_PREFIX
     *
     */
    public const OPT_PREFIX = UNKNOWN;

    /**
     * Used to set the read timeout for the connection.
     *
     * @var int
     * @cvalue REDIS_OPT_READ_TIMEOUT
     *
     */
    public const OPT_READ_TIMEOUT = UNKNOWN;

    /**
     * Used to enable or disable TCP keepalive on the connection.
     *
     * @var int
     * @cvalue REDIS_OPT_TCP_KEEPALIVE
     *
     */
    public const OPT_TCP_KEEPALIVE = UNKNOWN;

    /**
     * Used to set the compression algorithm to use for compressing
     *
     * @var int
     * @cvalue REDIS_OPT_COMPRESSION
     *
     */
    public const OPT_COMPRESSION = UNKNOWN;

    /**
     * Causes PhpRedis to return the actual string in `+OK` style responses
     * from Redis. If disabled those replies are just converted to boolean
     * true.
     *
     * @var int
     * @cvalue REDIS_OPT_REPLY_LITERAL
     *
     */
    public const OPT_REPLY_LITERAL = UNKNOWN;

    /**
     * Used to specify the compression level to use when compressing data.
     *
     * @var int
     * @cvalue REDIS_OPT_COMPRESSION_LEVEL
     *
     */
    public const OPT_COMPRESSION_LEVEL = UNKNOWN;

    /**
     * Tells PhpRedis to return a NULL multi-bulk (`*-1\r\n`) response
     * as `null` as opposed to an empty array.
     *
     * @var int
     * @cvalue REDIS_OPT_NULL_MBULK_AS_NULL
     *
     */
    public const OPT_NULL_MULTIBULK_AS_NULL = UNKNOWN;

    /**
     * When enabled, this option tells PhpRedis to ignore purely numeric values
     * when packing and unpacking data. This does not include numeric strings.
     * If you want numeric strings to be ignored, typecast them to an int or
     * float.
     *
     * The primary purpose of this option is to make it more ergonomic when
     * setting keys that will later be incremented or decremented.
     *
     * Note: This option incurs a small performance penalty when reading data
     * because we have to see if the data is a string representation of an int
     * or float.
     *
     * @var int
     * @cvalue REDIS_OPT_PACK_IGNORE_NUMBERS
     *
     * @example
     * $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);
     * $redis->setOption(Redis::OPT_PACK_IGNORE_NUMBERS, true);
     * $redis->set('answer', 32);
     *
     * var_dump($redis->incrBy('answer', 10));  // int(42)
     * var_dump($redis->get('answer'));         // int(42)
     */
    public const OPT_PACK_IGNORE_NUMBERS = UNKNOWN;

    /**
     * Sets the serializer to none (no serialization).
     *
     * @var int
     * @cvalue REDIS_SERIALIZER_NONE
     *
     */
    public const SERIALIZER_NONE = UNKNOWN;

    /**
     * Sets the serializer to PHP's built-in `serialize()`/`unserialize()`
     *
     * @var int
     * @cvalue REDIS_SERIALIZER_PHP
     *
     */
    public const SERIALIZER_PHP = UNKNOWN;

#ifdef HAVE_REDIS_IGBINARY
    /**
     * Sets the serializer to igbinary. Note that phpredis must be compiled
     * with ighbinary support to use this serializer.
     *
     * @var int
     * @cvalue REDIS_SERIALIZER_IGBINARY
     *
     */
    public const SERIALIZER_IGBINARY = UNKNOWN;
#endif

#ifdef HAVE_REDIS_MSGPACK
    /**
     * Sets the serializer to msgpack. Note that phpredis must be compiled
     * with msgpack support to use this serializer.
     *
     * @var int
     * @cvalue REDIS_SERIALIZER_MSGPACK
     *
     */
    public const SERIALIZER_MSGPACK = UNKNOWN;
#endif

    /**
     * Sets the serializer to JSON.
     *
     * @var int
     * @cvalue REDIS_SERIALIZER_JSON
     *
     */
    public const SERIALIZER_JSON = UNKNOWN;

    /**
     * Disables compression.
     *
     * @var int
     * @cvalue REDIS_COMPRESSION_NONE
     *
     */
    public const COMPRESSION_NONE = UNKNOWN;

#ifdef HAVE_REDIS_LZF
    /**
     * Sets the compression algorithm to LZF. PhpRedis must be compiled with
     * lzf support but this serializer is bundled with the extension.
     *
     * @var int
     * @cvalue REDIS_COMPRESSION_LZF
     *
     */
    public const COMPRESSION_LZF = UNKNOWN;
#endif

#ifdef HAVE_REDIS_ZSTD
    /**
     * Sets the compression algorithm to ZSTD. PhpRedis must be compiled with
     * zstd support to use this serializer. This is often the best balance
     * between speed and compression ratio.
     *
     * @var int
     * @cvalue REDIS_COMPRESSION_ZSTD
     *
     */
    public const COMPRESSION_ZSTD = UNKNOWN;

#ifdef ZSTD_CLEVEL_DEFAULT
    /**
     * This constant represents the "default" compression level for ZSTD. If
     * PhpRedis is compiled against a new enough ZSTD the value comes from the
     * library, otherwise we just set it to 3.
     *
     * @var int
     * @cvalue ZSTD_CLEVEL_DEFAULT
     *
     */
    public const COMPRESSION_ZSTD_DEFAULT = UNKNOWN;
#else
    /**
     * This constant represents the "default" compression level for ZSTD. If
     * PhpRedis is compiled against a new enough ZSTD the value comes from the
     * library, otherwise we just set it to 3.
     *
     * @var int
     *
     */
    public const COMPRESSION_ZSTD_DEFAULT = 3;
#endif

#if ZSTD_VERSION_NUMBER >= 10400
    /**
     * The minimum compression level ZSTD supports, which comes from the
     * underlying ZSTD library if new enough. Otherwise we just set it to 1.
     *
     * @var int
     * @cvalue ZSTD_minCLevel()
     *
     */
    public const COMPRESSION_ZSTD_MIN = UNKNOWN;
#else
    /**
     * The minimum compression level ZSTD supports, which comes from the
     * underlying ZSTD library if new enough. Otherwise we just set it to 1.
     *
     * @var int
     *
     */
    public const COMPRESSION_ZSTD_MIN = 1;
#endif

    /**
     * The maximum compression level ZSTD supports, which comes from the
     * underlying ZSTD library.
     *
     * @var int
     * @cvalue ZSTD_maxCLevel()
     */
    public const COMPRESSION_ZSTD_MAX = UNKNOWN;
#endif

#ifdef HAVE_REDIS_LZ4
    /**
     * Set the compression algorithm to LZ4. PhpRedis must be compiled with
     * lz4 support to use this serializer. This algorithm is generally
     * the fastest but has a lower compression ratio than ZSTD.
     *
     * @var int
     * @cvalue REDIS_COMPRESSION_LZ4
     *
     */
    public const COMPRESSION_LZ4 = UNKNOWN;
#endif

    /**
     * Used with `\Redis::setOption()` to specify scan options.
     *
     * @var int
     * @cvalue REDIS_OPT_SCAN
     *
     */
    public const OPT_SCAN = UNKNOWN;

    /**
     * When enabled, this option causes PhpRedis to automatically retry `SCAN`
     * commands when Redis returns a non-zero cursor but no keys. This can
     * happen due to the nature of Redis' scanning algorithm.
     *
     * @var int
     * @cvalue REDIS_SCAN_RETRY
     *
     */
    public const SCAN_RETRY = UNKNOWN;

    /**
     * Then enabled, this option tells PhpRedis to not retry `SCAN` commands
     * when Redis returns a non-zero cursor but no keys. This means that your
     * code must handle this case itself.
     *
     * @var int
     * @cvalue REDIS_SCAN_NORETRY
     *
     */
    public const SCAN_NORETRY = UNKNOWN;

    /**
     * Tells PhpRedis to prefix keys returned from `SCAN` commands with the
     * currently set key prefix.
     *
     * @var int
     * @cvalue REDIS_SCAN_PREFIX
     *
     */
    public const SCAN_PREFIX = UNKNOWN;

    /**
     * Tells PhpRedis to NOT prefix keys returned from `SCAN` commands with
     * the currently set key prefix.
     *
     * @var int
     * @cvalue REDIS_SCAN_NOPREFIX
     *
     */
    public const SCAN_NOPREFIX = UNKNOWN;

    /**
     * This is just the string "before" which is used with various  list
     * commands to indicate an insertion point.
     *
     * @var string
     *
     */
    public const BEFORE = "before";

    /**
     * This is just the string "after" which is used with various list commands
     * to indicate an insertion point.
     *
     * @var string
     *
     */
    public const AFTER = "after";

    /**
     * This is just the string "left" which is used with various list commands
     * such as `LMOVE`.
     *
     * @var string
     *
     */
    public const LEFT = "left";

    /**
     * This is just the string "right" which is used with various list commands
     * such as `LMOVE`.
     *
     * @var string
     *
     */
    public const RIGHT = "right";

    /**
     * How many times should `PhpRedis` attempt to reconnect when we are
     * disconnected.
     *
     * @var int
     * @cvalue REDIS_OPT_MAX_RETRIES
     *
     */
    public const OPT_MAX_RETRIES = UNKNOWN;

    /**
     * Used to specify the backoff algorithm to use when reconnecting.
     *
     * @var int
     * @cvalue REDIS_OPT_BACKOFF_ALGORITHM
     *
     */
    public const OPT_BACKOFF_ALGORITHM = UNKNOWN;

    /**
     * Default backoff - random delay before the first retry, then constant `base` ms.
     *
     * @var int
     * @cvalue REDIS_BACKOFF_ALGORITHM_DEFAULT
     *
     */
    public const BACKOFF_ALGORITHM_DEFAULT = UNKNOWN;

    /**
     * Constant backoff - always sleep for exactly `base` ms (capped by `cap`).
     *
     * @var int
     * @cvalue REDIS_BACKOFF_ALGORITHM_CONSTANT
     *
     */
    public const BACKOFF_ALGORITHM_CONSTANT = UNKNOWN;

    /**
     * Uniform backoff - randomly sleep between 0 and `base` ms for each retry.
     *
     * @var int
     * @cvalue REDIS_BACKOFF_ALGORITHM_UNIFORM
     *
     */
    public const BACKOFF_ALGORITHM_UNIFORM = UNKNOWN;

    /**
     * Exponential backoff - doubles the delay every retry (up to 2^10) before `cap`.
     *
     * @var int
     * @cvalue REDIS_BACKOFF_ALGORITHM_EXPONENTIAL
     *
     */
    public const BACKOFF_ALGORITHM_EXPONENTIAL = UNKNOWN;

    /**
     * Full jitter - exponential delay but pick a random value between 0 and that delay.
     *
     * @var int
     * @cvalue REDIS_BACKOFF_ALGORITHM_FULL_JITTER
     *
     */
    public const BACKOFF_ALGORITHM_FULL_JITTER = UNKNOWN;

    /**
     * Equal jitter - half the exponential delay plus a random amount up to the other half.
     *
     * @var int
     * @cvalue REDIS_BACKOFF_ALGORITHM_EQUAL_JITTER
     *
     */
    public const BACKOFF_ALGORITHM_EQUAL_JITTER = UNKNOWN;

    /**
     * Decorrelated jitter - pick a random delay between `base` and 3x the previous delay.
     *
     * @var int
     * @cvalue REDIS_BACKOFF_ALGORITHM_DECORRELATED_JITTER
     *
     */
    public const BACKOFF_ALGORITHM_DECORRELATED_JITTER = UNKNOWN;

    /**
     * Backoff base - minimum delay in milliseconds that algorithms start from.
     *
     * @var int
     * @cvalue REDIS_OPT_BACKOFF_BASE
     *
     */
    public const OPT_BACKOFF_BASE = UNKNOWN;

    /**
     * Backoff cap - maximum delay in milliseconds that any algorithm can reach.
     *
     * @var int
     * @cvalue REDIS_OPT_BACKOFF_CAP
     *
     */
    public const OPT_BACKOFF_CAP = UNKNOWN;

    /**
     * Create a new Redis instance.  If passed sufficient information in the
     * options array it is also possible to connect to an instance at the same
     * time.
     *
     * **NOTE**:  Below is an example options array with various setting
     *
     *```php
     *$options = [
     *    'host'           => 'localhost',
     *    'port'           => 6379,
     *    'readTimeout'    => 2.5,
     *    'connectTimeout' => 2.5,
     *    'persistent'     => true,
     *
     *    // Valid formats: NULL, ['user', 'pass'], 'pass', or ['pass']
     *    'auth' => ['phpredis', 'phpredis'],
     *
     *    // See PHP stream options for valid SSL configuration settings.
     *    'ssl' => ['verify_peer' => false],
     *
     *    // How quickly to retry a connection after we time out or it  closes.
     *    // Note that this setting is overridden by 'backoff' strategies.
     *    'retryInterval'  => 100,
     *
     *     // Which backoff algorithm to use.  'decorrelated jitter' is
     *     // likely the best one for most solution, but there are many
     *     // to choose from:
     *     //     REDIS_BACKOFF_ALGORITHM_DEFAULT
     *     //     REDIS_BACKOFF_ALGORITHM_CONSTANT
     *     //     REDIS_BACKOFF_ALGORITHM_UNIFORM
     *     //     REDIS_BACKOFF_ALGORITHM_EXPONENTIAL
     *     //     REDIS_BACKOFF_ALGORITHM_FULL_JITTER
     *     //     REDIS_BACKOFF_ALGORITHM_EQUAL_JITTER
     *     //     REDIS_BACKOFF_ALGORITHM_DECORRELATED_JITTER
     *     // 'base', and 'cap' are in milliseconds and represent the first
     *     // delay redis will use when reconnecting, and the maximum delay
     *     // we will reach while retrying.
     *    'backoff' => [
     *        'algorithm' => Redis::BACKOFF_ALGORITHM_DECORRELATED_JITTER,
     *        'base'      => 500,
     *        'cap'       => 750,
     *    ]
     *];
     *```
     *
     * Note: If you do wish to connect via the constructor, only 'host' is
     *       strictly required, which will cause PhpRedis to connect to that
     *       host on Redis' default port (6379).
     *
     *
     * @see Redis::connect()
     * @see https://aws.amazon.com/blogs/architecture/exponential-backoff-and-jitter/
     * @param array|null $options
     *
     * @return Redis
     *
     * @example
     * $redis = new Redis(['host' => '127.0.0.1', 'port' => 6380]);
     *
     */
    public function __construct(?array $options = null);

    /**
     * Destructor to clean up the Redis object.
     *
     * This method will disconnect from Redis. If the connection is persistento
     * it will be stashed for future reuse.
     *
     */
    public function __destruct();

    /**
     * Compress a value with the currently configured compressor (Redis::OPT_COMPRESSION)
     * exactly the same way PhpRedis does before sending data to Redis.
     *
     * @see Redis::setOption()
     *
     * @param  string $value The value to be compressed
     * @return string        The compressed result (or the original value if compression is disabled)
     *
     * @example
     * $redis->_compress('payload');
     *
     */
    public function _compress(string $value): string;

    /**
     * Uncompress the provided argument using the compressor configured via
     * Redis::setOption() (Redis::OPT_COMPRESSION).
     *
     * @see Redis::setOption()
     *
     * @param  string $value  The compressed value to uncompress.
     * @return string         The uncompressed result.
     *
     * @example
     * $redis->_uncompress($redis->_compress('payload'));
     *
     */
    public function _uncompress(string $value): string;

    /**
     * Prefix the passed argument with the currently set key prefix as set
     * with Redis::setOption().
     *
     * @param string  $key The key/string to prefix
     * @return string      The prefixed string
     *
     * @example
     * $redis->_prefix('user:42');
     *
     */
    public function _prefix(string $key): string;

    /**
     * Serialize the provided value with the currently set serializer as set
     * with Redis::setOption().
     *
     * @see Redis::setOption()
     *
     * @param mixed $value The value to serialize
     * @return string      The serialized result
     *
     * @example
     * $redis->_serialize(['answer' => 42]);
     *
     */
    public function _serialize(mixed $value): string;

    /**
     * Unserialize the passed argument with the currently set serializer as set
     * with Redis::setOption().
     *
     * @see Redis::setOption()
     *
     * @param string $value The value to unserialize
     * @return mixed        The unserialized result
     *
     * @example
     * $redis->_unserialize($redis->_serialize(['answer' => 42]));
     *
     */
    public function _unserialize(string $value): mixed;

    /**
     * Pack the provided value by first serializing it (if Redis::OPT_SERIALIZER is set)
     * and then compressing the serialized payload (if Redis::OPT_COMPRESSION is set),
     * mirroring exactly what PhpRedis transmits to Redis.
     *
     * @param  mixed $value  The value to pack
     * @return string        The packed result having been serialized and
     *                       compressed.
     *
     * @example
     * $redis->_pack(['count' => 5]);
     *
     */
    public function _pack(mixed $value): string;

    /**
     * Compute the XXH3 digest of a PHP value after it has been `_pack`ed, producing
     * the same digest Redis' DIGEST command would return for the stored value.
     *
     * @param mixed $value The value to compute the digest for.
     * @return string The computed digest.
     *
     * @throws RedisException If XXH3 is not supported.
     *
     * @note This function requires PHP >= 8.1 which is the version PHP
     *       added support for XXH3 hashing and made the hash extension
     *       mandatory.
     *
     * @example
     * $redis->_digest(['token' => 'abc']);
     *
     */
    public function _digest(mixed $value): string;

    /**
     * Unpack the provided value by first uncompressing it (if Redis::OPT_COMPRESSION
     * is set) and then unserializing it (if Redis::OPT_SERIALIZER is set) to recover
     * the original PHP value.
     *
     * @param  string $value The value which has been serialized and compressed.
     * @return mixed         The uncompressed and deserialized value.
     *
     * @example
     * $redis->_unpack($redis->_pack(['count' => 5]));
     *
     */
    public function _unpack(string $value): mixed;

    /**
     * Execute Redis ACL subcommands.
     *
     * @see https://redis.io/docs/latest/commands/acl/
     *
     * @example
     * $redis->acl('list');
     */
    public function acl(string $subcmd, string ...$args): mixed;

    /**
     * Append data to a Redis STRING key.
     *
     * @param string $key   The key in question
     * @param mixed $value  The data to append to the key.
     *
     * @return Redis|int|false The new string length of the key or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/append/
     *
     * @example
     * $redis->set('foo', 'hello);
     * $redis->append('foo', 'world');
     */
    public function append(string $key, mixed $value): Redis|int|false;

    /**
     * Authenticate a Redis connection after its been established.
     *
     *     $redis->auth('password');
     *     $redis->auth(['password']);
     *     $redis->auth(['username', 'password']);
     *
     * @see https://redis.io/docs/latest/commands/auth/
     *
     * @param mixed $credentials A string password, or an array with one or two string elements.
     * @return Redis|bool Whether the AUTH was successful.
     *
     * @example
     * $redis->auth('secret');
     *
     */
    public function auth(#[\SensitiveParameter] mixed $credentials): Redis|bool;

    /**
     * Execute a save of the Redis database in the background.
     *
     * @see https://redis.io/docs/latest/commands/bgsave/
     *
     * @return Redis|bool Whether the command was successful.
     *
     * @example
     * $redis->bgSave();
     *
     */
    public function bgSave(): Redis|bool;

    /**
     * Asynchronously rewrite Redis' append-only file
     *
     * @see https://redis.io/docs/latest/commands/bgrewriteaof/
     *
     * @return Redis|bool Whether the command was successful.
     *
     * @example
     * $redis->bgrewriteaof();
     *
     */
    public function bgrewriteaof(): Redis|bool;

    /**
     * @see https://redis.io/docs/latest/commands/waitaof/
     *
     * @return Redis|array|false
     *
     * @example
     * $redis->waitaof(1, 1, 5000);
     *
     */
    public function waitaof(int $numlocal, int $numreplicas, int $timeout): Redis|array|false;

    /**
     * Count the number of set bits in a Redis string.
     *
     * @see https://redis.io/docs/latest/commands/bitcount/
     *
     * @param string $key     The key in question (must be a string key)
     * @param int    $start   The index where Redis should start counting.  If omitted it
     *                        defaults to zero, which means the start of the string.
     * @param int    $end     The index where Redis should stop counting.  If omitted it
     *                        defaults to -1, meaning the very end of the string.
     *
     * @param bool   $bybit   Whether or not Redis should treat $start and $end as bit
     *                        positions, rather than bytes.
     *
     * @return Redis|int|false The number of bits set in the requested range.
     *
     * @example
     * $redis->bitcount('bitmap', 0, -1);
     *
     */
    public function bitcount(string $key, int $start = 0, int $end = -1, bool $bybit = false): Redis|int|false;

    public function bitop(string $operation, string $deskey, string $srckey, string ...$other_keys): Redis|int|false;

    /**
     * Return the position of the first bit set to 0 or 1 in a string.
     *
     * @see https://redis.io/docs/latest/commands/bitpos/
     *
     * @param string $key   The key to check (must be a string)
     * @param bool   $bit   Whether to look for an unset (0) or set (1) bit.
     * @param int    $start Where in the string to start looking.
     * @param int    $end   Where in the string to stop looking.
     * @param bool   $bybit If true, Redis will treat $start and $end as BIT values and not bytes, so if start
     *                      was 0 and end was 2, Redis would only search the first two bits.
     *
     * @return Redis|int|false The position of the first set or unset bit.
     *
     * @example
     * $redis->bitpos('bitmap', true, 0, -1);
     *
     **/
    public function bitpos(string $key, bool $bit, int $start = 0, int $end = -1, bool $bybit = false): Redis|int|false;

    /**
     * Pop an element off the beginning of a Redis list or lists, potentially blocking up to a specified
     * timeout.  This method may be called in two distinct ways, of which examples are provided below.
     *
     * @see https://redis.io/docs/latest/commands/blpop/
     *
     * @param string|array     $key_or_keys    This can either be a string key or an array of one or more
     *                                         keys.
     * @param string|float|int $timeout_or_key If the previous argument was a string key, this can either
     *                                         be an additional key, or the timeout you wish to send to
     *                                         the command.
     *
     * @return Redis|array|null|false Can return various things depending on command and data in Redis.
     *
     * @example
     * $redis->blPop('list1', 'list2', 'list3', 1.5);
     * $redis->blPop(['list1', 'list2', 'list3'], 1.5);
     */
    public function blPop(string|array $key_or_keys, string|float|int $timeout_or_key, mixed ...$extra_args): Redis|array|null|false;

    /**
     * Pop an element off of the end of a Redis list or lists, potentially blocking up to a specified timeout.
     * The calling convention is identical to Redis::blPop() so see that documentation for more details.
     *
     * @see https://redis.io/docs/latest/commands/brpop/
     * @see Redis::blPop()
     *
     * @example
     * $redis->brPop(['queue:critical', 'queue:default'], 5);
     *
     */
    public function brPop(string|array $key_or_keys, string|float|int $timeout_or_key, mixed ...$extra_args): Redis|array|null|false;

    /**
     * Pop an element from the end of a Redis list, pushing it to the beginning of another Redis list,
     * optionally blocking up to a specified timeout.
     *
     * @see https://redis.io/docs/latest/commands/brpoplpush/
     *
     * @param string    $src     The source list
     * @param string    $dst     The destination list
     * @param int|float $timeout The number of seconds to wait.  Note that you must be connected
     *                           to Redis >= 6.0.0 to send a floating point timeout.
     *
     * @example
     * $redis->brpoplpush('queue:pending', 'queue:processing', 5);
     *
     */
    public function brpoplpush(string $src, string $dst, int|float $timeout): Redis|string|false;

    /**
     * POP the maximum scoring element off of one or more sorted sets, blocking up to a specified
     * timeout if no elements are available.
     *
     * Following are examples of the two main ways to call this method.
     *
     * **NOTE**:  We recommend calling this function with an array and a timeout as the other strategy
     *            may be deprecated in future versions of PhpRedis
     *
     * @see https://redis.io/docs/latest/commands/bzpopmax/
     *
     * @param string|array $key            Either a string key or an array of one or more keys.
     * @param string|int   $timeout_or_key If the previous argument was an array, this argument
     *                                     must be a timeout value.  Otherwise it could also be
     *                                     another key.
     * @param mixed     ...$extra_args     Can consist of additional keys, until the last argument
     *                                     which needs to be a timeout.
     *
     * @return Redis|array|false The popped elements.
     *
     * @example
     * $redis->bzPopMax('key1', 'key2', 'key3', 1.5);
     * $redis->bzPopMax(['key1', 'key2', 'key3'], 1.5);
     */
    public function bzPopMax(string|array $key, string|int $timeout_or_key, mixed ...$extra_args): Redis|array|false;

    /**
     * POP the minimum scoring element off of one or more sorted sets, blocking up to a specified timeout
     * if no elements are available
     *
     * This command is identical in semantics to bzPopMax so please see that method for more information.
     *
     * @see https://redis.io/docs/latest/commands/bzpopmin/
     * @see Redis::bzPopMax()
     *
     * @example
     * $redis->bzPopMin(['scores:high', 'scores:low'], 1.5);
     *
     */
    public function bzPopMin(string|array $key, string|int $timeout_or_key, mixed ...$extra_args): Redis|array|false;

    /**
     * POP one or more elements from one or more sorted sets, blocking up to a specified amount of time
     * when no elements are available.
     *
     * @param float  $timeout How long to block if there are no element available
     * @param array  $keys    The sorted sets to pop from
     * @param string $from    The string 'MIN' or 'MAX' (case insensitive) telling Redis whether you wish to
     *                        pop the lowest or highest scoring members from the set(s).
     * @param int    $count   Pop up to how many elements.
     *
     * @return Redis|array|null|false This function will return an array of popped elements, or false
     *                                depending on whether any elements could be popped within the
     *                                specified timeout.
     *
     * NOTE:  If Redis::OPT_NULL_MULTIBULK_AS_NULL is set to true via Redis::setOption(), this method will
     *        instead return NULL when Redis doesn't pop any elements.
     *
     * @see https://redis.io/docs/latest/commands/bzmpop/
     *
     * @example
     * $redis->bzmpop(1.5, ['scores:high', 'scores:low'], 'MIN', 2);
     *
     */
    public function bzmpop(float $timeout, array $keys, string $from, int $count = 1): Redis|array|null|false;

    /**
     * POP one or more of the highest or lowest scoring elements from one or more sorted sets.
     *
     * @see https://redis.io/docs/latest/commands/zmpop/
     *
     * @param array  $keys  One or more sorted sets
     * @param string $from  The string 'MIN' or 'MAX' (case insensitive) telling Redis whether you want to
     *                      pop the lowest or highest scoring elements.
     * @param int    $count Pop up to how many elements at once.
     *
     * @return Redis|array|null|false An array of popped elements or false if none could be popped.
     *
     * @example
     * $redis->zmpop(['scores:high', 'scores:low'], 'MAX', 2);
     *
     */
    public function zmpop(array $keys, string $from, int $count = 1): Redis|array|null|false;

    /**
     * Pop one or more elements from one or more Redis LISTs, blocking up to a specified timeout when
     * no elements are available.
     *
     * @see https://redis.io/docs/latest/commands/blmpop/
     *
     * @param float  $timeout The number of seconds Redis will block when no elements are available.
     * @param array  $keys    One or more Redis LISTs to pop from.
     * @param string $from    The string 'LEFT' or 'RIGHT' (case insensitive), telling Redis whether
     *                        to pop elements from the beginning or end of the LISTs.
     * @param int    $count   Pop up to how many elements at once.
     *
     * @return Redis|array|null|false One or more elements popped from the list(s) or false if all LISTs
     *                                were empty.
     *
     * @example
     * $redis->blmpop(1.5, ['queue:critical', 'queue:default'], 'LEFT', 2);
     *
     */
    public function blmpop(float $timeout, array $keys, string $from, int $count = 1): Redis|array|null|false;

    /**
     * Pop one or more elements off of one or more Redis LISTs.
     *
     * @see https://redis.io/docs/latest/commands/lmpop/
     *
     * @param array  $keys  An array with one or more Redis LIST key names.
     * @param string $from  The string 'LEFT' or 'RIGHT' (case insensitive), telling Redis whether to pop\
     *                      elements from the beginning or end of the LISTs.
     * @param int    $count The maximum number of elements to pop at once.
     *
     * @return Redis|array|null|false One or more elements popped from the LIST(s) or false if all the LISTs
     *                                were empty.
     *
     * @example
     * $redis->lmpop(['queue:critical', 'queue:default'], 'RIGHT', 2);
     *
     */
    public function lmpop(array $keys, string $from, int $count = 1): Redis|array|null|false;

    /**
     * Reset any last error on the connection to NULL
     *
     * @see Redis::getLastError()
     * @return bool This should always return true or throw an exception if we're not connected.
     *
     * @example
     * $redis = new Redis(['host' => 'localhost']);
     * $redis->set('string', 'this_is_a_string');
     * $redis->smembers('string');
     * var_dump($redis->getLastError());
     * $redis->clearLastError();
     * var_dump($redis->getLastError());
     */
    public function clearLastError(): bool;

    /**
     * Execute Redis CLIENT subcommands.
     *
     * @param string $opt     The CLIENT subcommand to execute.
     * @param mixed  ...$args Additional arguments depending on the subcommand.
     *
     * @see https://redis.io/docs/latest/commands/client/
     *
     * @example
     * $redis->client('list');
     */
    public function client(string $opt, mixed ...$args): mixed;

    /**
     * Closes the connection to Redis
     *
     * This function will close the connection whether it is persistent or not.
     *
     * @return bool Whether the connection was successfully closed.
     *
     * @example
     * $redis = new Redis;
     * $redis->pconnect('localhost', 6379);
     * $id1 = $redis->client('id');
     * $redis->close();
     *
     * $redis = new Redis;
     * $redis->pconnect('localhost', 6379);
     * $id2 = $redis->client('id');
     *
     * // Will print "id is different"
     * printf("ID is %s\n", $id1 == $id2 ? 'the same' :  'different');
     */
    public function close(): bool;

    /**
     * Execute Redis COMMAND subcommands.
     *
     * @see https://redis.io/docs/latest/commands/command/
     *
     * @example
     * $redis->command('command');
     */
    public function command(?string $opt = null, mixed ...$args): mixed;

    /**
     *  Execute the Redis CONFIG command in a variety of ways.
     *
     *  What the command does in particular depends on the `$operation` qualifier.
     *  Operations that PhpRedis supports are: RESETSTAT, REWRITE, GET, and SET.
     *
     * @param string            $operation       The CONFIG operation to execute (e.g. GET, SET, REWRITE).
     * @param array|string|null $key_or_settings One or more keys or values.
     * @param string|null       $value           The value if this is a `CONFIG SET` operation.
     * @see https://redis.io/docs/latest/commands/config/
     *
     * @example
     * $redis->config('GET', 'timeout');
     * $redis->config('GET', ['timeout', 'databases']);
     * $redis->config('SET', 'timeout', 30);
     * $redis->config('SET', ['timeout' => 30, 'loglevel' => 'warning']);
     */
    public function config(string $operation, array|string|null $key_or_settings = null, ?string $value = null): mixed;

    /**
     * Connect to a Redis server
     *
     * @param string      $host           The Redis server hostname or IP
     *                                    address.
     * @param int         $port           The Redis server port.  Defaults to
     *                                    6379.
     * @param float       $timeout        The connection timeout in seconds.
     *                                    Defaults to 0 (no timeout).
     * @param string|null $persistent_id  If set, a persistent connection will
     *                                    be made with this ID.
     * @param int         $retry_interval The number of milliseconds to wait
     *                                    between connection attempts.
     * @param float       $read_timeout   The read timeout in seconds.
     *                                    Defaults to 0 (no timeout).
     * @param array|null  $context        An optional stream context to use
     *                                    when connecting.
     *                                    See `\Redis::__construct()` for more
     *                                    details.
     * @return bool Whether the connection was successful.
     *
     * @throws RedisException On connection errors.
     *
     * @example
     * $redis = new \Redis;
     * try {
     *     $redis->connect('localhost', 6379, 2.5, null, 100, 2.5);
     *     $foo = $redis->get('foo');
     *     printf("foo: %s\n", $foo);
     * } catch (Exception $ex) {
     *     fprintf(STDERR, "Error: {$ex->getMessage()}\n");
     * }
     */
    public function connect(string $host, int $port = 6379, float $timeout = 0, ?string $persistent_id = null,
                            int $retry_interval = 0, float $read_timeout = 0, ?array $context = null): bool;

    /**
     * Make a copy of a key.
     *
     * $redis = new Redis(['host' => 'localhost']);
     *
     * @param string     $src     The key to copy
     * @param string     $dst     The name of the new key created from the source key.
     * @param array|null $options An array with modifiers on how COPY should operate.
     *                            ```php
     *                            $options = [
     *                                'REPLACE' => true|false # Whether to replace an existing key.
     *                                'DB' => int             # Copy key to specific db.
     *                            ];
     *                            ```
     *
     * @return Redis|bool True if the copy was completed and false if not.
     *
     * @see https://redis.io/docs/latest/commands/copy/
     *
     * @example
     * $redis->pipeline()
     *       ->select(1)
     *       ->del('newkey')
     *       ->select(0)
     *       ->del('newkey')
     *       ->mset(['source1' => 'value1', 'exists' => 'old_value'])
     *       ->exec();
     *
     * var_dump($redis->copy('source1', 'newkey'));
     * var_dump($redis->copy('source1', 'newkey', ['db' => 1]));
     * var_dump($redis->copy('source1', 'exists'));
     * var_dump($redis->copy('source1', 'exists', ['REPLACE' => true]));
     */
    public function copy(string $src, string $dst, ?array $options = null): Redis|bool;

    /**
     * Return the number of keys in the currently selected Redis database.
     *
     * @see https://redis.io/docs/latest/commands/dbsize/
     *
     * @return Redis|int|false The number of keys or false on failure.
     *
     * @example
     * $redis = new Redis(['host' => 'localhost']);
     * $redis->flushdb();
     * $redis->set('foo', 'bar');
     * var_dump($redis->dbsize());
     * $redis->mset(['a' => 'a', 'b' => 'b', 'c' => 'c', 'd' => 'd']);
     * var_dump($redis->dbsize());
     */
    public function dbSize(): Redis|int|false;

    /**
     * Execute the Redis `DEBUG` command. Note that this is disabled by default
     * and can be very dangerous, even allowing you to crash the server. Use
     * with caution
     *
     * @note The command has greatly increased in complexity since it was first
     * added to PhpRedis, so you may need to use it via `Redis::rawCommand()`
     * for certain subcommands.
     *
     * @param string $key The DEBUG subcommand to execute.
     * @return Redis|string The result of the DEBUG command.
     */
    public function debug(string $key): Redis|string;

    /**
     * Decrement a Redis integer by 1 or a provided value.
     *
     * @param string $key The key to decrement
     * @param int    $by  How much to decrement the key.  Note that if this value is
     *                    not sent or is set to `1`, PhpRedis will actually invoke
     *                    the 'DECR' command.  If it is any value other than `1`
     *                    PhpRedis will actually send the `DECRBY` command.
     *
     * @return Redis|int|false The new value of the key or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/decr/
     * @see https://redis.io/docs/latest/commands/decrby/
     *
     * @example $redis->decr('counter');
     * @example $redis->decr('counter', 2);
     */
    public function decr(string $key, int $by = 1): Redis|int|false;

    /**
     * Decrement a redis integer by a value
     *
     * @param string $key   The integer key to decrement.
     * @param int    $value How much to decrement the key.
     *
     * @return Redis|int|false The new value of the key or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/decrby/
     *
     * @example $redis->decrby('counter', 1);
     * @example $redis->decrby('counter', 2);
     */
    public function decrBy(string $key, int $value): Redis|int|false;

    /**
     * Delete one or more keys from Redis.
     *
     * This method can be called in two distinct ways.  The first is to pass a single array
     * of keys to delete, and the second is to pass N arguments, all names of keys.  See
     * below for an example of both strategies.
     *
     * @param array|string $key           Either an array with one or more key names or a string with
     *                                    the name of a key.
     * @param string       ...$other_keys One or more additional keys passed in a variadic fashion.
     *
     * @return Redis|int|false The number of keys that were deleted
     *
     * @see https://redis.io/docs/latest/commands/del/
     *
     * @example $redis->del('key:0', 'key:1');
     * @example $redis->del(['key:2', 'key:3', 'key:4']);
     */
    public function del(array|string $key, string ...$other_keys): Redis|int|false;

    /**
     * Delete a key conditionally based on its value or hash digest
     *
     * @param string $key         The key to delete
     * @param array|null $options An array with options to modify how DELX works.
     *
     * @return Redis|int|false Returns 1 if the key was deleted, 0 if it was not.
     *
     * @see https://redis.io/docs/latest/commands/delex/
     *
     * @example
     * $redis->delex('session:42');
     *
     */
    public function delex(string $key, ?array $options = null): Redis|int|false;

    /**
     * Delete a key if it's equal to the specified value. This command is
     * specific to Valkey >= 9.0
     *
     * @param string $key   The key to delete
     * @param mixed  $value The value to compare against the key's value.
     * @return Redis|int|false Returns 1 if the key was deleted, 0 if it was not.
     *
     * @see https://valkey.io/commands/delifeq/
     *
     * @example
     * $redis->delifeq('session:42', 'token');
     *
     */
    public function delifeq(string $key, mixed $value): Redis|int|false;

    /**
     * @deprecated
     * @alias Redis::del
     *
     * @see https://redis.io/docs/latest/commands/del/
     *
     * @example
     * $redis->delete('legacy:key');
     *
     */
    public function delete(array|string $key, string ...$other_keys): Redis|int|false;

    /**
     * Discard a transaction currently in progress.
     *
     * @return Redis|bool  True if we could discard the transaction.
     *
     * @see https://redis.io/docs/latest/commands/discard/
     *
     * @example
     * $redis->getMode();
     * $redis->set('foo', 'bar');
     * $redis->discard();
     * $redis->getMode();
     */
    public function discard(): Redis|bool;

    /**
     * Dump Redis' internal binary representation of a key.
     *
     * @param string $key The key to dump.
     *
     * @return Redis|string|false A binary string representing the key's value.
     *
     * @see https://redis.io/docs/latest/commands/dump/
     *
     * @example
     * $redis->zadd('zset', 0, 'zero', 1, 'one', 2, 'two');
     * $binary = $redis->dump('zset');
     * $redis->restore('new-zset', 0, $binary);
     */
    public function dump(string $key): Redis|string|false;

    /**
     * Have Redis repeat back an arbitrary string to the client.
     *
     * @param string $str The string to echo
     *
     * @return Redis|string|false The string sent to Redis or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/echo/
     *
     * @example $redis->echo('Hello, World');
     */
    public function echo(string $str): Redis|string|false;

    /**
     * Execute a LUA script on the redis server.
     *
     * @see https://redis.io/docs/latest/commands/eval/
     *
     * @param string $script   A string containing the LUA script
     * @param array  $args     An array of arguments to pass to this script
     * @param int    $num_keys How many of the arguments are keys.  This is needed
     *                         as redis distinguishes between key name arguments
     *                         and other data.
     *
     * @return mixed LUA scripts may return arbitrary data so this method can return
     *               strings, arrays, nested arrays, etc.
     *
     * @example
     * $redis->eval('return redis.call("set", KEYS[1], ARGV[1])', ['counter', 1], 1);
     *
     */
    public function eval(string $script, array $args = [], int $num_keys = 0): mixed;

    /**
     * This is simply the read-only variant of eval, meaning the underlying script
     * may not modify data in redis.
     *
     * @see Redis::eval_ro()
     * @see https://redis.io/docs/latest/commands/eval_ro/
     *
     * @example
     * $redis->eval_ro('return redis.call("get", KEYS[1])', ['counter'], 1);
     *
     */
    public function eval_ro(string $script_sha, array $args = [], int $num_keys = 0): mixed;

    /**
     * Execute a LUA script on the server but instead of sending the script, send
     * the SHA1 hash of the script.
     *
     * @param string $sha1     The SHA1 hash of the lua code.  Note that the script
     *                         must already exist on the server, either having been
     *                         loaded with `SCRIPT LOAD` or having been executed directly
     *                         with `EVAL` first.
     * @param array  $args     Arguments to send to the script.
     * @param int    $num_keys The number of arguments that are keys
     *
     * @return mixed Returns whatever the specific script does.
     *
     * @see https://redis.io/docs/latest/commands/evalsha/
     * @see Redis::eval();
     *
     * @example
     * $sha = $redis->script('load', 'return redis.call("incr", KEYS[1])');
     * $redis->evalsha($sha, ['counter'], 1);
     *
     */
    public function evalsha(string $sha1, array $args = [], int $num_keys = 0): mixed;

    /**
     * This is simply the read-only variant of evalsha, meaning the underlying script
     * may not modify data in redis.
     *
     * @see Redis::evalsha()
     * @see https://redis.io/docs/latest/commands/evalsha_ro/
     *
     * @example
     * $sha = $redis->script('load', 'return redis.call("get", KEYS[1])');
     * $redis->evalsha_ro($sha, ['counter'], 1);
     *
     */
    public function evalsha_ro(string $sha1, array $args = [], int $num_keys = 0): mixed;

    /**
     * Execute either a MULTI or PIPELINE block and return the array of replies.
     *
     * @return Redis|array|false The array of pipeline'd or multi replies or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/exec/
     * @see https://redis.io/docs/latest/commands/multi/
     * @see Redis::pipeline()
     * @see Redis::multi()
     *
     * @example
     * $res = $redis->multi()
     *              ->set('foo', 'bar')
     *              ->get('foo')
     *              ->del('list')
     *              ->rpush('list', 'one', 'two', 'three')
     *              ->exec();
     */
    public function exec(): Redis|array|false;

    /**
     * Test if one or more keys exist.
     *
     * @param mixed $key            Either an array of keys or a string key
     * @param mixed ...$other_keys  If the previous argument was a string, you may send any number of
     *                              additional keys to test.
     *
     * @return Redis|int|bool    The number of keys that do exist and false on failure
     *
     * @see https://redis.io/docs/latest/commands/exists/
     *
     * @example $redis->exists(['k1', 'k2', 'k3']);
     * @example $redis->exists('k4', 'k5', 'notakey');
     */
    public function exists(mixed $key, mixed ...$other_keys): Redis|int|bool;

    /**
     * Sets an expiration in seconds on the key in question.  If connected to
     * redis-server >= 7.0.0 you may send an additional "mode" argument which
     * modifies how the command will execute.
     *
     * @param string      $key  The key to set an expiration on.
     * @param int         $timeout  The number of seconds after which key will be automatically deleted.
     * @param string|null $mode  A two character modifier that changes how the
     *                      command works.
     *                      <code>
     *                      NX - Set expiry only if key has no expiry
     *                      XX - Set expiry only if key has an expiry
     *                      LT - Set expiry only when new expiry is < current expiry
     *                      GT - Set expiry only when new expiry is > current expiry
     *                      </code>
     *
     * @return Redis|bool True if an expiration was set and false otherwise.
     * @see https://redis.io/docs/latest/commands/expire/
     *
     * @example
     * $redis->expire('session:42', 60);
     *
     */
    public function expire(string $key, int $timeout, ?string $mode = null): Redis|bool;

    /*
     * Set a key's expiration to a specific Unix timestamp in seconds.
     *
     * If connected to Redis >= 7.0.0 you can pass an optional 'mode' argument.
     * @see Redis::expire() For a description of the mode argument.
     *
     * @param string $key The key to set an expiration on.
     *
     * @return Redis|bool True if an expiration was set, false if not.
     *
     */

    /**
     * Set a key to expire at an exact unix timestamp.
     *
     * @param string      $key The key to set an expiration on.
     * @param int         $timestamp The unix timestamp to expire at.
     * @param string|null $mode An option 'mode' that modifies how the command acts (see {@link Redis::expire}).
     * @return Redis|bool True if an expiration was set, false if not.
     *
     * @see https://redis.io/docs/latest/commands/expireat/
     * @see https://redis.io/docs/latest/commands/expire/
     * @see Redis::expire()
     *
     * @example
     * $redis->expireAt('session:42', time() + 300);
     *
     */
    public function expireAt(string $key, int $timestamp, ?string $mode = null): Redis|bool;

    public function failover(?array $to = null, bool $abort = false, int $timeout = 0): Redis|bool;

    /**
     * Get the expiration of a given key as a unix timestamp
     *
     * @param string $key      The key to check.
     *
     * @return Redis|int|false The timestamp when the key expires, or -1 if the key has no expiry
     *                         and -2 if the key doesn't exist.
     *
     * @see https://redis.io/docs/latest/commands/expiretime/
     *
     * @example
     * $redis->setEx('mykey', 60, 'myval');
     * $redis->expiretime('mykey');
     */
    public function expiretime(string $key): Redis|int|false;

    /**
     * Get the expiration timestamp of a given Redis key but in milliseconds.
     *
     * @see https://redis.io/docs/latest/commands/pexpiretime/
     * @see Redis::expiretime()
     *
     * @param string $key      The key to check
     *
     * @return Redis|int|false The expiration timestamp of this key (in milliseconds) or -1 if the
     *                         key has no expiration, and -2 if it does not exist.
     *
     * @example
     * $redis->pexpiretime('session:42');
     *
     */
    public function pexpiretime(string $key): Redis|int|false;

    /**
     * Invoke a function.
     *
     * @param string $fn    The name of the function
     * @param array  $keys  Optional list of keys
     * @param array  $args  Optional list of args
     *
     * @return mixed        Function may return arbitrary data so this method can return
     *                      strings, arrays, nested arrays, etc.
     *
     * @see https://redis.io/docs/latest/commands/fcall/
     *
     * @example
     * $redis->fcall('mylib.increment', ['counter'], [1]);
     *
     */
    public function fcall(string $fn, array $keys = [], array $args = []): mixed;

    /**
     * This is a read-only variant of the FCALL command that cannot execute commands that modify data.
     *
     * @param string $fn    The name of the function
     * @param array  $keys  Optional list of keys
     * @param array  $args  Optional list of args
     *
     * @return mixed        Function may return arbitrary data so this method can return
     *                      strings, arrays, nested arrays, etc.
     *
     * @see https://redis.io/docs/latest/commands/fcall_ro/
     *
     * @example
     * $redis->fcall_ro('mylib.peek', ['counter']);
     *
     */
    public function fcall_ro(string $fn, array $keys = [], array $args = []): mixed;

    /**
     * Deletes every key in all Redis databases
     *
     * @param bool $sync Whether to perform the task in a blocking or non-blocking way.
     *
     * @see https://redis.io/docs/latest/commands/flushall/
     *
     * @example
     * $redis->flushAll(true);
     *
     */
    public function flushAll(?bool $sync = null): Redis|bool;

    /**
     * Deletes all the keys of the currently selected database.
     *
     * @param bool $sync Whether to perform the task in a blocking or non-blocking way.
     *
     * @see https://redis.io/docs/latest/commands/flushdb/
     *
     * @example
     * $redis->flushDB(true);
     *
     */
    public function flushDB(?bool $sync = null): Redis|bool;

    /**
     * Functions is an API for managing code to be executed on the server.
     *
     * @param string $operation The subcommand you intend to execute.  Valid options are as follows
     *                          'LOAD'      - Create a new library with the given library name and code.
     *                          'DELETE'    - Delete the given library.
     *                          'LIST'      - Return general information on all the libraries
     *                          'STATS'     - Return information about the current function running
     *                          'KILL'      - Kill the current running function
     *                          'FLUSH'     - Delete all the libraries
     *                          'DUMP'      - Return a serialized payload representing the current libraries
     *                          'RESTORE'   - Restore the libraries represented by the given payload
     * @param mixed  ...$args   Additional arguments
     *
     * @return Redis|bool|string|array  Depends on subcommand.
     *
     * @see https://redis.io/docs/latest/commands/function/
     *
     * @example
     * $redis->function('LIST');
     *
     */
    public function function(string $operation, mixed ...$args): Redis|bool|string|array;

    /**
     * Add one or more members to a geospacial sorted set
     *
     * @param string $key                         The sorted set to add data to.
     * @param float  $lng                         The longitude of the first member
     * @param float  $lat                         The latitude of the first member.
     * @param mixed ...$other_triples_and_options You can continue to pass longitude, latitude, and member
     *                                            arguments to add as many members as you wish.  Optionally, the final argument may be
     *                                            a string with options for the command @see Redis documentation for the options.
     *
     * @return Redis|int|false The number of added elements is returned.  If the 'CH' option is specified,
     *                         the return value is the number of members *changed*.
     *
     * @example $redis->geoAdd('cities', -121.8374, 39.7284, 'Chico', -122.03218, 37.322, 'Cupertino');
     * @example $redis->geoadd('cities', -121.837478, 39.728494, 'Chico', ['XX', 'CH']);
     *
     * @see https://redis.io/docs/latest/commands/geoadd/
     */

    public function geoadd(string $key, float $lng, float $lat, string $member, mixed ...$other_triples_and_options): Redis|int|false;

    /**
     * Get the distance between two members of a geospacially encoded sorted set.
     *
     * @param string      $key  The Sorted set to query.
     * @param string      $src  The first member.
     * @param string      $dst  The second member.
     * @param string|null $unit Which unit to use when computing distance, defaulting to meters.
     *                          <code>
     *                          M  - meters
     *                          KM - kilometers
     *                          FT - feet
     *                          MI - miles
     *                          </code>
     *
     * @return Redis|float|false The calculated distance in whichever units were specified or false
     *                           if one or both members did not exist.
     *
     * @example $redis->geodist('cities', 'Chico', 'Cupertino', 'mi');
     *
     * @see https://redis.io/docs/latest/commands/geodist/
     */
    public function geodist(string $key, string $src, string $dst, ?string $unit = null): Redis|float|false;

    /**
     * Retrieve one or more GeoHash encoded strings for members of the set.
     *
     * @param string $key              The key to query
     * @param string $member           The first member to request
     * @param string ...$other_members One or more additional members to request.
     *
     * @return Redis|array|false    An array of GeoHash encoded values.
     *
     * @see https://redis.io/docs/latest/commands/geohash/
     * @see https://en.wikipedia.org/wiki/Geohash
     *
     * @example $redis->geohash('cities', 'Chico', 'Cupertino');
     */
    public function geohash(string $key, string $member, string ...$other_members): Redis|array|false;

    /**
     * Return the longitude and latitude for one or more members of a geospacially encoded sorted set.
     *
     * @param string $key              The set to query.
     * @param string $member           The first member to query.
     * @param string ...$other_members One or more members to query.
     *
     * @return Redis|array|false An array of longitude and latitude pairs.
     *
     * @see https://redis.io/docs/latest/commands/geopos/
     *
     * @example $redis->geopos('cities', 'Seattle', 'New York');
     */
    public function geopos(string $key, string $member, string ...$other_members): Redis|array|false;

    /**
     * Retrieve members of a geospacially sorted set that are within a certain radius of a location.
     *
     * @param string $key     The set to query
     * @param float  $lng     The longitude of the location to query.
     * @param float  $lat     The latitude of the location to query.
     * @param float  $radius  The radius of the area to include.
     * @param string $unit    The unit of the provided radius (defaults to 'meters).
     *                        See {@link Redis::geodist} for possible units.
     * @param array  $options An array of options that modifies how the command behaves.
     *                        ```php
     *                        $options = [
     *                            'WITHCOORD',     # Return members and their coordinates.
     *                            'WITHDIST',      # Return members and their distances from the center.
     *                            'WITHHASH',      # Return members GeoHash string.
     *                            'ASC' | 'DESC',  # The sort order of returned members
     *
     *                            # Limit to N returned members.  Optionally a two element array may be
     *                            # passed as the `LIMIT` argument, and the `ANY` argument.
     *                            'COUNT' => [<int>], or [<int>, <bool>]
     *
     *                            # Instead of returning members, store them in the specified key.
     *                            'STORE' => <string>
     *
     *                            # Store the distances in the specified key
     *                            'STOREDIST' => <string>
     *                        ];
     *                        ```
     *
     * @return mixed This command can return various things, depending on the options passed.
     *
     * @see https://redis.io/docs/latest/commands/georadius/
     *
     * @example $redis->georadius('cities', 47.608013, -122.335167, 1000, 'km');
     */
    public function georadius(string $key, float $lng, float $lat, float $radius, string $unit, array $options = []): mixed;

    /**
     * A readonly variant of `GEORADIUS` that may be executed on replicas.
     *
     * @see Redis::georadius
     * @see https://redis.io/docs/latest/commands/georadius_ro/
     *
     * @example
     * $redis->georadius_ro('cities', -122.335167, 47.608013, 100, 'km');
     *
     */
    public function georadius_ro(string $key, float $lng, float $lat, float $radius, string $unit, array $options = []): mixed;

    /**
     * Similar to `GEORADIUS` except it uses a member as the center of the query.
     *
     * @param string $key     The key to query.
     * @param string $member  The member to treat as the center of the query.
     * @param float  $radius  The radius from the member to include.
     * @param string $unit    The unit of the provided radius
     *                        See {@link Redis::geodist} for possible units.
     * @param array  $options An array with various options to modify the command's behavior.
     *                        See {@link Redis::georadius} for options.
     *
     * @return mixed This command can return various things depending on options.
     *
     * @see https://redis.io/docs/latest/commands/georadiusbymember/
     *
     * @example $redis->georadiusbymember('cities', 'Seattle', 200, 'mi');
     */
    public function georadiusbymember(string $key, string $member, float $radius, string $unit, array $options = []): mixed;

    /**
     * This is the read-only variant of `GEORADIUSBYMEMBER` that can be run on replicas.
     *
     * @see https://redis.io/docs/latest/commands/georadiusbymember_ro/
     *
     * @example
     * $redis->georadiusbymember_ro('cities', 'Seattle', 200, 'mi');
     *
     */
    public function georadiusbymember_ro(string $key, string $member, float $radius, string $unit, array $options = []): mixed;

    /**
     * Search a geospacial sorted set for members in various ways.
     *
     * @param string          $key      The set to query.
     * @param array|string    $position Either a two element array with longitude and latitude, or
     *                                  a string representing a member of the set.
     * @param array|int|float $shape    Either a number representine the radius of a circle to search, or
     *                                  a two element array representing the width and height of a box
     *                                  to search.
     * @param string          $unit     The unit of our shape.  See {@link Redis::geodist} for possible units.
     * @param array           $options  @see {@link Redis::georadius} for options.  Note that the `STORE`
     *                                  options are not allowed for this command.
     *
     * @see https://redis.io/docs/latest/commands/geosearch/
     *
     * @example
     * $redis->geosearch('cities', 'Seattle', 50, 'km', ['WITHDIST']);
     *
     */
    public function geosearch(string $key, array|string $position, array|int|float $shape, string $unit, array $options = []): array;

    /**
     * Search a geospacial sorted set for members within a given area or range, storing the results into
     * a new set.
     *
     * @param string $dst The destination where results will be stored.
     * @param string $src The key to query.
     * @param array|string    $position Either a two element array with longitude and latitude, or
     *                                  a string representing a member of the set.
     * @param array|int|float $shape    Either a number representine the radius of a circle to search, or
     *                                  a two element array representing the width and height of a box
     *                                  to search.
     * @param string          $unit     The unit of our shape.  See {@link Redis::geodist} for possible units.
     * @param array           $options
     *                        ```php
     *                        $options = [
     *                            'ASC' | 'DESC',  # The sort order of returned members
     *                            'WITHDIST'       # Also store distances.
     *
     *                            # Limit to N returned members.  Optionally a two element array may be
     *                            # passed as the `LIMIT` argument, and the `ANY` argument.
     *                            'COUNT' => [<int>], or [<int>, <bool>]
     *                        ];
     *                        ```
     *
     * @see https://redis.io/docs/latest/commands/geosearchstore/
     *
     * @example
     * $redis->geosearchstore('west:cities', 'cities', 'Seattle', 50, 'km', ['DESC']);
     *
     */
    public function geosearchstore(string $dst, string $src, array|string $position, array|int|float $shape, string $unit, array $options = []): Redis|array|int|false;

    /**
     * Retrieve a string keys value.
     *
     * @param  string  $key The key to query
     * @return mixed   The keys value or false if it did not exist.
     *
     * @see https://redis.io/docs/latest/commands/get/
     *
     * @example $redis->get('foo');
     */
    public function get(string $key): mixed;

    /**
     * Retrieve a value and metadata of key.
     *
     * @param  string  $key The key to query
     * @return Redis|array|false
     *
     * @example $redis->getWithMeta('foo');
     */
    public function getWithMeta(string $key): Redis|array|false;

    /**
     * Get the authentication information on the connection, if any.
     *
     * @return mixed The authentication information used to authenticate the connection.
     *
     * @see Redis::auth()
     *
     * @example
     * $redis->getAuth();
     *
     */
    public function getAuth(): mixed;

    /**
     * Get the bit at a given index in a string key.
     *
     * @param string $key The key to query.
     * @param int    $idx The Nth bit that we want to query.
     *
     * @example $redis->getbit('bitmap', 1337);
     *
     * @see https://redis.io/docs/latest/commands/getbit/
     */
    public function getBit(string $key, int $idx): Redis|int|false;

    /**
     * Get the value of a key and optionally set it's expiration.
     *
     * @param string $key    The key to query
     * @param array $options Options to modify how the command works.
     *                       ```php
     *                       $options = [
     *                           'EX'     => <seconds>      # Expire in N seconds
     *                           'PX'     => <milliseconds> # Expire in N milliseconds
     *                           'EXAT'   => <timestamp>    # Expire at a unix timestamp (in seconds)
     *                           'PXAT'   => <mstimestamp>  # Expire at a unix timestamp (in milliseconds);
     *                           'PERSIST'                  # Remove any configured expiration on the key.
     *                       ];
     *                       ```
     *
     * @return Redis|string|bool The key's value or false if it didn't exist.
     *
     * @see https://redis.io/docs/latest/commands/getex/
     *
     * @example $redis->getEx('mykey', ['EX' => 60]);
     */
    public function getEx(string $key, array $options = []): Redis|string|bool;

    /**
     * Get the database number PhpRedis thinks we're connected to.
     *
     * This value is updated internally in PhpRedis each time {@link Redis::select} is called.
     *
     * @return int The database we're connected to.
     *
     * @see Redis::select()
     * @see https://redis.io/docs/latest/commands/select/
     *
     * @example
     * $redis->getDBNum();
     *
     */
    public function getDBNum(): int;

    /**
     * Get a key from Redis and delete it in an atomic operation.
     *
     * @param string $key The key to get/delete.
     * @return Redis|string|bool The value of the key or false if it didn't exist.
     *
     * @see https://redis.io/docs/latest/commands/getdel/
     *
     * @example $redis->getdel('token:123');
     */
    public function getDel(string $key): Redis|string|bool;

    /**
     * Return the host or Unix socket we are connected to.
     *
     * @return string The host or Unix socket.
     *
     * @example
     * $redis->getHost();
     *
     */
    public function getHost(): string;

    /**
     * Get the last error returned to us from Redis, if any.
     *
     * @return string|null The error string or NULL if there is none.
     *
     * @example
     * $redis->getLastError();
     *
     */
    public function getLastError(): string|null;

    /**
     * Returns whether the connection is in ATOMIC, MULTI, or PIPELINE mode
     *
     * @return int The mode we're in.
     *
     * @example
     * $redis->getMode();
     *
     */
    public function getMode(): int;

    /**
     * Retrieve the value of a configuration setting as set by Redis::setOption()
     *
     * @see Redis::setOption() for a detailed list of options and their values.
     *
     * @return mixed The setting itself or false on failure
     *
     * @example
     * $redis->getOption(Redis::OPT_PREFIX);
     *
     */
    public function getOption(int $option): mixed;

    /**
     * Get the persistent connection ID, if there is one.
     *
     * @return string|null The ID or NULL if we don't have one.
     *
     * @example
     * $redis->getPersistentID();
     *
     */
    public function getPersistentID(): string|null;

    /**
     * Get the port we are connected to.  This number will be zero if we are connected to a unix socket.
     *
     * @return int The port.
     *
     * @example
     * $redis->getPort();
     *
     */
    public function getPort(): int;

    /**
     * Get the server name as reported by the `HELLO` response.
     *
     * @return string|false
     *
     * @example
     * $redis->serverName();
     *
     */
    public function serverName(): string|false;

    /**
     * Get the server version as reported by the `HELLO` response.
     *
     * @return string|false
     *
     * @example
     * $redis->serverVersion();
     *
     */
    public function serverVersion(): string|false;

    /**
     * Retrieve a substring of a string by index.
     *
     * @param string $key   The string to query.
     * @param int    $start The zero-based starting index.
     * @param int    $end   The zero-based ending index.
     *
     * @return Redis|string|false The substring or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/getrange/
     *
     * @example
     * $redis->set('silly-word', 'Supercalifragilisticexpialidocious');
     * echo $redis->getRange('silly-word', 0, 4) . "\n";
     */
    public function getRange(string $key, int $start, int $end): Redis|string|false;

    /**
     * Get the longest common subsequence between two string keys.
     *
     * @param string     $key1    The first key to check
     * @param string     $key2    The second key to check
     * @param array|null $options An optional array of modifiers for the command.
     *
     *                            ```php
     *                            $options = [
     *                                'MINMATCHLEN'  => int  # Exclude matching substrings that are less than this value
     *
     *                                'WITHMATCHLEN' => bool # Whether each match should also include its length.
     *
     *                                'LEN'                  # Return the length of the longest subsequence
     *
     *                                'IDX'                  # Each returned match will include the indexes where the
     *                                                       # match occurs in each string.
     *                            ];
     *                            ```
     *
     *                            NOTE:  'LEN' cannot be used with 'IDX'.
     *
     * @return Redis|string|array|int|false Various reply types depending on options.
     *
     * @see https://redis.io/docs/latest/commands/lcs/
     *
     * @example
     * $redis->set('seq1', 'gtaggcccgcacggtctttaatgtatccctgtttaccatgccatacctgagcgcatacgc');
     * $redis->set('seq2', 'aactcggcgcgagtaccaggccaaggtcgttccagagcaaagactcgtgccccgctgagc');
     * echo $redis->lcs('seq1', 'seq2') . "\n";
     */
    public function lcs(string $key1, string $key2, ?array $options = null): Redis|string|array|int|false;

    /**
     * Get the currently set read timeout on the connection.
     *
     * @return float The timeout.
     *
     * @example
     * $redis->getReadTimeout();
     *
     */
    public function getReadTimeout(): float;

    /**
     * Sets a key and returns any previously set value, if the key already existed.
     *
     * @param string $key The key to set.
     * @param mixed $value The value to set the key to.
     *
     * @return Redis|string|false The old value of the key or false if it didn't exist.
     *
     * @see https://redis.io/docs/latest/commands/getset/
     *
     * @example
     * $redis->getset('captain', 'Pike');
     * $redis->getset('captain', 'Kirk');
     */
    public function getset(string $key, mixed $value): Redis|string|false;

    /**
     * Retrieve any set connection timeout
     *
     * @return float|false The currently set timeout or false on failure (e.g. we aren't connected).
     *
     * @example
     * $redis->getTimeout();
     *
     */
    public function getTimeout(): float|false;

    /**
     * Get the number of bytes sent and received on the socket.
     *
     * @return array An array in the form [$sent_bytes, $received_bytes]
     *
     * @example
     * $redis->getTransferredBytes();
     *
     */
    public function getTransferredBytes(): array;

    /**
     * Reset the number of bytes sent and received on the socket.
     *
     * @return void
     *
     * @example
     * $redis->clearTransferredBytes();
     *
     */
    public function clearTransferredBytes(): void;

    /**
     * Remove one or more fields from a hash.
     *
     * @param string $key             The hash key in question.
     * @param string $field           The first field to remove
     * @param string ...$other_fields One or more additional fields to remove.
     *
     * @return Redis|int|false     The number of fields actually removed.
     *
     * @see https://redis.io/docs/latest/commands/hdel/
     *
     * @example $redis->hDel('communication', 'Alice', 'Bob');
     */
    public function hDel(string $key, string $field, string ...$other_fields): Redis|int|false;

    /**
     * Checks whether a field exists in a hash.
     *
     * @param string $key   The hash to query.
     * @param string $field The field to check
     *
     * @return Redis|bool   True if it exists, false if not.
     *
     * @see https://redis.io/docs/latest/commands/hexists/
     *
     * @example $redis->hExists('communication', 'Alice');
     */
    public function hExists(string $key, string $field): Redis|bool;

    public function hGet(string $key, string $member): mixed;

    /**
     * Read every field and value from a hash.
     *
     * @param string $key The hash to query.
     * @return Redis|array<string|int, mixed>|false All fields and values or false if the key didn't exist.
     *
     * @see https://redis.io/docs/latest/commands/hgetall/
     *
     * @example
     * $redis->hgetall('myhash');
     */
    public function hGetAll(string $key): Redis|array|false;

    /**
     * Retrieve a value and metadata of hash field.
     *
     * @param  string  $key The key to query
     * @param  string  $member The key to query
     * @return mixed
     *
     * @example $redis->hgetWithMeta('foo', 'field');
     */
    public function hGetWithMeta(string $key, string $member): mixed;

    /**
     * Increment a hash field's value by an integer
     *
     * @param string $key   The hash to modify
     * @param string $field The field to increment
     * @param int    $value How much to increment the value.
     *
     * @return Redis|int|false The new value of the field.
     *
     * @see https://redis.io/docs/latest/commands/hincrby/
     *
     * @example
     * $redis->hMSet('player:1', ['name' => 'Alice', 'score' => 0]);
     * $redis->hincrby('player:1', 'score', 10);
     *
     */
    public function hIncrBy(string $key, string $field, int $value): Redis|int|false;

    /**
     * Increment a hash field by a floating point value
     *
     * @param string $key The hash with the field to increment.
     * @param string $field The field to increment.
     *
     * @return Redis|float|false The field value after incremented.
     *
     * @see https://redis.io/docs/latest/commands/hincrbyfloat/
     *
     * @example
     * $redis->hincrbyfloat('numbers', 'tau', 2 * 3.1415926);
     */
    public function hIncrByFloat(string $key, string $field, float $value): Redis|float|false;

    /**
     * Retrieve all of the fields of a hash.
     *
     * @param string $key The hash to query.
     *
     * @return Redis|list<string>|false The fields in the hash or false if the hash doesn't exist.
     *
     * @see https://redis.io/docs/latest/commands/hkeys/
     *
     * @example $redis->hkeys('myhash');
     */
    public function hKeys(string $key): Redis|array|false;

    /**
     * Get the number of fields in a hash.
     *
     * @see https://redis.io/docs/latest/commands/hlen/
     *
     * @param string $key The hash to check.
     *
     * @return Redis|int|false The number of fields or false if the key didn't exist.
     *
     * @example $redis->hlen('myhash');
     */
    public function hLen(string $key): Redis|int|false;

    /**
     * Get one or more fields from a hash.
     *
     * @param string $key    The hash to query.
     * @param array  $fields One or more fields to query in the hash.
     *
     * @return Redis|array|false The fields and values or false if the key didn't exist.
     *
     * @see https://redis.io/docs/latest/commands/hmget/
     *
     * @example $redis->hMGet('player:1', ['name', 'score']);
     */
    public function hMget(string $key, array $fields): Redis|array|false;

    /**
     * Get one or more fields of a hash while optionally setting expiration
     * information
     *
     * @param string            $key    The hash to query.
     * @param array             $fields One or more fields to query in the hash.
     * @param string|array|null $expiry Info about the expiration
     *
     * @return Redis|array|false The fields and values or false if the key didn't exist.
     *
     * @see https://redis.io/docs/latest/commands/hgetex/
     *
     * @example
     * $redis->hgetex('profiles', ['name', 'email'], ['EX' => 60]);
     *
     */
    public function hgetex(string $key, array $fields, string|array|null $expiry = null): Redis|array|false;

    /**
     * Set one or more fields in a hash with optional expiration information.
     *
     * @param string $key         The hash to create/update.
     * @param array $fields       An array with fields values.
     * @param array|null $expiry  Info about the expiration
     *
     * @return Redis|int|false One if fields were set zero if not.
     *
     * @see https://redis.io/docs/latest/commands/hsetex/
     *
     * @example
     * $redis->hsetex('profiles', ['token' => 'abc123'], ['EX' => 60]);
     *
     */
    public function hsetex(string $key, array $fields, ?array $expiry = null): Redis|int|false;

    /**
     * Get one or more fields and delete them
     *
     * @param string $key         The hash in question
     * @param array $fields       One or more fields
     *
     * @return Redis|array|false  The field and values or false on failure
     *
     * @see https://redis.io/docs/latest/commands/hgetdel/
     *
     * @example
     * $redis->hgetdel('profiles', ['token']);
     *
     */
    public function hgetdel(string $key, array $fields): Redis|array|false;

    /**
     * Add or update one or more hash fields and values
     *
     * @param string $key        The hash to create/update
     * @param array  $fieldvals  An associative array with fields and their values.
     *
     * @return Redis|bool True if the operation was successful
     *
     * @see https://redis.io/docs/latest/commands/hmset/
     *
     * @example $redis->hmset('updates', ['status' => 'starting', 'elapsed' => 0]);
     */
    public function hMset(string $key, array $fieldvals): Redis|bool;

    /**
     * Get one or more random field from a hash.
     *
     * @param string     $key     The hash to query.
     * @param array|null $options An array of options to modify how the command behaves.
     *
     *                            ```php
     *                            $options = [
     *                                'COUNT'      => int  # An optional number of fields to return.
     *                                'WITHVALUES' => bool # Also return the field values.
     *                            ];
     *                            ```
     *
     * @return Redis|string|array|false One or more random fields (and possibly values).
     *
     * @see https://redis.io/docs/latest/commands/hrandfield/
     *
     * @example $redis->hrandfield('settings');
     * @example $redis->hrandfield('settings', ['count' => 2, 'withvalues' => true]);
     */
    public function hRandField(string $key, ?array $options = null): Redis|string|array|false;

    /**
     * Add or update one or more hash fields and values.
     *
     * @param string $key                The hash to create/update.
     * @param mixed  ...$fields_and_vals Argument pairs of fields and values. Alternatively, an associative array with the
     *                                   fields and their values.
     *
     * @return Redis|int|false The number of fields that were added, or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/hset/
     *
     * @example $redis->hSet('player:1', 'name', 'Kim', 'score', 78);
     * @example $redis->hSet('player:1', ['name' => 'Kim', 'score' => 78]);
     */
    public function hSet(string $key, mixed ...$fields_and_vals): Redis|int|false;

    /**
     * Set a hash field and value, but only if that field does not exist
     *
     * @param string $key   The hash to update.
     * @param string $field The value to set.
     *
     * @return Redis|bool True if the field was set and false if not.
     *
     * @see https://redis.io/docs/latest/commands/hsetnx/
     *
     * @example
     * $redis->hsetnx('player:1', 'lock', 'enabled');
     * $redis->hsetnx('player:1', 'lock', 'enabled');
     */
    public function hSetNx(string $key, string $field, mixed $value): Redis|bool;

    /**
     * Get the string length of a hash field
     *
     * @param string $key   The hash to query.
     * @param string $field The field to query.
     *
     * @return Redis|int|false The string length of the field or false.
     *
     * @example
     * $redis = new Redis(['host' => 'localhost']);
     * $redis->del('hash');
     * $redis->hmset('hash', ['50bytes' => str_repeat('a', 50)]);
     * $redis->hstrlen('hash', '50bytes');
     *
     * @see https://redis.io/docs/latest/commands/hstrlen/
     */
    public function hStrLen(string $key, string $field): Redis|int|false;

    /**
     * Get all of the values from a hash.
     *
     * @param string $key The hash to query.
     *
     * @return Redis|list<mixed>|false The values from the hash.
     *
     * @see https://redis.io/docs/latest/commands/hvals/
     *
     * @example $redis->hvals('player:1');
     */
    public function hVals(string $key): Redis|array|false;

    /**
     * Set the expiration on one or more fields in a hash.
     *
     * @param string      $key    The hash to update.
     * @param int         $ttl    The time to live in seconds.
     * @param array       $fields The fields to set the expiration on.
     * @param string|null $mode   An optional mode (NX, XX, ETC)
     *
     * @return Redis|array|false
     *
     * @see https://redis.io/docs/latest/commands/hexpire/
     *
     * @example
     * $redis->hexpire('profiles', 300, ['token'], 'NX');
     *
     */
    public function hexpire(string $key, int $ttl, array $fields,
                            ?string $mode = NULL): Redis|array|false;

    /**
     * Set the expiration on one or more fields in a hash in milliseconds.
     *
     * @param string      $key    The hash to update.
     * @param int         $ttl    The time to live in milliseconds.
     * @param array       $fields The fields to set the expiration on.
     * @param string|null $mode   An optional mode (NX, XX, ETC)
     *
     * @return Redis|array|false
     *
     * @see https://redis.io/docs/latest/commands/hexpire/
     *
     * @example
     * $redis->hpexpire('profiles', 1500, ['token']);
     *
     */
    public function hpexpire(string $key, int $ttl, array $fields,
                            ?string $mode = NULL): Redis|array|false;

    /**
     * Set the expiration time on one or more fields of a hash.
     *
     * @param string      $key    The hash to update.
     * @param int         $time   The time to live in seconds.
     * @param array       $fields The fields to set the expiration on.
     * @param string|null $mode   An optional mode (NX, XX, ETC)
     *
     * @return Redis|array|false
     *
     * @see https://redis.io/docs/latest/commands/hexpire/
     *
     * @example
     * $redis->hexpireat('profiles', time() + 600, ['token']);
     *
     */
    public function hexpireat(string $key, int $time, array $fields,
                              ?string $mode = NULL): Redis|array|false;

    /**
     * Set the expiration time on one or more fields of a hash in milliseconds.
     *
     * @param string      $key    The hash to update.
     * @param int         $mstime The time to live in milliseconds.
     * @param array       $fields The fields to set the expiration on.
     * @param string|null $mode   An optional mode (NX, XX, ETC)
     *
     * @return Redis|array|false
     *
     * @see https://redis.io/docs/latest/commands/hexpire/
     *
     * @example
     * $redis->hpexpireat('profiles', (int) (microtime(true) * 1000) + 60000, ['token']);
     *
     */
    public function hpexpireat(string $key, int $mstime, array $fields,
                               ?string $mode = NULL): Redis|array|false;

    /**
     * Get the TTL of one or more fields in a hash
     *
     * @param string $key    The hash to query.
     * @param array  $fields The fields to query.
     *
     * @return Redis|array|false
     *
     * @see https://redis.io/docs/latest/commands/httl/
     *
     * @example
     * $redis->httl('profiles', ['token']);
     *
     */
    public function httl(string $key, array $fields): Redis|array|false;

    /**
     * Get the millisecond TTL of one or more fields in a hash
     *
     * @param string $key    The hash to query.
     * @param array  $fields The fields to query.
     *
     * @return Redis|array|false
     *
     * @see https://redis.io/docs/latest/commands/hpttl/
     *
     * @example
     * $redis->hpttl('profiles', ['token']);
     *
     */
    public function hpttl(string $key, array $fields): Redis|array|false;

    /**
     * Get the expiration time of one or more fields in a hash
     *
     * @param string $key    The hash to query.
     * @param array  $fields The fields to query.
     *
     * @return Redis|array|false
     *
     * @see https://redis.io/docs/latest/commands/hexpiretime/
     *
     * @example
     * $redis->hexpiretime('profiles', ['token']);
     *
     */
    public function hexpiretime(string $key, array $fields): Redis|array|false;

    /**
     * Get the expiration time in milliseconds of one or more fields in a hash
     *
     * @param string $key    The hash to query.
     * @param array  $fields The fields to query.
     *
     * @return Redis|array|false
     *
     * @see https://redis.io/docs/latest/commands/hpexpiretime/
     *
     * @example
     * $redis->hpexpiretime('profiles', ['token']);
     *
     */
    public function hpexpiretime(string $key, array $fields): Redis|array|false;

    /**
     * Persist one or more hash fields
     *
     * @param string $key    The hash to query.
     * @param array  $fields The fields to query.
     *
     * @return Redis|array|false
     *
     * @see https://redis.io/docs/latest/commands/hpersist/
     *
     * @example
     * $redis->hpersist('profiles', ['token']);
     *
     */
    public function hpersist(string $key, array $fields): Redis|array|false;

    /**
     * Iterate over the fields and values of a hash in an incremental fashion.
     *
     * @see https://redis.io/docs/latest/commands/hscan/
     * @see https://redis.io/docs/latest/commands/scan/
     *
     * @param string          $key      The hash to query.
     * @param int|string|null $iterator The scan iterator, which should be initialized to NULL before the first call.
     *                                  This value will be updated after every call to hscan, until it reaches zero
     *                                  meaning the scan is complete.
     * @param string|null     $pattern  An optional glob-style pattern to filter fields with.
     * @param int             $count    An optional hint to Redis about how many fields and values to return per HSCAN.
     *
     * @return Redis|array|bool An array with a subset of fields and values.
     *
     * @example
     * $redis = new Redis(['host' => 'localhost']);
     *
     * $redis->del('big-hash');
     *
     * for ($i = 0; $i < 1000; $i++) {
     *     $fields["field:$i"] = "value:$i";
     * }
     *
     * $redis->hmset('big-hash', $fields);
     *
     * $it = null;
     *
     * do {
     *     // Scan the hash but limit it to fields that match '*:1?3'
     *     $fields = $redis->hscan('big-hash', $it, '*:1?3');
     *
     *     foreach ($fields as $field => $value) {
     *         echo "[$field] => $value\n";
     *     }
     * } while ($it != 0);
     */
    public function hscan(string $key, null|int|string &$iterator, ?string $pattern = null, int $count = 0): Redis|array|bool;

    /**
     * Set an expiration on a key member (KeyDB only).
     *
     * @see https://docs.keydb.dev/docs/commands/#expiremember
     * @see https://redis.io/docs/latest/commands/expiremember/
     *
     * @param string $key The key to expire
     * @param string $field The field to expire
     * @param string|null $unit The unit of the ttl (s, or ms).
     *
     * @example
     * $redis->expiremember('profiles', 'token', 60);
     *
     */
    public function expiremember(string $key, string $field, int $ttl, ?string $unit = null): Redis|int|false;

    /**
     * Set an expiration on a key membert to a specific unix timestamp (KeyDB only).
     *
     * @see https://docs.keydb.dev/docs/commands/#expirememberat
     * @see https://redis.io/docs/latest/commands/expirememberat/
     *
     * @param string $key The key to expire
     * @param string $field The field to expire
     * @param int $timestamp The unix timestamp to expire at.
     *
     * @example
     * $redis->expirememberat('profiles', 'token', time() + 300);
     *
     */
    public function expirememberat(string $key, string $field, int $timestamp): Redis|int|false;

    /**
     * Increment a key's value, optionally by a specific amount.
     *
     * @see https://redis.io/docs/latest/commands/incr/
     * @see https://redis.io/docs/latest/commands/incrby/
     *
     * @param string $key The key to increment
     * @param int    $by  An optional amount to increment by.
     *
     * @return Redis|int|false  The new value of the key after incremented.
     *
     * @example $redis->incr('mycounter');
     * @example $redis->incr('mycounter', 10);
     */
    public function incr(string $key, int $by = 1): Redis|int|false;

    /**
     * Increment a key by a specific integer value
     *
     * @see https://redis.io/docs/latest/commands/incrby/
     *
     * @param string $key   The key to increment.
     * @param int    $value The amount to increment.
     *
     * @example
     * $redis->set('primes', 2);
     * $redis->incrby('primes', 1);
     * $redis->incrby('primes', 2);
     * $redis->incrby('primes', 2);
     * $redis->incrby('primes', 4);
     */
    public function incrBy(string $key, int $value): Redis|int|false;

    /**
     * Increment a numeric key by a floating point value.
     *
     * @param string $key   The key to increment
     * @param float  $value How much to increment (or decrement) the value.
     *
     * @return Redis|float|false The new value of the key or false if the key didn't contain a string.
     *
     * @see https://redis.io/docs/latest/commands/incrbyfloat/
     *
     * @example
     * $redis->incrbyfloat('tau', 3.1415926);
     * $redis->incrbyfloat('tau', 3.1415926);
     */
    public function incrByFloat(string $key, float $value): Redis|float|false;

    /**
     * Retrieve information about the connected redis-server.  If no arguments are passed to
     * this function, redis will return every info field.  Alternatively you may pass a specific
     * section you want returned (e.g. 'server', or 'memory') to receive only information pertaining
     * to that section.
     *
     * If connected to Redis server >= 7.0.0 you may pass multiple optional sections.
     *
     * @see https://redis.io/docs/latest/commands/info/
     *
     * @param string ...$sections Optional section(s) you wish Redis server to return.
     *
     * @return Redis|array|false
     *
     * @example
     * $redis->info('server', 'stats');
     *
     */
    public function info(string ...$sections): Redis|array|false;

    /**
     * Check if we are currently connected to a Redis instance.
     *
     * @return bool True if we are, false if not
     *
     * @example
     * $redis->isConnected();
     *
     */
    public function isConnected(): bool;

    /**
     * @param string $pattern
     * @return Redis|list<string>|false
     *
     * @see https://redis.io/docs/latest/commands/keys/
     *
     * @example
     * $redis->keys('session:*');
     *
     */
    public function keys(string $pattern);

    /**
     * @return Redis|int|false
     *
     * @see https://redis.io/docs/latest/commands/linsert/
     *
     * @example
     * $redis->lInsert('letters', Redis::AFTER, 'b', 'beta');
     *
     */
    public function lInsert(string $key, string $pos, mixed $pivot, mixed $value);

    /**
     * Retrieve the length of a list.
     *
     * @param string $key The list
     *
     * @return Redis|int|false The number of elements in the list or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/llen/
     *
     * @example
     * $redis->lLen('queue');
     *
     */
    public function lLen(string $key): Redis|int|false;

    /**
     * Move an element from one list into another.
     *
     * @param string $src       The source list.
     * @param string $dst       The destination list
     * @param string $wherefrom Where in the source list to retrieve the element.  This can be either
     *                          - `Redis::LEFT`, or `Redis::RIGHT`.
     * @param string $whereto   Where in the destination list to put the element.  This can be either
     *                          - `Redis::LEFT`, or `Redis::RIGHT`.
     * @return Redis|string|false The element removed from the source list.
     *
     * @see https://redis.io/docs/latest/commands/lmove/
     *
     * @example
     * $redis->rPush('numbers', 'one', 'two', 'three');
     * $redis->lMove('numbers', 'odds', Redis::LEFT, Redis::LEFT);
     */
    public function lMove(string $src, string $dst, string $wherefrom, string $whereto): Redis|string|false;

    /**
     * Move an element from one list to another, blocking up to a timeout until an element is available.
     *
     * @param string $src       The source list
     * @param string $dst       The destination list
     * @param string $wherefrom Where in the source list to extract the element.
     *                          - `Redis::LEFT`, or `Redis::RIGHT`.
     * @param string $whereto   Where in the destination list to put the element.
     *                          - `Redis::LEFT`, or `Redis::RIGHT`.
     * @param float $timeout    How long to block for an element.
     *
     * @return Redis|string|false;
     *
     * @see https://redis.io/docs/latest/commands/blmove/
     *
     * @example
     * @redis->lPush('numbers', 'one');
     * @redis->blmove('numbers', 'odds', Redis::LEFT, Redis::LEFT 1.0);
     * // This call will block, if no additional elements are in 'numbers'
     * @redis->blmove('numbers', 'odds', Redis::LEFT, Redis::LEFT, 1.0);
     */
    public function blmove(string $src, string $dst, string $wherefrom, string $whereto, float $timeout): Redis|string|false;

    /**
     * Pop one or more elements off a list.
     *
     * @param string $key   The list to pop from.
     * @param int    $count Optional number of elements to remove.  By default one element is popped.
     *
     * @return Redis|bool|string|array Will return the element(s) popped from the list or false/NULL
     *                                 if none was removed.
     *
     * @see https://redis.io/docs/latest/commands/lpop/
     *
     * @example $redis->lpop('mylist');
     * @example $redis->lpop('mylist', 4);
     */
    public function lPop(string $key, int $count = 0): Redis|bool|string|array;

    /**
     * Retrieve the index of an element in a list.
     *
     * @param string     $key     The list to query.
     * @param mixed      $value   The value to search for.
     * @param array|null $options Options to configure how the command operates
     *                            ```php
     *                            $options = [
     *                                # How many matches to return.  By default a single match is returned.
     *                                # If count is set to zero, it means unlimited.
     *                                'COUNT' => <num-matches>
     *
     *                                # Specify which match you want returned.  `RANK` 1 means "the first match"
     *                                # 2 means the second, and so on.  If passed as a negative number the
     *                                # RANK is computed right to left, so a `RANK` of -1 means "the last match".
     *                                'RANK'  => <rank>
     *
     *                                # This argument allows you to limit how many elements Redis will search before
     *                                # returning.  This is useful to prevent Redis searching very long lists while
     *                                # blocking the client.
     *                                'MAXLEN => <max-len>
     *                            ];
     *                            ```
     *
     * @return Redis|null|bool|int|array Returns one or more of the matching indexes, or null/false if none were found.
     *
     * @see https://redis.io/docs/latest/commands/lpos/
     *
     * @example
     * $redis->lPos('queue', 'job-42');
     *
     */
    public function lPos(string $key, mixed $value, ?array $options = null): Redis|null|bool|int|array;

    /**
     * Prepend one or more elements to a list.
     *
     * @param string $key         The list to prepend.
     * @param mixed  ...$elements One or more elements to prepend.
     *
     * @return Redis|int|false The new length of the list after prepending.
     *
     * @see https://redis.io/docs/latest/commands/lpush/
     *
     * @example $redis->lPush('mylist', 'cat', 'bear', 'aligator');
     */
    public function lPush(string $key, mixed ...$elements): Redis|int|false;

    /**
     * Append one or more elements to a list.
     *
     * @param string $key         The list to append to.
     * @param mixed  ...$elements one or more elements to append.
     *
     * @return Redis|int|false The new length of the list
     *
     * @see https://redis.io/docs/latest/commands/rpush/
     *
     * @example $redis->rPush('mylist', 'xray', 'yankee', 'zebra');
     */
    public function rPush(string $key, mixed ...$elements): Redis|int|false;

    /**
     * Prepend an element to a list but only if the list exists
     *
     * @param string $key   The key to prepend to.
     * @param mixed  $value The value to prepend.
     *
     * @return Redis|int|false The new length of the list.
     *
     * @see https://redis.io/docs/latest/commands/lpushx/
     *
     * @example
     * $redis->lPushx('queue', 'job-42');
     *
     */
    public function lPushx(string $key, mixed $value): Redis|int|false;

    /**
     * Append an element to a list but only if the list exists
     *
     * @param string $key   The key to prepend to.
     * @param mixed  $value The value to prepend.
     *
     * @return Redis|int|false The new length of the list.
     *
     * @see https://redis.io/docs/latest/commands/rpushx/
     *
     * @example
     * $redis->rPushx('queue', 'job-99');
     *
     */
    public function rPushx(string $key, mixed $value): Redis|int|false;

    /**
     * Set a list element at an index to a specific value.
     *
     * @param string $key   The list to modify.
     * @param int    $index The position of the element to change.
     * @param mixed  $value The new value.
     *
     * @return Redis|bool True if the list was modified.
     *
     * @see https://redis.io/docs/latest/commands/lset/
     *
     * @example
     * $redis->lSet('queue', 0, 'job-42');
     *
     */
    public function lSet(string $key, int $index, mixed $value): Redis|bool;

    /**
     * Retrieve the last time Redis' database was persisted to disk.
     *
     * @return int The unix timestamp of the last save time
     *
     * @see https://redis.io/docs/latest/commands/lastsave/
     *
     * @example
     * $redis->lastSave();
     *
     */
    public function lastSave(): int;

    /**
     * Get the element of a list by its index.
     *
     * @param string $key   The key to query
     * @param int    $index The index to check.
     * @return mixed The index or NULL/false if the element was not found.
     *
     * @see https://redis.io/docs/latest/commands/lindex/
     *
     * @example
     * $redis->lindex('queue', 0);
     *
     */
    public function lindex(string $key, int $index): mixed;

    /**
     * Retrieve elements from a list.
     *
     * @param string $key   The list to query.
     * @param int    $start The beginning index to retrieve.  This number can be negative
     *                      meaning start from the end of the list.
     * @param int    $end   The end index to retrieve.  This can also be negative to start
     *                      from the end of the list.
     *
     * @return Redis|array|false The range of elements between the indexes.
     *
     * @see https://redis.io/docs/latest/commands/lrange/
     *
     * @example $redis->lrange('mylist', 0, -1);  // the whole list
     * @example $redis->lrange('mylist', -2, -1); // the last two elements in the list.
     */
    public function lrange(string $key, int $start , int $end): Redis|array|false;

    /**
     * Remove one or more matching elements from a list.
     *
     * @param string $key   The list to truncate.
     * @param mixed  $value The value to remove.
     * @param int    $count How many elements matching the value to remove.
     *
     * @return Redis|int|false The number of elements removed.
     *
     * @see https://redis.io/docs/latest/commands/lrem/
     *
     * @example
     * $redis->lrem('queue', 0, 'expired-job');
     *
     */
    public function lrem(string $key, mixed $value, int $count = 0): Redis|int|false;

    /**
     * Trim a list to a subrange of elements.
     *
     * @param string $key   The list to trim
     * @param int    $start The starting index to keep
     * @param int    $end   The ending index to keep.
     *
     * @return Redis|bool true if the list was trimmed.
     *
     * @see https://redis.io/docs/latest/commands/ltrim/
     *
     * @example $redis->ltrim('mylist', 0, 3);  // Keep the first four elements
     */
    public function ltrim(string $key, int $start , int $end): Redis|bool;

    /**
     * Get one or more string keys.
     *
     * @param array $keys The keys to retrieve
     * @return Redis|array|false an array of keys with their values.
     *
     * @see https://redis.io/docs/latest/commands/mget/
     *
     * @example $redis->mget(['key1', 'key2']);
     */
    public function mget(array $keys): Redis|array|false;

    /**
     * Proxy for the Redis MIGRATE command.
     *
     * @param string          $host        The destination redis host.
     * @param int             $port        The destination redis port.
     * @param string|array    $key         The key or array of keys to migrate.
     * @param int             $dstdb       The destination database index.
     * @param int             $timeout     The timeout for the operation in
     *                                     milliseconds.
     * @param bool            $copy        Whether to copy the key(s) or move
     *                                     them.
     * @param bool            $replace     Whether to replace existing keys on
     *                                     the destination.
     * @param mixed|null     $credentials  Optional credentials for
     *                                     authenticating to the destination
     *                                     server.
     *
     * @see https://redis.io/docs/latest/commands/migrate/
     *
     * @example
     * $redis->connect('localhost', 6379);
     * $redis->set('foo', '6379_key');
     *
     * // Move the key to localhost:9999 with a 5 second timeout
     * var_dump($redis->migrate('localhost', 9999, 'foo', 0, 5000));
     */
    public function migrate(string $host, int $port, string|array $key, int $dstdb, int $timeout,
                            bool $copy = false, bool $replace = false,
                            #[\SensitiveParameter] mixed $credentials = null): Redis|bool;

    /**
     * Move a key to a different database on the same redis instance.
     *
     * @param string $key The key to move
     * @return Redis|bool True if the key was moved
     *
     * @see https://redis.io/docs/latest/commands/move/
     *
     * @example
     * $redis->move('cart:42', 1);
     *
     */
    public function move(string $key, int $index): Redis|bool;

    /**
     * Set one or more string keys.
     *
     * @param array $key_values An array with keys and their values.
     * @return Redis|bool True if the keys could be set.
     *
     * @see https://redis.io/docs/latest/commands/mset/
     *
     * @example $redis->mSet(['foo' => 'bar', 'baz' => 'bop']);
     */
    public function mset(array $key_values): Redis|bool;

    /**
     * Set one or more keys and values with optional expiry information.
     *
     * @param array      $key_vals An array of keys with their values.
     * @param int|float|array|null $expiry   An optional array with expiry information.
     * @return Redis|int|false 1 if all keys were set, 0 if none were.
     *
     * @see https://redis.io/commands/msetex
     *
     * @example $redis->msetex(['foo' => 'bar', 'baz' => 'bop'], ['EX' => 60]);
     */
    public function msetex(array $key_vals, int|float|array|null $expiry = null): Redis|int|false;

    /**
     * Set one or more string keys but only if none of the key exist.
     *
     * @param array $key_values An array of keys with their values.
     *
     * @return Redis|bool True if the keys were set and false if not.
     *
     * @see https://redis.io/docs/latest/commands/msetnx/
     *
     * @example $redis->msetnx(['foo' => 'bar', 'baz' => 'bop']);
     */
    public function msetnx(array $key_values): Redis|bool;

    /**
     * Begin a transaction.
     *
     * @param int $value  The type of transaction to start.  This can either be `Redis::MULTI` or
     *                    `Redis::PIPELINE'.
     *
     * @return Redis|bool True if the transaction could be started.
     *
     * @see https://redis.io/docs/latest/commands/multi/
     *
     * @example
     * $redis->multi();
     * $redis->set('foo', 'bar');
     * $redis->get('foo');
     * $redis->exec();
     */
    public function multi(int $value = Redis::MULTI): bool|Redis;

    /**
     * Get encoding and other information about a key.
     *
     * @param string $subcommand The subcommand to execute.  This can be either 'encoding', 'freq', or 'idle'.
     * @param string $key        The key to query.
     *
     * @return Redis|int|string|false The requested information about the key.
     *
     * @example
     * $redis->del('list1');
     * $redis->rPush('list1', 'a', 'b', 'c');
     * echo $redis->object('encoding', 'list1');
     *
     */
    public function object(string $subcommand, string $key): Redis|int|string|false;

    /**
     * @deprecated
     * @alias Redis::connect
     *
     * @example
     * $redis->open('127.0.0.1', 6379);
     *
     */
    public function open(string $host, int $port = 6379, float $timeout = 0, ?string $persistent_id = null, int $retry_interval = 0, float $read_timeout = 0, ?array $context = null): bool;

    /**
     * Connects to a Redis server creating or reusing a persistent connection.
     *
     * @param string      $host          The Redis server hostname.
     * @param int         $port          The Redis server port.
     * @param float       $timeout       Connection timeout in seconds.
     * @param string|null $persistent_id An optional persistent ID to use for the connection.
     * @param int         $retry_interval The number of microseconds to wait before retrying a connection.
     * @param float       $read_timeout  Read timeout in seconds.
     * @param array|null  $context       An optional stream context array.
     *
     * @return bool True if the connection was successful.
     *
     * @throws RedisException
     *
     * @example
     * try {
     *     $redis = new Redis();
     *     $redis->pconnect('localhost', 6379);
     * } catch (Exception $ex) {
     *    echo "Could not connect to Redis: ", $ex->getMessage(), "\n";
     * }
     */
    public function pconnect(string $host, int $port = 6379, float $timeout = 0, ?string $persistent_id = null, int $retry_interval = 0, float $read_timeout = 0, ?array $context = null): bool;

    /**
     * Remove the expiration from a key.
     *
     * @param string $key The key to operate against.
     *
     * @return Redis|bool True if a timeout was removed and false if it was not or the key didn't exist.
     *
     * @see https://redis.io/docs/latest/commands/persist/
     *
     * @example
     * $redis->persist('session:42');
     *
     */
    public function persist(string $key): Redis|bool;

    /**
     * Sets an expiration in milliseconds on a given key.  If connected to Redis >= 7.0.0
     * you can pass an optional mode argument that modifies how the command will execute.
     *
     * @see Redis::expire() for a description of the mode argument.
     * @see https://redis.io/docs/latest/commands/pexpire/
     *
     * @param string      $key     The key to set an expiration on.
     * @param int         $timeout The number of milliseconds after which key will be automatically deleted.
     * @param string|null $mode    A two character modifier that changes how the
     *                             command works.
     *
     * @return bool True if an expiry was set on the key, and false otherwise.
     *
     * @example
     * $redis->pexpire('session:42', 5000);
     *
     */
    public function pexpire(string $key, int $timeout, ?string $mode = null): bool;

    /**
     * Set a key's expiration to a specific Unix Timestamp in milliseconds.  If connected to
     * Redis >= 7.0.0 you can pass an optional 'mode' argument.
     *
     * @see Redis::expire() For a description of the mode argument.
     * @see https://redis.io/docs/latest/commands/pexpireat/
     *
     *  @param string      $key  The key to set an expiration on.
     *  @param int         $timestamp The unix timestamp to expire at.
     *  @param string|null $mode A two character modifier that changes how the
     *                       command works.
     *
     *  @return Redis|bool   True if an expiration was set on the key, false otherwise.
     *
     * @example
     * $redis->pexpireAt('session:42', (int) (microtime(true) * 1000) + 60000);
     *
     */
    public function pexpireAt(string $key, int $timestamp, ?string $mode = null): Redis|bool;

    /**
     * Add one or more elements to a Redis HyperLogLog key
     *
     * @see https://redis.io/docs/latest/commands/pfadd/
     *
     * @param string $key      The key in question.
     *
     * @param array  $elements One or more elements to add.
     *
     * @return Redis|int Returns 1 if the set was altered, and zero if not.
     *
     * @example
     * $redis->pfadd('visitors', ['alice', 'bob']);
     *
     */
    public function pfadd(string $key, array $elements): Redis|int;

    /**
     * Retrieve the cardinality of a Redis HyperLogLog key.
     *
     * @see https://redis.io/docs/latest/commands/pfcount/
     *
     * @param array|string $key_or_keys Either one key or an array of keys
     *
     * @return Redis|int|false The estimated cardinality of the set.
     *
     * @example
     * $redis->pfcount(['visitors:today', 'visitors:yesterday']);
     *
     */
    public function pfcount(array|string $key_or_keys): Redis|int|false;

    /**
     * Merge one or more source HyperLogLog sets into a destination set.
     *
     * @see https://redis.io/docs/latest/commands/pfmerge/
     *
     * @param string $dst     The destination key.
     * @param array  $srckeys One or more source keys.
     *
     * @return Redis|bool Always returns true.
     *
     * @example
     * $redis->pfmerge('visitors:all', ['visitors:today', 'visitors:yesterday']);
     *
     */
    public function pfmerge(string $dst, array $srckeys): Redis|bool;

    /**
     * PING the redis server with an optional string argument.
     *
     * @see https://redis.io/docs/latest/commands/ping/
     *
     * @param string|null $message An optional string message that Redis will reply with, if passed.
     *
     * @return Redis|string|false If passed no message, this command will simply return `true`.
     *                            If a message is passed, it will return the message.
     *
     * @example $redis->ping();
     * @example $redis->ping('beep boop');
     */
    public function ping(?string $message = null): Redis|string|bool;

    /**
     * Enter into pipeline mode.
     *
     * Pipeline mode is the highest performance way to send many commands to Redis
     * as they are aggregated into one stream of commands and then all sent at once
     * when the user calls Redis::exec().
     *
     * NOTE:  That this is shorthand for Redis::multi(Redis::PIPELINE)
     *
     * @return bool|Redis The redis object is returned, to facilitate method chaining.
     *
     * @example
     * $redis->pipeline()
     *       ->set('foo', 'bar')
     *       ->del('mylist')
     *       ->rpush('mylist', 'a', 'b', 'c')
     *       ->exec();
     */
    public function pipeline(): bool|Redis;

    /**
     * @deprecated
     * @alias Redis::pconnect
     *
     * @example
     * $redis->popen('127.0.0.1', 6379, 0.0, 'cache');
     *
     */
    public function popen(string $host, int $port = 6379, float $timeout = 0, ?string $persistent_id = null, int $retry_interval = 0, float $read_timeout = 0, ?array $context = null): bool;

    /**
     * Set a key with an expiration time in milliseconds
     *
     * @param string $key    The key to set
     * @param int    $expire The TTL to set, in milliseconds.
     * @param mixed  $value  The value to set the key to.
     *
     * @return Redis|bool True if the key could be set.
     *
     * @see https://redis.io/docs/latest/commands/psetex/
     *
     * @example $redis->psetex('mykey', 1000, 'myval');
     */
    public function psetex(string $key, int $expire, mixed $value): Redis|bool;

    /**
     * Subscribe to one or more glob-style patterns
     *
     * @param array     $patterns One or more patterns to subscribe to.
     * @param callable  $cb       A callback with the following prototype:
     *
     *                            ```php
     *                            function ($redis, $channel, $message) { }
     *                            ```
     *
     * @see https://redis.io/docs/latest/commands/psubscribe/
     *
     * @return bool True if we were subscribed.
     *
     * @example
     * $redis->psubscribe(['user:*'], function (Redis $client, string $pattern, string $channel, string $message): void {
     *     printf('[%s] %s' . PHP_EOL, $channel, $message);
     * });
     *
     */
    public function psubscribe(array $patterns, callable $cb): bool;

    /**
     * Get a keys time to live in milliseconds.
     *
     * @param string $key The key to check.
     *
     * @return Redis|int|false The key's TTL or one of two special values if it has none.
     *                         <code>
     *                         -1 - The key has no TTL.
     *                         -2 - The key did not exist.
     *                         </code>
     *
     * @see https://redis.io/docs/latest/commands/pttl/
     *
     * @example $redis->pttl('ttl-key');
     */
    public function pttl(string $key): Redis|int|false;

    /**
     * Publish a message to a pubsub channel
     *
     * @see https://redis.io/docs/latest/commands/publish/
     *
     * @param string $channel The channel to publish to.
     * @param string $message The message itself.
     *
     * @return Redis|int|false The number of subscribed clients to the given channel.
     *
     * @example
     * $redis->publish('updates', 'build complete');
     *
     */
    public function publish(string $channel, string $message): Redis|int|false;

    /**
     * Interact with the Redis PubSub subsystem.
     *
     * @param string $command The PubSub command to execute.  This can be one of:
     * @param mixed  $arg     An optional argument to the command.
     *
     * @return mixed Can return any number of things depending on the command executed.
     * @see https://redis.io/docs/latest/commands/pubsub/
     *
     * @example
     * $redis->pubsub('channels');
     */
    public function pubsub(string $command, mixed $arg = null): mixed;

    /**
     * Unsubscribe from one or more channels by pattern
     *
     * @see https://redis.io/docs/latest/commands/punsubscribe/
     * @see https://redis.io/docs/latest/commands/subscribe/
     * @see Redis::subscribe()
     *
     * @param array $patterns One or more glob-style patterns of channel names.
     *
     * @return Redis|array|bool  The array of subscribed patterns or false on failure.
     *
     * @example
     * $redis->punsubscribe(['user:*', 'room:*']);
     *
     */
    public function punsubscribe(array $patterns): Redis|array|bool;

    /**
     * Pop one or more elements from the end of a list.
     *
     * @param string $key   A redis LIST key name.
     * @param int    $count The maximum number of elements to pop at once.
     *                      NOTE:  The `count` argument requires Redis >= 6.2.0
     *
     * @return Redis|array|string|bool One or more popped elements or false if all were empty.
     *
     * @see https://redis.io/docs/latest/commands/rpop/
     *
     * @example $redis->rPop('mylist');
     * @example $redis->rPop('mylist', 4);
     */
    public function rPop(string $key, int $count = 0): Redis|array|string|bool;

    /**
     * Return a random key from the current database
     *
     * @see https://redis.io/docs/latest/commands/randomkey/
     *
     * @return Redis|string|false A random key name or false if no keys exist
     *
     * @example
     * $redis->randomKey();
     *
     */
    public function randomKey(): Redis|string|false;

    /**
     * Execute any arbitrary Redis command by name.
     *
     * @param string $command The command to execute
     * @param mixed  ...$args One or more arguments to pass to the command.
     *
     * @return mixed Can return any number of things depending on command executed.
     *
     * @example $redis->rawCommand('del', 'mystring', 'mylist');
     * @example $redis->rawCommand('set', 'mystring', 'myvalue');
     * @example $redis->rawCommand('rpush', 'mylist', 'one', 'two', 'three');
     */
    public function rawcommand(string $command, mixed ...$args): mixed;

    /**
     * Unconditionally rename a key from $old_name to $new_name
     *
     * @see https://redis.io/docs/latest/commands/rename/
     *
     * @param string $old_name The original name of the key
     * @param string $new_name The new name for the key
     *
     * @return Redis|bool True if the key was renamed or false if not.
     *
     * @example
     * $redis->rename('config:pending', 'config:active');
     *
     */
    public function rename(string $old_name, string $new_name): Redis|bool;

    /**
     * Renames $key_src to $key_dst but only if newkey does not exist.
     *
     * @see https://redis.io/docs/latest/commands/renamenx/
     *
     * @param string $key_src The source key name
     * @param string $key_dst The destination key name.
     *
     * @return Redis|bool True if the key was renamed, false if not.
     *
     * @example
     * $redis->set('src', 'src_key');
     * $redis->set('existing-dst', 'i_exist');
     *
     * $redis->renamenx('src', 'dst');
     * $redis->renamenx('dst', 'existing-dst');
     */
    public function renameNx(string $key_src, string $key_dst): Redis|bool;

    /**
     * Reset the state of the connection.
     *
     * @return Redis|bool Should always return true unless there is an error.
     *
     * @see https://redis.io/docs/latest/commands/reset/
     *
     * @example
     * $redis->reset();
     *
     */
    public function reset(): Redis|bool;

    /**
     * Restore a key by the binary payload generated by the DUMP command.
     *
     * @param string     $key     The name of the key you wish to create.
     * @param int        $ttl     What Redis should set the key's TTL (in milliseconds) to once it is created.
     *                            Zero means no TTL at all.
     * @param string     $value   The serialized binary value of the string (generated by DUMP).
     * @param array|null $options An array of additional options that modifies how the command operates.
     *
     *                            ```php
     *                            $options = [
     *                                'ABSTTL'          # If this is present, the `$ttl` provided by the user should
     *                                                  # be an absolute timestamp, in milliseconds()
     *
     *                                'REPLACE'         # This flag instructs Redis to store the key even if a key with
     *                                                  # that name already exists.
     *
     *                                'IDLETIME' => int # Tells Redis to set the keys internal 'idletime' value to a
     *                                                  # specific number (see the Redis command OBJECT for more info).
     *                                'FREQ'     => int # Tells Redis to set the keys internal 'FREQ' value to a specific
     *                                                  # number (this relates to Redis' LFU eviction algorithm).
     *                            ];
     *                            ```
     *
     * @return Redis|bool     True if the key was stored, false if not.
     *
     * @see https://redis.io/docs/latest/commands/restore/
     * @see https://redis.io/docs/latest/commands/dump/
     * @see Redis::dump()
     *
     * @example
     * $redis->sAdd('captains', 'Janeway', 'Picard', 'Sisko', 'Kirk', 'Archer');
     * $serialized = $redis->dump('captains');
     *
     * $redis->restore('captains-backup', 0, $serialized);
     */
    public function restore(string $key, int $ttl, string $value, ?array $options = null): Redis|bool;

    /**
     * Query whether the connected instance is a primary or replica
     *
     * @return mixed Will return an array with the role of the connected instance unless there is
     *               an error.
     *
     * @see https://redis.io/docs/latest/commands/role/
     *
     * @example
     * $redis->role();
     *
     */
    public function role(): mixed;

    /**
     * Atomically pop an element off the end of a Redis LIST and push it to the beginning of
     * another.
     *
     * @param string $srckey The source key to pop from.
     * @param string $dstkey The destination key to push to.
     *
     * @return Redis|string|false The popped element or false if the source key was empty.
     *
     * @see https://redis.io/docs/latest/commands/rpoplpush/
     *
     * @example
     * $redis->pipeline()
     *       ->del('list1', 'list2')
     *       ->rpush('list1', 'list1-1', 'list1-2')
     *       ->rpush('list2', 'list2-1', 'list2-2')
     *       ->exec();
     *
     * $redis->rpoplpush('list2', 'list1');
     */
    public function rpoplpush(string $srckey, string $dstkey): Redis|string|false;

    /**
     * Add one or more values to a Redis SET key.
     *
     * @param string $key            The key name
     * @param mixed  $value          A value to add to the set.
     * @param mixed ...$other_values One or more additional values to add
     *
     * @return Redis|int|false The number of values added to the set.
     *
     * @see https://redis.io/docs/latest/commands/sadd/
     *
     * @example
     * $redis->del('myset');
     *
     * $redis->sadd('myset', 'foo', 'bar', 'baz');
     * $redis->sadd('myset', 'foo', 'new');
     */
    public function sAdd(string $key, mixed $value, mixed ...$other_values): Redis|int|false;

    /**
     * Add one or more values to a Redis SET key.  This is an alternative to Redis::sadd() but
     * instead of being variadic, takes a single array of values.
     *
     * @see https://redis.io/docs/latest/commands/sadd/
     * @see Redis::sadd()
     *
     * @param string $key    The set to add values to.
     * @param array  $values One or more members to add to the set.
     *
     * @return int The number of members added to the set.
     *
     * @example
     * $redis->del('myset');
     *
     * $redis->sAddArray('myset', ['foo', 'bar', 'baz']);
     * $redis->sAddArray('myset', ['foo', 'new']);
     */
    public function sAddArray(string $key, array $values): int;

    /**
     * Given one or more Redis SETS, this command returns all of the members from the first
     * set that are not in any subsequent set.
     *
     * @param string $key           The first set
     * @param string ...$other_keys One or more additional sets
     *
     * @return Redis|array|false Returns the elements from keys 2..N that don't exist in the
     *                           first sorted set, or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/sdiff/
     *
     * @example
     * $redis->pipeline()
     *       ->del('set1', 'set2', 'set3')
     *       ->sadd('set1', 'apple', 'banana', 'carrot', 'date')
     *       ->sadd('set2', 'carrot')
     *       ->sadd('set3', 'apple', 'carrot', 'eggplant')
     *       ->exec();
     *
     * $redis->sdiff('set1', 'set2', 'set3');
     */
    public function sDiff(string $key, string ...$other_keys): Redis|array|false;

    /**
     * This method performs the same operation as SDIFF except it stores the resulting diff
     * values in a specified destination key.
     *
     * @see https://redis.io/docs/latest/commands/sdiffstore/
     * @see Redis::sdiff()
     *
     * @param string $dst           The key where to store the result
     * @param string $key           The first key to perform the DIFF on
     * @param string ...$other_keys One or more additional keys.
     *
     * @return Redis|int|false The number of values stored in the destination set or false on failure.
     *
     * @example
     * $redis->sDiffStore('diff:set', 'set:all', 'set:archived');
     *
     */
    public function sDiffStore(string $dst, string $key, string ...$other_keys): Redis|int|false;

    /**
     * Given one or more Redis SET keys, this command will return all of the elements that are
     * in every one.
     *
     * @see https://redis.io/docs/latest/commands/sinter/
     *
     * @param array|string $key     The first SET key to intersect.
     * @param string ...$other_keys One or more Redis SET keys.
     *
     * @example
     * $redis->pipeline()
     *       ->del('alice_likes', 'bob_likes', 'bill_likes')
     *       ->sadd('alice_likes', 'asparagus', 'broccoli', 'carrot', 'potato')
     *       ->sadd('bob_likes', 'asparagus', 'carrot', 'potato')
     *       ->sadd('bill_likes', 'broccoli', 'potato')
     *       ->exec();
     *
     * var_dump($redis->sinter('alice_likes', 'bob_likes', 'bill_likes'));
     */
    public function sInter(array|string $key, string ...$other_keys): Redis|array|false;

    /**
     * Compute the intersection of one or more sets and return the cardinality of the result.
     *
     * @param array $keys  One or more set key names.
     * @param int   $limit A maximum cardinality to return.  This is useful to put an upper bound
     *                     on the amount of work Redis will do.
     *
     * @return Redis|int|false The
     *
     * @see https://redis.io/docs/latest/commands/sintercard/
     *
     * @example
     * $redis->sAdd('set1', 'apple', 'pear', 'banana', 'carrot');
     * $redis->sAdd('set2', 'apple',         'banana');
     * $redis->sAdd('set3',          'pear', 'banana');
     *
     * $redis->sInterCard(['set1', 'set2', 'set3']);
     */
    public function sintercard(array $keys, int $limit = -1): Redis|int|false;

    /**
     * Compute the union of one or more sets and return the cardinality of the result.
     *
     * @param array      $keys    One or more set key names.
     * @param array|null $options An optional array of options that modifies how the command works.
     *
     * @return Redis|int|false The cardinality of the union of the sets.
     *
     * @see https://redis.io/docs/latest/commands/sunioncard/
     *
     * @example
     * $redis->sAdd('set1', 'apple', 'pear', 'banana', 'carrot');
     * $redis->sAdd('set2', 'apple',         'banana');
     * $redis->sAdd('set3',          'pear', 'banana');
     *
     * $redis->sUnionCard(['set1', 'set2', 'set3']);
     * $redis->sUnionCard(['set1', 'set2'], ['LIMIT' => 2]);
     * $redis->sUnionCard(['set1', 'set2'], ['LIMIT' => 2, 'APPROX']);
     */
    public function sunioncard(array $keys, ?array $options = null): Redis|int|false;

    /**
     * Compute the difference of one or more sets and return the cardinality of the result.o
     *
     * @param array      $keys    One or more set key names.
     * @param array|null $options An optional array of options that modifies how the command works
     *
     * @return Redis|int|false The cardinality of the difference of the sets.
     *
     * @see https://redis.io/docs/latest/commands/sdiffcard/
     *
     * @example
     * $redis->sAdd('set1', 'apple', 'pear', 'banana
     * $redis->sAdd('set2', 'apple',         'banana');
     * $redis->sAdd('set3',          'pear', 'banana');
     *
     */
    public function sdiffcard(array $keys, ?array $options = null): Redis|int|false;

    /**
     * Perform the intersection of one or more Redis SETs, storing the result in a destination
     * key, rather than returning them.
     *
     * @param array|string $key     Either a string key, or an array of keys (with at least two
     *                              elements, consisting of the destination key name and one
     *                              or more source keys names.
     * @param string ...$other_keys If the first argument was a string, subsequent arguments should
     *                              be source key names.
     *
     * @return Redis|int|false The number of values stored in the destination key or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/sinterstore/
     * @see Redis::sinter()
     *
     * @example
     * $redis->sInterStore(['dst', 'src1', 'src2', 'src3']);
     * $redis->sInterStore('dst', 'src1', 'src'2', 'src3');
     */
    public function sInterStore(array|string $key, string ...$other_keys): Redis|int|false;

    /**
     * Retrieve every member from a set key.
     *
     * @param string $key The set name.
     *
     * @return Redis|array|false Every element in the set or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/smembers/
     *
     * @example
     * $redis->sAdd('tng-crew', ...['Picard', 'Riker', 'Data', 'Worf', 'La Forge', 'Troi', 'Crusher', 'Broccoli']);
     * $redis->sMembers('tng-crew');
     */
    public function sMembers(string $key): Redis|array|false;

    /**
     * Check if one or more values are members of a set.
     *
     * @see https://redis.io/docs/latest/commands/smismember/
     * @see https://redis.io/docs/latest/commands/smember/
     * @see Redis::smember()
     *
     * @param string $key              The set to query.
     * @param string $member           The first value to test if exists in the set.
     * @param string ...$other_members Any number of additional values to check.
     *
     * @return Redis|array|false An array of integers representing whether each passed value
     *                           was a member of the set.
     *
     * @example
     * $redis->sAdd('ds9-crew', ...["Sisko", "Kira", "Dax", "Worf", "Bashir", "O'Brien"]);
     * $members = $redis->sMIsMember('ds9-crew', ...['Sisko', 'Picard', 'Data', 'Worf']);
     */
    public function sMisMember(string $key, string $member, string ...$other_members): Redis|array|false;

    /**
     * Pop a member from one set and push it onto another.  This command will create the
     * destination set if it does not currently exist.
     *
     * @see https://redis.io/docs/latest/commands/smove/
     *
     * @param string $src   The source set.
     * @param string $dst   The destination set.
     * @param mixed  $value The member you wish to move.
     *
     * @return Redis|bool   True if the member was moved, and false if it wasn't in the set.
     *
     * @example
     * $redis->sAdd('numbers', 'zero', 'one', 'two', 'three', 'four');
     * $redis->sMove('numbers', 'evens', 'zero');
     * $redis->sMove('numbers', 'evens', 'two');
     * $redis->sMove('numbers', 'evens', 'four');
     */
    public function sMove(string $src, string $dst, mixed $value): Redis|bool;

    /**
     * Remove one or more elements from a set.
     *
     * @see https://redis.io/docs/latest/commands/spop/
     *
     * @param string $key    The set in question.
     * @param int    $count  An optional number of members to pop.   This defaults to
     *                       removing one element.
     *
     * @example
     * $redis->del('numbers', 'evens');
     * $redis->sAdd('numbers', 'zero', 'one', 'two', 'three', 'four');
     * $redis->sPop('numbers');
     */
    public function sPop(string $key, int $count = 0): Redis|string|array|false;

    /**
     * Retrieve one or more random members of a set.
     *
     * @param string $key   The set to query.
     * @param int    $count An optional count of members to return.
     *
     *                      If this value is positive, Redis will return *up to* the requested
     *                      number but with unique elements that will never repeat.  This means
     *                      you may receive fewer then `$count` replies.
     *
     *                      If the number is negative, Redis will return the exact number requested
     *                      but the result may contain duplicate elements.
     *
     * @return Redis|array|string|false One or more random members or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/srandmember/
     *
     * @example $redis->sRandMember('myset');
     * @example $redis->sRandMember('myset', 10);
     * @example $redis->sRandMember('myset', -10);
     */
    public function sRandMember(string $key, int $count = 0): mixed;

    /**
     * Returns the union of one or more Redis SET keys.
     *
     * @see https://redis.io/docs/latest/commands/sunion/
     *
     * @param string $key           The first SET to do a union with
     * @param string ...$other_keys One or more subsequent keys
     *
     * @return Redis|array|false  The union of the one or more input sets or false on failure.
     *
     * @example $redis->sunion('set1', 'set2');
     */
    public function sUnion(string $key, string ...$other_keys): Redis|array|false;

    /**
     * Perform a union of one or more Redis SET keys and store the result in a new set
     *
     * @see https://redis.io/docs/latest/commands/sunionstore/
     * @see Redis::sunion()
     *
     * @param string $dst           The destination key
     * @param string $key           The first source key
     * @param string ...$other_keys One or more additional source keys
     *
     * @return Redis|int|false   The number of elements stored in the destination SET or
     *                           false on failure.
     *
     * @example
     * $redis->sUnionStore('union:set', 'set:a', 'set:b');
     *
     */
    public function sUnionStore(string $dst, string $key, string ...$other_keys): Redis|int|false;

    /**
     * Persist the Redis database to disk.  This command will block the server until the save is
     * completed.  For a nonblocking alternative, see Redis::bgsave().
     *
     * @see https://redis.io/docs/latest/commands/save/
     * @see Redis::bgsave()
     *
     * @return Redis|bool Returns true unless an error occurs.
     *
     * @example
     * $redis->save();
     *
     */
    public function save(): Redis|bool;

    /**
     * Incrementally scan the Redis keyspace, with optional pattern and type matching.
     *
     * A note about Redis::SCAN_NORETRY and Redis::SCAN_RETRY.
     *
     * For convenience, PhpRedis can retry SCAN commands itself when Redis returns an empty array of
     * keys with a nonzero iterator.  This can happen when matching against a pattern that very few
     * keys match inside a key space with a great many keys.  The following example demonstrates how
     * to use Redis::scan() with the option disabled and enabled.
     *
     * @param int|string|null $iterator The cursor returned by Redis for every subsequent call to SCAN.  On
     *                                  the initial invocation of the call, it should be initialized by the
     *                                  caller to NULL.  Each time SCAN is invoked, the iterator will be
     *                                  updated to a new number, until finally Redis will set the value to
     *                                  zero, indicating that the scan is complete.
     * @param string|null     $pattern  An optional glob-style pattern for matching key names.  If passed as
     *                                  NULL, it is the equivalent of sending '*' (match every key).
     * @param int             $count    A hint to redis that tells it how many keys to return in a single
     *                                  call to SCAN.  The larger the number, the longer Redis may block
     *                                  clients while iterating the key space.
     * @param string|null     $type     An optional argument to specify which key types to scan (e.g.
     *                                  'STRING', 'LIST', 'SET')
     *
     * @return array|false     An array of keys, or false if no keys were returned for this
     *                         invocation of scan.  Note that it is possible for Redis to return
     *                         zero keys before having scanned the entire key space, so the caller
     *                         should instead continue to SCAN until the iterator reference is
     *                         returned to zero.
     *
     * @see https://redis.io/docs/latest/commands/scan/
     * @see Redis::setOption()
     *
     * @example
     * $redis = new Redis(['host' => 'localhost']);
     *
     * $redis->setOption(Redis::OPT_SCAN, Redis::SCAN_NORETRY);
     *
     * $it = null;
     *
     * do {
     *     $keys = $redis->scan($it, '*zorg*');
     *     foreach ($keys as $key) {
     *         echo "KEY: $key\n";
     *     }
     * } while ($it != 0);
     *
     * $redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
     *
     * $it = null;
     *
     * // When Redis::SCAN_RETRY is enabled, we can use simpler logic, as we will never receive an
     * // empty array of keys when the iterator is nonzero.
     * while ($keys = $redis->scan($it, '*zorg*')) {
     *     foreach ($keys as $key) {
     *         echo "KEY: $key\n";
     *     }
     * }
     */
    public function scan(null|int|string &$iterator, ?string $pattern = null, int $count = 0, ?string $type = null): array|false;

    /**
     * Retrieve the number of members in a Redis set.
     *
     * @param string $key The set to get the cardinality of.
     *
     * @return Redis|int|false The cardinality of the set or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/scard/
     *
     * @example $redis->scard('set');
     */
    public function scard(string $key): Redis|int|false;

    /**
     * An administrative command used to interact with LUA scripts stored on the server.
     *
     * @see https://redis.io/docs/latest/commands/script/
     *
     * @param string $command The script suboperation to execute.
     * @param mixed  ...$args One or more additional argument
     *
     * @return mixed This command returns various things depending on the specific operation executed.
     *
     * @example $redis->script('load', 'return 1');
     * @example $redis->script('exists', sha1('return 1'));
     */
    public function script(string $command, mixed ...$args): mixed;

    /**
     * Select a specific Redis database.
     *
     * @param int $db The database to select.  Note that by default Redis has 16 databases (0-15).
     *
     * @return Redis|bool true on success and false on failure
     *
     * @see https://redis.io/docs/latest/commands/select/
     *
     * @example $redis->select(1);
     */
    public function select(int $db): Redis|bool;

    /**
     * Create or set a Redis STRING key to a value.
     *
     * @param string    $key     The key name to set.
     * @param mixed     $value   The value to set the key to.
     * @param array|int $options Either an array with options for how to perform the set or an
     *                           integer with an expiration.  If an expiration is set PhpRedis
     *                           will actually send the `SETEX` command.
     *
     * OPTION                         DESCRIPTION
     * ------------                   --------------------------------------------------------------
     * ['EX' => 60]                   expire 60 seconds.
     * ['PX' => 6000]                 expire in 6000 milliseconds.
     * ['EXAT' => time() + 10]        expire in 10 seconds.
     * ['PXAT' => time()*1000 + 1000] expire in 1 second.
     * ['KEEPTTL' => true]            Redis will not update the key's current TTL.
     * ['XX']                         Only set the key if it already exists.
     * ['NX']                         Only set the key if it doesn't exist.
     * ['GET']                        Instead of returning `+OK` return the previous value of the
     *                                key or NULL if the key didn't exist.
     *
     * @return Redis|string|bool True if the key was set or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/set/
     * @see https://redis.io/docs/latest/commands/setex/
     *
     * @example $redis->set('key', 'value');
     * @example $redis->set('key', 'expires_in_60_seconds', 60);
     */
    public function set(string $key, mixed $value, mixed $options = null): Redis|string|bool;

    /**
     * Set a specific bit in a Redis string to zero or one
     *
     * @see https://redis.io/docs/latest/commands/setbit/
     *
     * @param string $key    The Redis STRING key to modify
     * @param bool   $value  Whether to set the bit to zero or one.
     *
     * @return Redis|int|false The original value of the bit or false on failure.
     *
     * @example
     * $redis->set('foo', 'bar');
     * $redis->setbit('foo', 7, 1);
     */
    public function setBit(string $key, int $idx, bool $value): Redis|int|false;

    /**
     * Update or append to a Redis string at a specific starting index
     *
     * @see https://redis.io/docs/latest/commands/setrange/
     *
     * @param string $key    The key to update
     * @param int    $index  Where to insert the provided value
     * @param string $value  The value to copy into the string.
     *
     * @return Redis|int|false The new length of the string or false on failure
     *
     * @example
     * $redis->set('message', 'Hello World');
     * $redis->setRange('message', 6, 'Redis');
     */
    public function setRange(string $key, int $index, string $value): Redis|int|false;

    /**
     * Set a configurable option on the Redis object.
     *
     * Following are a list of options you can set:
     *
     * | OPTION          | TYPE | DESCRIPTION |
     * | --------------- | ---- | ----------- |
     * | OPT_MAX_RETRIES | int  | The maximum number of times Redis will attempt to reconnect if it gets disconnected, before throwing an exception. |
     * | OPT_SCAN        | enum | Redis::OPT_SCAN_RETRY, or Redis::OPT_SCAN_NORETRY.  Whether PhpRedis should automatically SCAN again when zero keys but a nonzero iterator are returned. |
     * | OPT_SERIALIZER  | enum | Set the automatic data serializer.<br>`Redis::SERIALIZER_NONE`<br>`Redis::SERIALIZER_PHP`<br>`Redis::SERIALIZER_IGBINARY`<br>`Redis::SERIALIZER_MSGPACK`, `Redis::SERIALIZER_JSON`|
     * | OPT_PREFIX | string | A string PhpRedis will use to prefix every key we read or write. |
     * | OPT_READ_TIMEOUT | float | How long PhpRedis will block for a response from Redis before throwing a 'read error on connection' exception. |
     * | OPT_TCP_KEEPALIVE | bool |   Set or disable TCP_KEEPALIVE on the connection. |
     * | OPT_COMPRESSION | enum | Set the compression algorithm<br>`Redis::COMPRESSION_NONE`<br>`Redis::COMPRESSION_LZF`<br>`Redis::COMPRESSION_LZ4`<br> `Redis::COMPRESSION_ZSTD` |
     * | OPT_REPLY_LITERAL | bool | If set to true, PhpRedis will return the literal string Redis returns for LINE replies (e.g. '+OK'), rather than `true`. |
     * | OPT_COMPRESSION_LEVEL | int | Set a specific compression level if Redis is compressing data. |
     * | OPT_NULL_MULTIBULK_AS_NULL | bool | Causes PhpRedis to return `NULL` rather than `false` for NULL MULTIBULK replies |
     * | OPT_BACKOFF_ALGORITHM | enum | The exponential backoff strategy to use. |
     * | OPT_BACKOFF_BASE | int | The minimum delay between retries when backing off. |
     * | OPT_BACKOFF_CAP  | int | The maximum delay between replies when backing off. |
     *
     * @see Redis::getOption()
     * @see Redis::__construct() for details about backoff strategies.
     *
     * @param int    $option The option constant.
     * @param mixed  $value  The option value.
     *
     * @return bool  true if the setting was updated, false if not.
     *
     * @example
     * $redis->setOption(Redis::OPT_PREFIX, 'app:');
     *
     */
    public function setOption(int $option, mixed $value): bool;

    /**
     * Set a Redis STRING key with a specific expiration in seconds.
     *
     * @param string $key     The name of the key to set.
     * @param int    $expire  The key's expiration in seconds.
     * @param mixed  $value   The value to set the key.
     *
     * @return Redis|bool True on success or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/setex/
     *
     * @example $redis->setex('60s-ttl', 60, 'some-value');
     */
    public function setex(string $key, int $expire, mixed $value);

    /**
     * Set a key to a value, but only if that key does not already exist.
     *
     * @see https://redis.io/docs/latest/commands/setnx/
     *
     * @param string $key   The key name to set.
     * @param mixed  $value What to set the key to.
     *
     * @return Redis|bool Returns true if the key was set and false otherwise.
     *
     * @example $redis->setnx('existing-key', 'existing-value');
     * @example $redis->setnx('new-key', 'new-value');
     */
    public function setnx(string $key, mixed $value): Redis|bool;

    /**
     * Check whether a given value is the member of a Redis SET.
     *
     * @param string $key   The redis set to check.
     * @param mixed  $value The value to test.
     *
     * @return Redis|bool True if the member exists and false if not.
     *
     * @see https://redis.io/docs/latest/commands/sismember/
     *
     * @example $redis->sismember('myset', 'mem1', 'mem2');
     */
    public function sismember(string $key, mixed $value): Redis|bool;

    /**
     * Turn a redis instance into a replica of another or promote a replica
     * to a primary.
     *
     * This method and the corresponding command in Redis has been marked deprecated
     * and users should instead use Redis::replicaof() if connecting to redis-server
     * >= 5.0.0.
     *
     * @deprecated
     *
     * @see https://redis.io/docs/latest/commands/slaveof/
     * @see https://redis.io/docs/latest/commands/replicaof/
     * @see Redis::replicaof()
     *
     * @example
     * $redis->slaveof('10.0.0.5', 6380);
     *
     */
    public function slaveof(?string $host = null, int $port = 6379): Redis|bool;

    /**
     * Used to turn a Redis instance into a replica of another, or to remove
     * replica status promoting the instance to a primary.
     *
     * @see https://redis.io/docs/latest/commands/replicaof/
     * @see https://redis.io/docs/latest/commands/slaveof/
     * @see Redis::slaveof()
     *
     * @param string|null $host The host of the primary to start replicating.
     * @param int         $port The port of the primary to start replicating.
     *
     * @return Redis|bool Success if we were successfully able to start replicating a primary or
     *                    were able to promote the replicat to a primary.
     *
     * @example
     * $redis = new Redis(['host' => 'localhost']);
     *
     * // Attempt to become a replica of a Redis instance at 127.0.0.1:9999
     * $redis->replicaof('127.0.0.1', 9999);
     *
     * // When passed no arguments, PhpRedis will deliver the command `REPLICAOF NO ONE`
     * // attempting to promote the instance to a primary.
     * $redis->replicaof();
     */
    public function replicaof(?string $host = null, int $port = 6379): Redis|bool;

    /**
     * Update one or more keys last modified metadata.
     *
     * @see https://redis.io/docs/latest/commands/touch/
     *
     * @param array|string $key_or_array Either the first key or if passed as the only argument
     *                                   an array of keys.
     * @param string ...$more_keys       One or more keys to send to the command.
     *
     * @return Redis|int|false     This command returns the number of keys that exist and
     *                             had their last modified time reset
     *
     * @example
     * $redis->touch('cache:1', 'cache:2');
     *
     */
    public function touch(array|string $key_or_array, string ...$more_keys): Redis|int|false;

    /**
     * Interact with Redis' slowlog functionality in various ways, depending
     * on the value of 'operation'.
     *
     * @category administration
     *
     * @param string $operation  The operation you wish to perform.  This can
     *                           be one of the following values:
     *                           'GET'   - Retrieve the Redis slowlog as an array.
     *                           'LEN'   - Retrieve the length of the slowlog.
     *                           'RESET' - Remove all slowlog entries.
     * @param int    $length     This optional argument can be passed when operation
     *                           is 'get' and will specify how many elements to retrieve.
     *                           If omitted Redis will send up to a default number of
     *                           entries, which is configurable.
     *
     *                           Note:  With Redis >= 7.0.0 you can send -1 to mean "all".
     *
     * @return mixed
     *
     * @see https://redis.io/docs/latest/commands/slowlog/
     *
     * @example $redis->slowlog('get', -1);   // Retrieve all slowlog entries.
     * @example $redis->slowlog('len');       // Retrieve slowlog length.
     * @example $redis->slowlog('reset');     // Reset the slowlog.
     */
    public function slowlog(string $operation, int $length = 0): mixed;

    /**
     * Sort the contents of a Redis key in various ways.
     *
     * @see https://redis.io/docs/latest/commands/sort/
     *
     * @param string     $key     The key you wish to sort
     * @param array|null $options Various options controlling how you would like the
     *                            data sorted.  See blow for a detailed description
     *                            of this options array.
     *
     * @return mixed This command can either return an array with the sorted data
     *               or the number of elements placed in a destination set when
     *               using the STORE option.
     *
     * @example
     * $options = [
     *     'SORT'  => 'ASC'|| 'DESC' // Sort in descending or descending order.
     *     'ALPHA' => true || false  // Whether to sort alphanumerically.
     *     'LIMIT' => [0, 10]        // Return a subset of the data at offset, count
     *     'BY'    => 'weight_*'     // For each element in the key, read data from the
     *                                  external key weight_* and sort based on that value.
     *     'GET'   => 'weight_*'     // For each element in the source key, retrieve the
     *                                  data from key weight_* and return that in the result
     *                                  rather than the source keys' element.  This can
     *                                  be used in combination with 'BY'
     * ];
     */
    public function sort(string $key, ?array $options = null): mixed;

    /**
     * This is simply a read-only variant of the sort command
     *
     * @see Redis::sort()
     * @see https://redis.io/docs/latest/commands/sort_ro/
     *
     * @example
     * $redis->sort_ro('numbers', ['LIMIT' => [0, 5]]);
     *
     */
    public function sort_ro(string $key, ?array $options = null): mixed;

    /**
     * @deprecated
     *
     * @see https://redis.io/docs/latest/commands/sort/
     *
     * @example
     * $redis->sortAsc('numbers');
     *
     */
    public function sortAsc(string $key, ?string $pattern = null, mixed $get = null, int $offset = -1, int $count = -1, ?string $store = null): array;

    /**
     * @deprecated
     *
     * @see https://redis.io/docs/latest/commands/sort/
     *
     * @example
     * $redis->sortAscAlpha('tags');
     *
     */
    public function sortAscAlpha(string $key, ?string $pattern = null, mixed $get = null, int $offset = -1, int $count = -1, ?string $store = null): array;

    /**
     * @deprecated
     *
     * @see https://redis.io/docs/latest/commands/sort/
     *
     * @example
     * $redis->sortDesc('numbers');
     *
     */
    public function sortDesc(string $key, ?string $pattern = null, mixed $get = null, int $offset = -1, int $count = -1, ?string $store = null): array;

    /**
     * @deprecated
     *
     * @see https://redis.io/docs/latest/commands/sort/
     *
     * @example
     * $redis->sortDescAlpha('tags');
     *
     */
    public function sortDescAlpha(string $key, ?string $pattern = null, mixed $get = null, int $offset = -1, int $count = -1, ?string $store = null): array;

    /**
     * Remove one or more values from a Redis SET key.
     *
     * @see https://redis.io/docs/latest/commands/srem/
     *
     * @param string $key            The Redis SET key in question.
     * @param mixed  $value          The first value to remove.
     * @param mixed ...$other_values One or more additional values to remove.
     *
     * @return Redis|int|false    The number of values removed from the set or false on failure.
     *
     * @example $redis->sRem('set1', 'mem1', 'mem2', 'not-in-set');
     */
    public function srem(string $key, mixed $value, mixed ...$other_values): Redis|int|false;

    /**
     * Scan the members of a redis SET key.
     *
     * @see https://redis.io/docs/latest/commands/sscan/
     * @see https://redis.io/docs/latest/commands/scan/
     * @see Redis::setOption()
     *
     * @param string          $key      The Redis SET key in question.
     * @param int|string|null $iterator A reference to an iterator which should be initialized to NULL that
     *                                  PhpRedis will update with the value returned from Redis after each
     *                                  subsequent call to SSCAN.  Once this cursor is zero you know all
     *                                  members have been traversed.
     * @param string|null     $pattern  An optional glob style pattern to match against, so Redis only
     *                                  returns the subset of members matching this pattern.
     * @param int             $count    A hint to Redis as to how many members it should scan in one command
     *                                  before returning members for that iteration.
     *
     * @example
     * $redis->del('myset');
     * for ($i = 0; $i < 10000; $i++) {
     *     $redis->sAdd('myset', "member:$i");
     * }
     * $redis->sadd('myset', 'foofoo');
     *
     * $redis->setOption(Redis::OPT_SCAN, Redis::SCAN_NORETRY);
     *
     * $scanned = 0;
     * $it = null;
     *
     * // Without Redis::SCAN_RETRY we may receive empty results and
     * // a nonzero iterator.
     * do {
     *     // Scan members containing '5'
     *     $members = $redis->sscan('myset', $it, '*5*');
     *     foreach ($members as $member) {
     *          echo "NORETRY: $member\n";
     *          $scanned++;
     *     }
     * } while ($it != 0);
     * echo "TOTAL: $scanned\n";
     *
     * $redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
     *
     * $scanned = 0;
     * $it = null;
     *
     * // With Redis::SCAN_RETRY PhpRedis will never return an empty array
     * // when the cursor is non-zero
     * while (($members = $redis->sscan('myset', $it, '*5*'))) {
     *     foreach ($members as $member) {
     *         echo "RETRY: $member\n";
     *         $scanned++;
     *     }
     * }
     */
    public function sscan(string $key, null|int|string &$iterator, ?string $pattern = null, int $count = 0): array|false;

    /**
     * Subscribes the client to the specified shard channels.
     *
     * @param array    $channels One or more channel names.
     * @param callable $cb       The callback PhpRedis will invoke when we receive a message
     *                           from one of the subscribed channels.
     *
     * @return bool True on success, false on faiilure.  Note that this command will block the
     *              client in a subscribe loop, waiting for messages to arrive.
     *
     * @see https://redis.io/docs/latest/commands/ssubscribe/
     *
     * @example
     * $redis = new Redis(['host' => 'localhost']);
     *
     * $redis->ssubscribe(['channel-1', 'channel-2'], function ($redis, $channel, $message) {
     *     echo "[$channel]: $message\n";
     *
     *     // Unsubscribe from the message channel when we read 'quit'
     *     if ($message == 'quit') {
     *         echo "Unsubscribing from '$channel'\n";
     *         $redis->sunsubscribe([$channel]);
     *     }
     * });
     *
     * // Once we read 'quit' from both channel-1 and channel-2 the subscribe loop will be
     * // broken and this command will execute.
     * echo "Subscribe loop ended\n";
     */
    public function ssubscribe(array $channels, callable $cb): bool;

    /**
     * Retrieve the length of a Redis STRING key.
     *
     * @param string $key The key we want the length of.
     *
     * @return Redis|int|false The length of the string key if it exists, zero if it does not, and
     *                         false on failure.
     *
     * @see https://redis.io/docs/latest/commands/strlen/
     *
     * @example $redis->strlen('mykey');
     */
    public function strlen(string $key): Redis|int|false;

    /**
     * Subscribe to one or more Redis pubsub channels.
     *
     * @param array    $channels One or more channel names.
     * @param callable $cb       The callback PhpRedis will invoke when we receive a message
     *                           from one of the subscribed channels.
     *
     * @return bool True on success, false on faiilure.  Note that this command will block the
     *              client in a subscribe loop, waiting for messages to arrive.
     *
     * @see https://redis.io/docs/latest/commands/subscribe/
     *
     * @example
     * $redis = new Redis(['host' => 'localhost']);
     *
     * $redis->subscribe(['channel-1', 'channel-2'], function ($redis, $channel, $message) {
     *     echo "[$channel]: $message\n";
     *
     *     // Unsubscribe from the message channel when we read 'quit'
     *     if ($message == 'quit') {
     *         echo "Unsubscribing from '$channel'\n";
     *         $redis->unsubscribe([$channel]);
     *     }
     * });
     *
     * // Once we read 'quit' from both channel-1 and channel-2 the subscribe loop will be
     * // broken and this command will execute.
     * echo "Subscribe loop ended\n";
     */
    public function subscribe(array $channels, callable $cb): bool;

    /**
     * Unsubscribes the client from the given shard channels,
     * or from all of them if none is given.
     *
     * @param array $channels One or more channels to unsubscribe from.
     * @return Redis|array|bool The array of unsubscribed channels.
     *
     * @see https://redis.io/docs/latest/commands/sunsubscribe/
     * @see Redis::ssubscribe()
     *
     * @example
     * $redis->ssubscribe(['channel-1', 'channel-2'], function ($redis, $channel, $message) {
     *     if ($message == 'quit') {
     *         echo "$channel => 'quit' detected, unsubscribing!\n";
     *         $redis->sunsubscribe([$channel]);
     *     } else {
     *         echo "$channel => $message\n";
     *     }
     * });
     *
     * echo "We've unsubscribed from both channels, exiting\n";
     */
    public function sunsubscribe(array $channels): Redis|array|bool;

    /**
     * Atomically swap two Redis databases so that all of the keys in the source database will
     * now be in the destination database and vice-versa.
     *
     * Note: This command simply swaps Redis' internal pointer to the database and is therefore
     * very fast, regardless of the size of the underlying databases.
     *
     * @param int $src The source database number
     * @param int $dst The destination database number
     *
     * @return Redis|bool Success if the databases could be swapped and false on failure.
     *
     * @see https://redis.io/docs/latest/commands/swapdb/
     * @see Redis::del()
     *
     * @example
     * $redis->select(0);
     * $redis->set('db0-key', 'db0-value');
     * $redis->swapdb(0, 1);
     * $redis->get('db0-key');
     */
    public function swapdb(int $src, int $dst): Redis|bool;

    /**
     * Retrieve the server time from the connected Redis instance.
     *
     * @see https://redis.io/docs/latest/commands/time/
     *
     * @return Redis|array A two element array consisting of a Unix Timestamp and the number of microseconds
     *                     elapsed since the second.
     *
     * @example $redis->time();
     */
    public function time(): Redis|array;

    /**
     * Get the amount of time a Redis key has before it will expire, in seconds.
     *
     * @param string $key      The Key we want the TTL for.
     * @return Redis|int|false (a) The number of seconds until the key expires, or -1 if the key has
     *                         no expiration, and -2 if the key does not exist.  In the event of an
     *                         error, this command will return false.
     *
     * @see https://redis.io/docs/latest/commands/ttl/
     *
     * @example $redis->ttl('mykey');
     */
    public function ttl(string $key): Redis|int|false;

    /**
     * Get the type of a given Redis key.
     *
     * @see https://redis.io/docs/latest/commands/type/
     *
     * @param  string $key     The key to check
     * @return Redis|int|false The Redis type constant or false on failure.
     *
     * The Redis class defines several type constants that correspond with Redis key types.
     *
     *     Redis::REDIS_NOT_FOUND
     *     Redis::REDIS_STRING
     *     Redis::REDIS_SET
     *     Redis::REDIS_LIST
     *     Redis::REDIS_ZSET
     *     Redis::REDIS_HASH
     *     Redis::REDIS_STREAM
     *     Redis::REDIS_VECTORSET
     *
     * @example
     * foreach ($redis->keys('*') as $key) {
     *     echo "$key => " . $redis->type($key) . "\n";
     * }
     */
    public function type(string $key): Redis|int|false;

    /**
     * Delete one or more keys from the Redis database.  Unlike this operation, the actual
     * deletion is asynchronous, meaning it is safe to delete large keys without fear of
     * Redis blocking for a long period of time.
     *
     * @param array|string $key     Either an array with one or more keys or a string with
     *                              the first key to delete.
     * @param string ...$other_keys If the first argument passed to this method was a string
     *                              you may pass any number of additional key names.
     *
     * @return Redis|int|false The number of keys deleted or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/unlink/
     * @see https://redis.io/docs/latest/commands/del/
     * @see Redis::del()
     *
     * @example $redis->unlink('key1', 'key2', 'key3');
     * @example $redis->unlink(['key1', 'key2', 'key3']);
     */
    public function unlink(array|string $key, string ...$other_keys): Redis|int|false;

    /**
     * Unsubscribe from one or more subscribed channels.
     *
     * @param array $channels One or more channels to unsubscribe from.
     * @return Redis|array|bool The array of unsubscribed channels.
     *
     * @see https://redis.io/docs/latest/commands/unsubscribe/
     * @see Redis::subscribe()
     *
     * @example
     * $redis->subscribe(['channel-1', 'channel-2'], function ($redis, $channel, $message) {
     *     if ($message == 'quit') {
     *         echo "$channel => 'quit' detected, unsubscribing!\n";
     *         $redis->unsubscribe([$channel]);
     *     } else {
     *         echo "$channel => $message\n";
     *     }
     * });
     *
     * echo "We've unsubscribed from both channels, exiting\n";
     */
    public function unsubscribe(array $channels): Redis|array|bool;

    /**
     * Remove any previously WATCH'ed keys in a transaction.
     *
     * @see https://redis.io/docs/latest/commands/unwatch/
     * @see https://redis.io/docs/latest/commands/unwatch/
     * @see Redis::watch()
     *
     * @return Redis|bool True on success and false on failure.
     *
     * @example
     * $redis->unwatch();
     *
     */
    public function unwatch(): Redis|bool;

    /**
     * Watch one or more keys for conditional execution of a transaction.
     *
     * @param array|string $key     Either an array with one or more key names, or a string key name
     * @param string ...$other_keys If the first argument was passed as a string, any number of additional
     *                              string key names may be passed variadically.
     *
     * @return Redis|bool
     *
     * @see https://redis.io/docs/latest/commands/watch/
     * @see https://redis.io/docs/latest/commands/unwatch/
     *
     * @example
     * $redis1 = new Redis(['host' => 'localhost']);
     * $redis2 = new Redis(['host' => 'localhost']);
     *
     * // Start watching 'incr-key'
     * $redis1->watch('incr-key');
     *
     * // Retrieve its value.
     * $val = $redis1->get('incr-key');
     *
     * // A second client modifies 'incr-key' after we read it.
     * $redis2->set('incr-key', 0);
     *
     * // Because another client changed the value of 'incr-key' after we read it, this
     * // is no longer a proper increment operation, but because we are `WATCH`ing the
     * // key, this transaction will fail and we can try again.
     * //
     * // If were to comment out the above `$redis2->set('incr-key', 0)` line the
     * // transaction would succeed.
     * $redis1->multi();
     * $redis1->set('incr-key', $val + 1);
     * $res = $redis1->exec();
     *
     * // bool(false)
     * var_dump($res);
     */
    public function watch(array|string $key, string ...$other_keys): Redis|bool;

    /**
     * Block the client up to the provided timeout until a certain number of replicas have confirmed
     * receiving them.
     *
     * @see https://redis.io/docs/latest/commands/wait/
     *
     * @param int $numreplicas The number of replicas we want to confirm write operations
     * @param int $timeout     How long to wait (zero meaning forever).
     *
     * @return int|false The number of replicas that have confirmed or false on failure.
     *
     * @example
     * $redis->wait(1, 1000);
     *
     */
    public function wait(int $numreplicas, int $timeout): int|false;

    /**
     * Acknowledge one or more messages that are pending (have been consumed using XREADGROUP but
     * not yet acknowledged by XACK.)
     *
     * @param string $key   The stream to query.
     * @param string $group The consumer group to use.
     * @param array  $ids   An array of stream entry IDs.
     *
     * @return int|false The number of acknowledged messages
     *
     * @see https://redis.io/docs/latest/commands/xack/
     * @see https://redis.io/docs/latest/commands/xreadgroup/
     * @see Redis::xack()
     *
     * @example
     * $redis->xAdd('ships', '*', ['name' => 'Enterprise']);
     * $redis->xAdd('ships', '*', ['name' => 'Defiant']);
     *
     * $redis->xGroup('CREATE', 'ships', 'Federation', '0-0');
     *
     * // Consume a single message with the consumer group 'Federation'
     * $ship = $redis->xReadGroup('Federation', 'Picard', ['ships' => '>'], 1);
     *
     * /* Retrieve the ID of the message we read.
     * assert(isset($ship['ships']));
     * $id = key($ship['ships']);
     *
     * // The message we just read is now pending.
     * $res = $redis->xPending('ships', 'Federation'));
     * var_dump($res);
     *
     * // We can tell Redis we were able to process the message by using XACK
     * $res = $redis->xAck('ships', 'Federation', [$id]);
     * assert($res === 1);
     *
     * // The message should no longer be pending.
     * $res = $redis->xPending('ships', 'Federation');
     * var_dump($res);
     */
    public function xack(string $key, string $group, array $ids): int|false;

    /**
     * Append a message to a stream.
     *
     * @param string $key        The stream name.
     * @param string $id         The ID for the message we want to add.  This can be the special value '*'
     *                           which means Redis will generate the ID that appends the message to the
     *                           end of the stream.  It can also be a value in the form <ms>-* which will
     *                           generate an ID that appends to the end of entries with the same <ms> value
     *                           (if any exist).
     * @param int    $maxlen     If specified Redis will append the new message but trim any number of the
     *                           oldest messages in the stream until the length is <= $maxlen.
     * @param bool   $approx     Used in conjunction with `$maxlen`, this flag tells Redis to trim the stream
     *                           but in a more efficient way, meaning the trimming may not be exactly to
     *                           `$maxlen` values.
     * @param bool   $nomkstream If passed as `TRUE`, the stream must exist for Redis to append the message.
     *
     * @see https://redis.io/docs/latest/commands/xadd/
     *
     * @example $redis->xAdd('ds9-season-1', '1-1', ['title' => 'Emissary Part 1']);
     * @example $redis->xAdd('ds9-season-1', '1-2', ['title' => 'A Man Alone']);
     */
    public function xadd(string $key, string $id, array $values, int $maxlen = 0, bool $approx = false, bool $nomkstream = false): Redis|string|false;

    /**
     * This command allows a consumer to claim pending messages that have been idle for a specified period of time.
     * Its purpose is to provide a mechanism for picking up messages that may have had a failed consumer.
     *
     * @see https://redis.io/docs/latest/commands/xautoclaim/
     * @see https://redis.io/docs/latest/commands/xclaim/
     * @see https://redis.io/docs/data-types/streams-tutorial/
     *
     * @param string $key      The stream to check.
     * @param string $group    The consumer group to query.
     * @param string $consumer Which consumer to check.
     * @param int    $min_idle The minimum time in milliseconds for the message to have been pending.
     * @param string $start    The minimum message id to check.
     * @param int    $count    An optional limit on how many messages are returned.
     * @param bool   $justid   If the client only wants message IDs and not all of their data.
     *
     * @return Redis|array|bool An array of pending IDs or false if there are none, or on failure.
     *
     * @example
     * $redis->xGroup('CREATE', 'ships', 'combatants', '0-0', true);
     *
     * $redis->xAdd('ships', '1424-74205', ['name' => 'Defiant']);
     *
     * // Consume the ['name' => 'Defiant'] message
     * $msgs = $redis->xReadGroup('combatants', "Jem'Hadar", ['ships' => '>'], 1);
     *
     * // The "Jem'Hadar" consumer has the message presently
     * $pending = $redis->xPending('ships', 'combatants');
     * var_dump($pending);
     *
     * // Assume control of the pending message with a different consumer.
     * $res = $redis->xAutoClaim('ships', 'combatants', 'Sisko', 0, '0-0');
     *
     * // Now the 'Sisko' consumer owns the message
     * $pending = $redis->xPending('ships', 'combatants');
     * var_dump($pending);
     */
    public function xautoclaim(string $key, string $group, string $consumer, int $min_idle, string $start, int $count = -1, bool $justid = false): Redis|bool|array;

    /**
     * This method allows a consumer to take ownership of pending stream entries, by ID.  Another
     * command that does much the same thing but does not require passing specific IDs is `Redis::xAutoClaim`.
     *
     * @see https://redis.io/docs/latest/commands/xclaim/
     * @see https://redis.io/docs/latest/commands/xautoclaim./
     *
     * @param string $key      The stream we wish to claim messages for.
     * @param string $group    Our consumer group.
     * @param string $consumer Our consumer.
     * @param int    $min_idle The minimum idle-time in milliseconds a message must have for ownership to be transferred.
     * @param array  $options  An options array that modifies how the command operates.
     *
     *                         ```php
     *                         # Following is an options array describing every option you can pass.  Note that
     *                         # 'IDLE', and 'TIME' are mutually exclusive.
     *                         $options = [
     *                             'IDLE'       => 3           # Set the idle time of the message to a 3.  By default
     *                                                         # the idle time is set to zero.
     *                             'TIME'       => 1000*time() # Same as IDLE except it takes a unix timestamp in
     *                                                         # milliseconds.
     *                             'RETRYCOUNT' => 0           # Set the retry counter to zero.  By default XCLAIM
     *                                                         # doesn't modify the counter.
     *                             'FORCE'                     # Creates the pending message entry even if IDs are
     *                                                         # not already
     *                                                         # in the PEL with another client.
     *                             'JUSTID'                    # Return only an array of IDs rather than the messages
     *                                                         # themselves.
     *                         ];
     *                         ```
     *
     * @return Redis|array|bool      An array of claimed messages or false on failure.
     *
     * @example
     * $redis->xGroup('CREATE', 'ships', 'combatants', '0-0', true);
     *
     * $redis->xAdd('ships', '1424-74205', ['name' => 'Defiant']);
     *
     * // Consume the ['name' => 'Defiant'] message
     * $msgs = $redis->xReadGroup('combatants', "Jem'Hadar", ['ships' => '>'], 1);
     *
     * // The "Jem'Hadar" consumer has the message presently
     * $pending = $redis->xPending('ships', 'combatants');
     * var_dump($pending);
     *
     * assert($pending && isset($pending[1]));
     *
     * // Claim the message by ID.
     * $claimed = $redis->xClaim('ships', 'combatants', 'Sisko', 0, [$pending[1]], ['JUSTID']);
     * var_dump($claimed);
     *
     * // Now the 'Sisko' consumer owns the message
     * $pending = $redis->xPending('ships', 'combatants');
     * var_dump($pending);
     */
    public function xclaim(string $key, string $group, string $consumer, int $min_idle, array $ids, array $options): Redis|array|bool;

    /**
     * Remove one or more specific IDs from a stream.
     *
     * @param string $key The stream to modify.
     * @param array $ids One or more message IDs to remove.
     *
     * @return Redis|int|false The number of messages removed or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/xdel/
     *
     * @example $redis->xDel('stream', ['1-1', '2-1', '3-1']);
     */
    public function xdel(string $key, array $ids): Redis|int|false;

    /**
     * Remove one or more IDs from a stream with extended options.
     *
     * @param string      $key  The stream to modify.
     * @param array       $ids  One or more message IDs to remove.
     * @param string|null $mode An optional mode argument.  Valid modes
     *                          are as follows: KEEPREF | DELREF | ACKED
     *
     * @return Redis|array|false An array corresponding to IDs. 1 if the id was
     *                           deleted and 0 if not.
     *
     * @see https://redis.io/docs/latest/commands/xdelex/
     *
     * @example
     * $redis->xadd('s', '*', ['field' => 'value1']);
     * $redis->xdelex('s', ['1-0'], 'KEEPREF');
     */
    public function xdelex(string $key, array $ids, ?string $mode = null): Redis|array|false;

    /**
     * XGROUP
     *
     * Perform various operation on consumer groups for a particular Redis STREAM.  What the command does
     * is primarily based on which operation is passed.
     *
     * @see https://redis.io/docs/latest/commands/xgroup/
     *
     * @param string      $operation      The subcommand you intend to execute.  Valid options are as follows
     *                                    'HELP'           - Redis will return information about the command
     *                                                       Requires: none
     *                                    'CREATE'         - Create a consumer group.
     *                                                       Requires:  Key, group, consumer.
     *                                    'SETID'          - Set the ID of an existing consumer group for the stream.
     *                                                       Requires:  Key, group, id.
     *                                    'CREATECONSUMER' - Create a new consumer group for the stream.  You must
     *                                                       also pass key, group, and the consumer name you wish to
     *                                                       create.
     *                                                       Requires:  Key, group, consumer.
     *                                    'DELCONSUMER'    - Delete a consumer from group attached to the stream.
     *                                                       Requires:  Key, group, consumer.
     *                                    'DESTROY'        - Delete a consumer group from a stream.
     *                                                       Requires:  Key, group.
     * @param string|null $key            The STREAM we're operating on.
     * @param string|null $group          The consumer group we want to create/modify/delete.
     * @param string|null $id_or_consumer The STREAM id (e.g. '$') or consumer group.  See the operation section
     *                                    for information about which to send.
     * @param bool        $mkstream       This flag may be sent in combination with the 'CREATE' operation, and
     *                                    cause Redis to also create the STREAM if it doesn't currently exist.
     * @param int         $entries_read   Allows you to set Redis' 'entries-read' STREAM value.  This argument is
     *                                    only relevant to the 'CREATE' and 'SETID' operations.
     *                                    Note:  Requires Redis >= 7.0.0.
     *
     * @return mixed This command return various results depending on the operation performed.
     *
     * @example
     * $redis->xgroup('CREATE', 'mystream', 'workers', '$');
     *
     */
    public function xgroup(string $operation, ?string $key = null, ?string $group = null, ?string $id_or_consumer = null,
                           bool $mkstream = false, int $entries_read = -2): mixed;

    /**
     * Retrieve information about a stream key.
     *
     * @param string      $operation The specific info operation to perform.
     * @param string|null $arg1      The first argument (depends on operation)
     * @param string|null $arg2      The second argument
     * @param int         $count     The COUNT argument to `XINFO STREAM`
     *
     * @return mixed This command can return different things depending on the operation being called.
     *
     * @see https://redis.io/docs/latest/commands/xinfo/
     *
     * @example $redis->xInfo('CONSUMERS', 'stream');
     * @example $redis->xInfo('GROUPS', 'stream');
     * @example $redis->xInfo('STREAM', 'stream');
     */
    public function xinfo(string $operation, ?string $arg1 = null, ?string $arg2 = null, int $count = -1): mixed;


    /**
     * Get the number of messages in a Redis STREAM key.
     *
     * @param string $key The Stream to check.
     *
     * @return Redis|int|false The number of messages or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/xlen/
     *
     * @example $redis->xLen('stream');
     */
    public function xlen(string $key): Redis|int|false;

    /**
     * Interact with stream messages that have been consumed by a consumer group but not yet
     * acknowledged with XACK.
     *
     * @see https://redis.io/docs/latest/commands/xpending/
     * @see https://redis.io/docs/latest/commands/xreadgroup/
     *
     * @param string      $key      The stream to inspect.
     * @param string      $group    The user group we want to see pending messages from.
     * @param string|null $start    The minimum ID to consider.
     * @param string|null $end      The maximum ID to consider.
     * @param int         $count    Optional maximum number of messages to return.
     * @param string|null $consumer If provided, limit the returned messages to a specific consumer.
     *
     * @return Redis|array|false The pending messages belonging to the stream or false on failure.
     *
     * @example
     * $redis->xpending('mystream', 'workers', '-', '+', 10);
     *
     */
    public function xpending(string $key, string $group, ?string $start = null, ?string $end = null, int $count = -1, ?string $consumer = null): Redis|array|false;

    /**
     * Get a range of entries from a STREAM key.
     *
     * @param string $key   The stream key name to list.
     * @param string $start The minimum ID to return.
     * @param string $end   The maximum ID to return.
     * @param int    $count An optional maximum number of entries to return.
     *
     * @return Redis|array|bool The entries in the stream within the requested range or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/xrange/
     *
     * @example $redis->xRange('stream', '0-1', '0-2');
     * @example $redis->xRange('stream', '-', '+');
     */
    public function xrange(string $key, string $start, string $end, int $count = -1): Redis|array|bool;

    /**
     * Consume one or more unconsumed elements in one or more streams.
     *
     * @param array $streams An associative array with stream name keys and minimum id values.
     * @param int   $count   An optional limit to how many entries are returned *per stream*
     * @param int   $block   An optional maximum number of milliseconds to block the caller if no
     *                       data is available on any of the provided streams.
     *
     * @return Redis|array|bool An array of read elements or false if there aren't any.
     *
     * @see https://redis.io/docs/latest/commands/xread/
     *
     * @example
     * $redis->xAdd('s03', '3-1', ['title' => 'The Search, Part I']);
     * $redis->xAdd('s03', '3-2', ['title' => 'The Search, Part II']);
     * $redis->xAdd('s03', '3-3', ['title' => 'The House Of Quark']);
     * $redis->xAdd('s04', '4-1', ['title' => 'The Way of the Warrior']);
     * $redis->xAdd('s04', '4-3', ['title' => 'The Visitor']);
     * $redis->xAdd('s04', '4-4', ['title' => 'Hippocratic Oath']);
     *
     * $redis->xRead(['s03' => '3-2', 's04' => '4-1']);
     */
    public function xread(array $streams, int $count = -1, int $block = -1): Redis|array|bool;

    /**
     * Read one or more messages using a consumer group.
     *
     * @param string $group     The consumer group to use.
     * @param string $consumer  The consumer to use.
     * @param array  $streams   An array of stream names and message IDs
     * @param int    $count     Optional maximum number of messages to return
     * @param int    $block     How long to block if there are no messages available.
     *
     * @return Redis|array|bool Zero or more unread messages or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/xreadgroup/
     *
     * @example
     * $redis->xGroup('CREATE', 'episodes', 'ds9', '0-0', true);
     *
     * $redis->xAdd('episodes', '1-1', ['title' => 'Emissary: Part 1']);
     * $redis->xAdd('episodes', '1-2', ['title' => 'A Man Alone']);
     *
     * $messages = $redis->xReadGroup('ds9', 'sisko', ['episodes' => '>']);
     *
     * // After having read the two messages, add another
     * $redis->xAdd('episodes', '1-3', ['title' => 'Emissary: Part 2']);
     *
     * // Acknowledge the first two read messages
     * foreach ($messages as $stream => $stream_messages) {
     *     $ids = array_keys($stream_messages);
     *     $redis->xAck('stream', 'ds9', $ids);
     * }
     *
     * // We can now pick up where we left off, and will only get the final message
     * $msgs = $redis->xReadGroup('ds9', 'sisko', ['episodes' => '>']);
     */
    public function xreadgroup(string $group, string $consumer, array $streams, int $count = 1, int $block = 1): Redis|array|bool;

    /**
     * Get a range of entries from a STREAM key in reverse chronological order.
     *
     * @param string $key   The stream key to query.
     * @param string $end   The maximum message ID to include.
     * @param string $start The minimum message ID to include.
     * @param int    $count An optional maximum number of messages to include.
     *
     * @return Redis|array|bool The entries within the requested range, from newest to oldest.
     *
     * @see https://redis.io/docs/latest/commands/xrevrange/
     * @see https://redis.io/docs/latest/commands/xrange/
     *
     * @example $redis->xRevRange('stream', '0-2', '0-1');
     * @example $redis->xRevRange('stream', '+', '-');
     */
    public function xrevrange(string $key, string $end, string $start, int $count = -1): Redis|array|bool;

    /**
     * Add to a vector set
     *
     * @param string $key         The vector set to add to.
     * @param array $values       A non-empty array of floating point values
     * @param mixed $element      The element to add to the vector set.
     * @param array|null $options An optional options array
     *
     * @return Redis|int|false One if the key was added zero if not.
     *
     * @see https://redis.io/docs/latest/commands/vadd/
     *
     * @example
     * $redis->vadd('embeddings', [0.12, 0.04, 0.88], 'doc:1');
     *
     */
    public function vadd(string $key, array $values, mixed $element, array|null $options = null): Redis|int|false;

    /**
     * Query similarity of a vector by element or scores
     *
     * @param string $key          The vector set to query.
     * @param mixed $member        Either an element or array of scores. PhpRedis
     *                             will attempt to infer which it is, but since
     *                             there can be some ambiguity here due to
     *                             serialization you can also explicitly specify
     *                             `ELE`, `VALUES`, or `FP32` in the options
     *                             array.
     * @param array|null $options  An optional options array
     *
     * @return Redis|array|false   An array of elements and their similarity scores, or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/vsim/
     *
     * @example
     * $redis->vsim('embeddings', 'doc:1', ['COUNT' => 3]);
     *
     */
    public function vsim(string $key, mixed $member, array|null $options = null): Redis|array|false;

    /**
     * Get the length of a vector set
     *
     * @param string $key The vector set to query.
     *
     * @return Redis|int|false The number of elements in the vector set or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/vcard/
     *
     * @example
     * $redis->vcard('embeddings');
     *
     */
    public function vcard(string $key): Redis|int|false;

    /**
     * Get the dimensions of a vector set
     *
     * @param string $key The vector set to query.
     *
     * @return Redis|int|false The number of dimensions in the vector set or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/vdim/
     *
     * @example
     * $redis->vdim('embeddings');
     *
     */
    public function vdim(string $key): Redis|int|false;

    /**
     * Get various bits of information about a vector set
     *
     * @param string $key The vector set to query.
     *
     * @return Redis|array|false An array of information about the vector set or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/vinfo/
     *
     * @example
     * $redis->vinfo('embeddings');
     *
     */
    public function vinfo(string $key): Redis|array|false;

    /**
     * Check if an element is a member of a vectorset
     *
     * @param string $key    The vector set to query.
     * @param mixed  $member The member to check for.
     *
     * @return Redis|bool true if the member exists, false if it does not.
     *
     * @see https://redis.io/docs/latest/commands/vismember/
     *
     * @example
     * $redis->vismember('embeddings', 'doc:1');
     *
     */
    public function vismember(string $key, mixed $member): Redis|bool;

    /**
     * Get the embeddings for a specific member
     *
     * @param string $key   The vector set to query.
     * @param mixed $member The member to query.
     * @param bool $raw     If set to `true`, the raw embeddings will be returned
     *
     * @return Redis|array|false An array of embeddings for the member or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/vemb/
     *
     * @example
     * $redis->vemb('embeddings', 'doc:1');
     *
     */
    public function vemb(string $key, mixed $member, bool $raw = false): Redis|array|false;

    /**
     * Get one or more random members from a vector set
     *
     * @param string $key   The vector set to query.
     * @param int $count    The number of random members to return.
     *
     * @see https://redis.io/docs/latest/commands/vrandmember/
     *
     * @example
     * $redis->vrandmember('embeddings', 2);
     *
     */
    public function vrandmember(string $key, int $count = 0): Redis|array|string|false;

    /**
     * Retreive a lexographical range of elements from a vector set
     *
     * @param string $key        The vector set to query.
     * @param string $min        The minimum element to return.
     * @param string $max        The maximum element to return.
     * @param int    $count      An optional maximum number of elements to return.
     *
     * @return Redis|array|false An array of elements in the requested range or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/vrange/
     *
     * @example
     * $redis->vrange('embeddings', '-', '+', 5);
     *
     */
    public function vrange(string $key, string $min, string $max, int $count = -1): Redis|array|false;

    /**
     * Remove an element from a vector set
     *
     * @param string $key     The vector set to remove from.
     * @param mixed  $member  The member to remove.
     *
     * @return Redis|int|faslse 1 if the member was removed, 0 if it was not.
     *
     * @see https://redis.io/docs/latest/commands/vrem/
     *
     * @example
     * $redis->vrem('embeddings', 'doc:1');
     *
     */
    public function vrem(string $key, mixed $member): Redis|int|false;

    /**
     * Set the attributes of a vector set element
     *
     * @param string $key              The vector set to modify.
     * @param mixed $member            The member to modify.
     * @param array|string $attributes The attributes to set. This should either
     *                                 be a json encoded string or an array which
     *                                 will be json encoded.
     *
     * @return Redis|int|false 1 if the attributes were set, 0 if they were not.
     *
     * @see https://redis.io/docs/latest/commands/vsetattr/
     *
     * @example
     * $redis->vsetattr('embeddings', 'doc:1', ['topic' => 'tech']);
     *
     */
    public function vsetattr(string $key, mixed $member, array|string $attributes): Redis|int|false;

    /**
     * Get the attributes of a vector set element
     *
     * @param string $key    The vector set to query.
     * @param mixed  $member The member to query.
     * @param bool   $decode Whether to automatically deserialize any returned json.
     *
     * @return Redis|array|string|false An array of attributes for the member or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/vgetattr/
     *
     * @example
     * $redis->vgetattr('embeddings', 'doc:1');
     *
     */
    public function vgetattr(string $key, mixed $member, bool $decode = true): Redis|array|string|false;

    /**
     * Get any adajcent values for a member of a vector set.
     *
     * @param string $key     The vector set to query.
     * @param mixed  $member  The member to query.
     * @param bool   $withscores If set to `true`, the scores of the adjacent values will be returned.
     *
     * @return Redis|array|false An array of adjacent values and their scores, or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/vlinks/
     *
     * @example
     * $redis->vlinks('embeddings', 'doc:1', true);
     *
     */
    public function vlinks(string $key, mixed $member, bool $withscores = false): Redis|array|false;

    /**
     * Get rate limiting information
     *
     * @param string $key
     * @param int $maxBurst
     * @param int $requestsPerPeriod
     * @param int $period
     * @param int $tokens = 0
     * @return Redis|array|false
     *
     * @see https://redis.io/docs/latest/commands/gcra/
     *
     * @example
     * $redis->gcra('user:123', 10, 100, 3600);
     */
    public function gcra(string $key, int $maxBurst, int $requestsPerPeriod,
                         int $period, int $tokens = 0): Redis|array|false;


    /**
     * Truncate a STREAM key in various ways.
     *
     * @param string $key       The STREAM key to trim.
     * @param string $threshold This can either be a maximum length, or a minimum id.
     *                          MAXLEN - An integer describing the maximum desired length of the stream after the command.
     *                          MINID  - An ID that will become the new minimum ID in the stream, as Redis will trim all
     *                                   messages older than this ID.
     * @param bool   $approx    Whether redis is allowed to do an approximate trimming of the stream.  This is
     *                          more efficient for Redis given how streams are stored internally.
     * @param bool   $minid     When set to `true`, users should pass a minimum ID to the `$threshold` argument.
     * @param int    $limit     An optional upper bound on how many entries to trim during the command.
     *
     * @return Redis|int|false  The number of entries deleted from the stream.
     *
     * @see https://redis.io/docs/latest/commands/xtrim/
     *
     * @example $redis->xTrim('stream', 3);
     * @example $redis->xTrim('stream', '2-1', false, true);
     */
    public function xtrim(string $key, string $threshold, bool $approx = false, bool $minid = false, int $limit = -1): Redis|int|false;

    /**
     * Add one or more elements and scores to a Redis sorted set.
     *
     * @param string      $key                     The sorted set in question.
     * @param array|float $score_or_options        Either the score for the first element, or an array of options.
     *                                              ```php
     *                                              $options = [
     *                                                  'NX',       # Only update elements that already exist
     *                                                  'NX',       # Only add new elements but don't update existing ones.
     *
     *                                                  'LT'        # Only update existing elements if the new score is
     *                                                              # less than the existing one.
     *                                                  'GT'        # Only update existing elements if the new score is
     *                                                              # greater than the existing one.
     *
     *                                                  'CH'        # Instead of returning the number of elements added,
     *                                                              # Redis will return the number Of elements that were
     *                                                              # changed in the operation.
     *
     *                                                  'INCR'      # Instead of setting each element to the provide score,
     *                                                              # increment the element by the
     *                                                              # provided score, much like ZINCRBY.  When this option
     *                                                              # is passed, you may only send a single score and member.
     *                                              ];
     *                                             ```
     *                                              Note:  'GX', 'LT', and 'NX' cannot be passed together, and PhpRedis
     *                                                     will send whichever one is last in the options array.
     * @param mixed       ...$more_scores_and_mems A variadic number of additional scores and members.
     *
     * @return Redis|int|float|false The return value varies depending on the options passed.
     *
     * Following is information about the options that may be passed as the second argument:
     *
     * @see https://redis.io/docs/latest/commands/zadd/
     *
     * @example
     * $redis->zadd('zs', 1, 'first', 2, 'second', 3, 'third');
     * $redis->zAdd('zs', ['XX'], 8, 'second', 99, 'new-element');
     */
    public function zAdd(string $key, array|float $score_or_options, mixed ...$more_scores_and_mems): Redis|int|float|false;

    /**
     * Return the number of elements in a sorted set.
     *
     * @param string $key The sorted set to retrieve cardinality from.
     *
     * @return Redis|int|false The number of elements in the set or false on failure
     *
     * @see https://redis.io/docs/latest/commands/zcard/
     *
     * @example $redis->zCard('zs');
     */
    public function zCard(string $key): Redis|int|false;

    /**
     * Count the number of members in a sorted set with scores inside a provided range.
     *
     * @param string     $key The sorted set to check.
     * @param int|string $start The minimum score to include in the count
     * @param int|string $end The maximum score to include in the count
     *
     * NOTE:  In addition to a floating point score you may pass the special values of '-inf' and
     *        '+inf' meaning negative and positive infinity, respectively.
     *
     * @see https://redis.io/docs/latest/commands/zcount/
     *
     * @example
     * $redis->zCount('fruit-rankings', '0', '+inf');
     * $redis->zCount('fruit-rankings', 50, 60);
     * $redis->zCount('fruit-rankings', '-inf', 0);
     */
    public function zCount(string $key, int|string $start, int|string $end): Redis|int|false;

    /**
     * Create or increment the score of a member in a Redis sorted set
     *
     * @param string $key   The sorted set in question.
     * @param float  $value How much to increment the score.
     *
     * @return Redis|float|false The new score of the member or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/zincrby/
     *
     * @example
     * $redis->zIncrBy('zs', 5.0, 'bananas');
     * $redis->zIncrBy('zs', 2.0, 'eggplants');
     */
    public function zIncrBy(string $key, float $value, mixed $member): Redis|float|false;

    /**
     * Count the number of elements in a sorted set whose members fall within the provided
     * lexographical range.
     *
     * @param string $key The sorted set to check.
     * @param string $min The minimum matching lexographical string
     * @param string $max The maximum matching lexographical string
     *
     * @return Redis|int|false The number of members that fall within the range or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/zlexcount/
     *
     * @example
     * $redis->zAdd('captains', 0, 'Janeway', 0, 'Kirk', 0, 'Picard', 0, 'Sisko', 0, 'Archer');
     * $redis->zLexCount('captains', '[A', '[S');
     */
    public function zLexCount(string $key, string $min, string $max): Redis|int|false;

    /**
     * Retrieve the score of one or more members in a sorted set.
     *
     * @see https://redis.io/docs/latest/commands/zmscore/
     *
     * @param string $key              The sorted set
     * @param mixed  $member           The first member to return the score from
     * @param mixed  ...$other_members One or more additional members to return the scores of.
     *
     * @return Redis|array|false An array of the scores of the requested elements.
     *
     * @example
     * $redis->zAdd('zs', 0, 'zero', 1, 'one', 2, 'two', 3, 'three');
     *
     * $redis->zMScore('zs', 'zero', 'two');
     * $redis->zMScore('zs', 'one', 'not-a-member');
     */
    public function zMscore(string $key, mixed $member, mixed ...$other_members): Redis|array|false;

    /**
     * Pop one or more of the highest scoring elements from a sorted set.
     *
     * @param string   $key   The sorted set to pop elements from.
     * @param int|null $count An optional count of elements to pop.
     *
     * @return Redis|array|false All of the popped elements with scores or false on failure
     *
     * @see https://redis.io/docs/latest/commands/zpopmax/
     *
     * @example
     * $redis->zAdd('zs', 0, 'zero', 1, 'one', 2, 'two', 3, 'three');
     *
     * $redis->zPopMax('zs');
     * $redis->zPopMax('zs', 2);.
     */
    public function zPopMax(string $key, ?int $count = null): Redis|array|false;

    /**
     * Pop one or more of the lowest scoring elements from a sorted set.
     *
     * @param string   $key   The sorted set to pop elements from.
     * @param int|null $count An optional count of elements to pop.
     *
     * @return Redis|array|false The popped elements with their scores or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/zpopmin/
     *
     * @example
     * $redis->zAdd('zs', 0, 'zero', 1, 'one', 2, 'two', 3, 'three');
     *
     * $redis->zPopMin('zs');
     * $redis->zPopMin('zs', 2);
     */
    public function zPopMin(string $key, ?int $count = null): Redis|array|false;

    /**
     * Retrieve a range of elements of a sorted set between a start and end point.
     * How the command works in particular is greatly affected by the options that
     * are passed in.
     *
     * @param string          $key     The sorted set in question.
     * @param mixed           $start   The starting index we want to return.
     * @param mixed           $end     The final index we want to return.
     *
     * @param array|bool|null $options This value may either be an array of options to pass to
     *                                 the command, or for historical purposes a boolean which
     *                                 controls just the 'WITHSCORES' option.
     *                                 ```php
     *                                 $options = [
     *                                     'WITHSCORES' => true,     # Return both scores and members.
     *                                     'LIMIT'      => [10, 10], # Start at offset 10 and return 10 elements.
     *                                     'REV'                     # Return the elements in reverse order
     *                                     'BYSCORE',                # Treat `start` and `end` as scores instead
     *                                     'BYLEX'                   # Treat `start` and `end` as lexicographical values.
     *                                 ];
     *                                 ```
     *
     *                                 Note:  `BYLEX` and `BYSCORE` are mutually exclusive.
     *
     *
     * @return Redis|array|false  An array with matching elements or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/zrange/
     * @category zset
     *
     * @example
     * $redis->zRange('zset', 0, -1);
     * $redis->zRange('zset', '-inf', 'inf', ['byscore']);
     */
    public function zRange(string $key, string|int $start, string|int $end, array|bool|null $options = null): Redis|array|false;

    /**
     * Retrieve a range of elements from a sorted set by legographical range.
     *
     * @param string $key    The sorted set to retrieve elements from
     * @param string $min    The minimum legographical value to return
     * @param string $max    The maximum legographical value to return
     * @param int    $offset An optional offset within the matching values to return
     * @param int    $count  An optional count to limit the replies to (used in conjunction with offset)
     *
     * @return Redis|array|false An array of matching elements or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/zrangebylex/
     *
     * @example
     * $redis = new Redis(['host' => 'localhost']);
     * $redis->zAdd('captains', 0, 'Janeway', 0, 'Kirk', 0, 'Picard', 0, 'Sisko', 0, 'Archer');
     *
     * $redis->zRangeByLex('captains', '[A', '[S');
     * $redis->zRangeByLex('captains', '[A', '[S', 2, 2);
     */
    public function zRangeByLex(string $key, string $min, string $max, int $offset = -1, int $count = -1): Redis|array|false;

    /**
     * Retrieve a range of members from a sorted set by their score.
     *
     * @param string $key     The sorted set to query.
     * @param string $start   The minimum score of elements that Redis should return.
     * @param string $end     The maximum score of elements that Redis should return.
     * @param array  $options Options that change how Redis will execute the command.
     *
     *                        OPTION       TYPE            MEANING
     *                        'WITHSCORES' bool            Whether to also return scores.
     *                        'LIMIT'      [offset, count] Limit the reply to a subset of elements.
     *
     * @return Redis|array|false The number of matching elements or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/zrangebyscore/
     *
     * @example
     * $redis->zRangeByScore('zs', 20, 30, ['WITHSCORES' => true]);
     * $redis->zRangeByScore('zs', 20, 30, ['WITHSCORES' => true, 'LIMIT' => [5, 5]]);
     */
    public function zRangeByScore(string $key, string $start, string $end, array $options = []): Redis|array|false;

    /**
     * This command is similar to ZRANGE except that instead of returning the values directly
     * it will store them in a destination key provided by the user
     *
     * @param string           $dstkey  The key to store the resulting element(s)
     * @param string           $srckey  The source key with element(s) to retrieve
     * @param string           $start   The starting index to store
     * @param string           $end     The ending index to store
     * @param array|bool|null  $options Our options array that controls how the command will function.
     *
     * @return Redis|int|false The number of elements stored in $dstkey or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/zrange/
     * @see Redis::zRange
     * @category zset
     *
     * See {@link Redis::zRange} for a full description of the possible options.
     *
     * @example
     * $redis->zrangestore('recent:leaders', 'leaders', '0', '9');
     *
     */
    public function zrangestore(string $dstkey, string $srckey, string $start, string $end,
                                array|bool|null $options = null): Redis|int|false;

    /**
     * Retrieve one or more random members from a Redis sorted set.
     *
     * @param string     $key     The sorted set to pull random members from.
     * @param array|null $options One or more options that determine exactly how the command operates.
     *                            OPTION       TYPE    MEANING
     *                            'COUNT'      int     The number of random members to return.
     *                            'WITHSCORES' bool    Whether to return scores and members instead of
     *
     * @return Redis|string|array One or more random elements.
     *
     * @see https://redis.io/docs/latest/commands/zrandmember/
     *
     * @example
     * $redis->zRandMember('zs', ['COUNT' => 2, 'WITHSCORES' => true]);
     */
    public function zRandMember(string $key, ?array $options = null): Redis|string|array;

    /**
     * Get the rank of a member of a sorted set, by score.
     *
     * @param string $key     The sorted set to check.
     * @param mixed  $member The member to test.
     *
     * @return Redis|int|false The rank of the requested member.
     * @see https://redis.io/docs/latest/commands/zrank/
     *
     * @example
     * $redis->zRank('zs', 'zero');
     * $redis->zRank('zs', 'three');
     */
    public function zRank(string $key, mixed $member): Redis|int|false;

    /**
     * Remove one or more members from a Redis sorted set.
     *
     * @param mixed $key              The sorted set in question.
     * @param mixed $member           The first member to remove.
     * @param mixed ...$other_members One or more members to remove passed in a variadic fashion.
     *
     * @return Redis|int|false The number of members that were actually removed or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/zrem/
     *
     * @example
     * $redis->zRem('zs', 'mem:0', 'mem:1', 'mem:2', 'mem:6', 'mem:7', 'mem:8', 'mem:9');
     */
    public function zRem(mixed $key, mixed $member, mixed ...$other_members): Redis|int|false;

    /**
     * Remove zero or more elements from a Redis sorted set by legographical range.
     *
     * @param string $key The sorted set to remove elements from.
     * @param string $min The start of the lexographical range to remove.
     * @param string $max The end of the lexographical range to remove
     *
     * @return Redis|int|false The number of elements removed from the set or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/zremrangebylex/
     * @see Redis::zrangebylex()
     *
     * @example
     * $redis->zRemRangeByLex('zs', '[a', '(b');
     * $redis->zRemRangeByLex('zs', '(banana', '(eggplant');
     */
    public function zRemRangeByLex(string $key, string $min, string $max): Redis|int|false;

    /**
     * Remove one or more members of a sorted set by their rank.
     *
     * @param string $key    The sorted set where we want to remove members.
     * @param int    $start  The rank when we want to start removing members
     * @param int    $end    The rank we want to stop removing membersk.
     *
     * @return Redis|int|false The number of members removed from the set or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/zremrangebyrank/
     *
     * @example
     * $redis->zRemRangeByRank('zs', 0, 3);
     */
    public function zRemRangeByRank(string $key, int $start, int $end): Redis|int|false;

    /**
     * Remove one or more members of a sorted set by their score.
     *
     * @param string $key   The sorted set where we want to remove members.
     * @param string $start The lowest score to remove.
     * @param string $end   The highest score to remove.
     *
     * @return Redis|int|false The number of members removed from the set or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/zremrangebyrank/
     *
     * @example
     * $redis->zAdd('zs', 2, 'two', 4, 'four', 6, 'six');
     * $redis->zRemRangeByScore('zs', 2, 4);
     */
    public function zRemRangeByScore(string $key, string $start, string $end): Redis|int|false;

    /**
     * List the members of a Redis sorted set in reverse order
     *
     * @param string     $key    The sorted set in question.
     * @param int        $start  The index to start listing elements
     * @param int        $end    The index to stop listing elements.
     * @param mixed|null $scores Whether or not Redis should also return each members score.  See
     *                           the example below demonstrating how it may be used.
     *
     * @return Redis|array|false The members (and possibly scores) of the matching elements or false
     *                           on failure.
     *
     * @see https://redis.io/docs/latest/commands/zrevrange/
     *
     * @example
     * $redis->zRevRange('zs', 0, -1);
     * $redis->zRevRange('zs', 2, 3);
     * $redis->zRevRange('zs', 0, -1, true);
     * $redis->zRevRange('zs', 0, -1, ['withscores' => true]);
     */
    public function zRevRange(string $key, int $start, int $end, mixed $scores = null): Redis|array|false;

    /**
     * List members of a Redis sorted set within a legographical range, in reverse order.
     *
     * @param string $key    The sorted set to list
     * @param string $max    The maximum legographical element to include in the result.
     * @param string $min    The minimum lexographical element to include in the result.
     * @param int    $offset An option offset within the matching elements to start at.
     * @param int    $count  An optional count to limit the replies to.
     *
     * @return Redis|array|false The matching members or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/zrevrangebylex/
     * @see Redis::zrangebylex()
     *
     * @example
     * $redis->zRevRangeByLex('captains', '[Q', '[J');
     * $redis->zRevRangeByLex('captains', '[Q', '[J', 1, 2);
     */
    public function zRevRangeByLex(string $key, string $max, string $min, int $offset = -1, int $count = -1): Redis|array|false;

    /**
     * List elements from a Redis sorted set by score, highest to lowest
     *
     * @param string     $key     The sorted set to query.
     * @param string     $max     The highest score to include in the results.
     * @param string     $min     The lowest score to include in the results.
     * @param array|bool $options An options array that modifies how the command executes.
     *
     *                            ```php
     *                            $options = [
     *                                'WITHSCORES' => true|false # Whether or not to return scores
     *                                'LIMIT' => [offset, count] # Return a subset of the matching members
     *                            ];
     *                            ```
     *
     *                            NOTE: For legacy reason, you may also simply pass `true` for the
     *                                  options argument, to mean `WITHSCORES`.
     *
     * @return Redis|array|false The matching members in reverse order of score or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/zrevrangebyscore/
     *
     * @example
     * $redis->zadd('oldest-people', 122.4493, 'Jeanne Calment', 119.2932, 'Kane Tanaka',
     *                               119.2658, 'Sarah Knauss',   118.7205, 'Lucile Randon',
     *                               117.7123, 'Nabi Tajima',    117.6301, 'Marie-Louise Meilleur',
     *                               117.5178, 'Violet Brown',   117.3753, 'Emma Morano',
     *                               117.2219, 'Chiyo Miyako',   117.0740, 'Misao Okawa');
     *
     * $redis->zRevRangeByScore('oldest-people', 122, 119);
     * $redis->zRevRangeByScore('oldest-people', 'inf', 118);
     * $redis->zRevRangeByScore('oldest-people', '117.5', '-inf', ['LIMIT' => [0, 1]]);
     */
    public function zRevRangeByScore(string $key, string $max, string $min, array|bool $options = []): Redis|array|false;

    /**
    * Retrieve a member of a sorted set by reverse rank.
    *
    * @param string $key      The sorted set to query.
    * @param mixed  $member   The member to look up.
    *
    * @return Redis|int|false The reverse rank (the rank if counted high to low) of the member or
    *                         false on failure.
    * @see https://redis.io/docs/latest/commands/zrevrank/
    *
    * @example
    * $redis->zAdd('ds9-characters', 10, 'Sisko', 9, 'Garak', 8, 'Dax', 7, 'Odo');
    *
    * $redis->zrevrank('ds9-characters', 'Sisko');
    * $redis->zrevrank('ds9-characters', 'Garak');
    */
    public function zRevRank(string $key, mixed $member): Redis|int|false;

    /**
     * Get the score of a member of a sorted set.
     *
     * @param string $key    The sorted set to query.
     * @param mixed  $member The member we wish to query.
     *
     * @return Redis|float|false The score of the requested element or false if it is not found.
     *
     * @see https://redis.io/docs/latest/commands/zscore/
     *
     * @example
     * $redis->zAdd('telescopes', 11.9, 'LBT', 10.4, 'GTC', 10, 'HET');
     * $redis->zScore('telescopes', 'LBT');
     */
    public function zScore(string $key, mixed $member): Redis|float|false;

    /**
     * Given one or more sorted set key names, return every element that is in the first
     * set but not any of the others.
     *
     * @param array      $keys    One or more sorted sets.
     * @param array|null $options An array which can contain ['WITHSCORES' => true] if you want Redis to
     *                            return members and scores.
     *
     * @return Redis|array|false An array of members or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/zdiff/
     *
     * @example
     * $redis->zAdd('primes', 1, 'one', 3, 'three', 5, 'five');
     * $redis->zAdd('evens', 2, 'two', 4, 'four');
     * $redis->zAdd('mod3', 3, 'three', 6, 'six');
     *
     * $redis->zDiff(['primes', 'evens', 'mod3']);
     */
    public function zdiff(array $keys, ?array $options = null): Redis|array|false;

    /**
     * Store the difference of one or more sorted sets in a destination sorted set.
     *
     * See {@link Redis::zdiff} for a more detailed description of how the diff operation works.
     *
     * @param string $dst  The destination set name.
     * @param array  $keys One or more source key names
     *
     * @return Redis|int|false The number of elements stored in the destination set or false on
     *                         failure.
     *
     * @see https://redis.io/docs/latest/commands/zdiff/
     * @see Redis::zdiff()
     *
     * @example
     * $redis->zdiffstore('only:new', ['all:users', 'inactive:users']);
     *
     */
    public function zdiffstore(string $dst, array $keys): Redis|int|false;

    /**
     * Compute the intersection of one or more sorted sets and return the members
     *
     * @param array      $keys    One or more sorted sets.
     * @param array|null $weights An optional array of weights to be applied to each set when performing
     *                            the intersection.
     * @param array|null $options Options for how Redis should combine duplicate elements when performing the
     *                            intersection.  See Redis::zunion() for details.
     *
     * @return Redis|array|false All of the members that exist in every set.
     *
     * @see https://redis.io/docs/latest/commands/zinter/
     *
     * @example
     * $redis->zAdd('TNG', 2, 'Worf', 2.5, 'Data', 4.0, 'Picard');
     * $redis->zAdd('DS9', 2.5, 'Worf', 3.0, 'Kira', 4.0, 'Sisko');
     *
     * $redis->zInter(['TNG', 'DS9']);
     * $redis->zInter(['TNG', 'DS9'], NULL, ['withscores' => true]);
     * $redis->zInter(['TNG', 'DS9'], NULL, ['withscores' => true, 'aggregate' => 'max']);
     */
    public function zinter(array $keys, ?array $weights = null, ?array $options = null): Redis|array|false;

    /**
     * Similar to ZINTER but instead of returning the intersected values, this command returns the
     * cardinality of the intersected set.
     *
     * @see https://redis.io/docs/latest/commands/zintercard/
     * @see https://redis.io/docs/latest/commands/zinter/
     * @see Redis::zinter()
     *
     * @param array $keys   One or more sorted set key names.
     * @param int   $limit  An optional upper bound on the returned cardinality.  If set to a value
     *                      greater than zero, Redis will stop processing the intersection once the
     *                      resulting cardinality reaches this limit.
     *
     * @return Redis|int|false The cardinality of the intersection or false on failure.
     *
     * @example
     * $redis->zAdd('zs1', 1, 'one', 2, 'two', 3, 'three', 4, 'four');
     * $redis->zAdd('zs2', 2, 'two', 4, 'four');
     *
     * $redis->zInterCard(['zs1', 'zs2']);
     */
    public function zintercard(array $keys, int $limit = -1): Redis|int|false;

    /**
     * Compute the intersection of one or more sorted sets storing the result in a new sorted set.
     *
     * @param string      $dst       The destination sorted set to store the intersected values.
     * @param array       $keys      One or more sorted set key names.
     * @param array|null  $weights   An optional array of floats to weight each passed input set.
     * @param string|null $aggregate An optional aggregation method to use.
     *                               'SUM' - Store sum of all intersected members (this is the default).
     *                               'MIN' - Store minimum value for each intersected member.
     *                               'MAX' - Store maximum value for each intersected member.
     *
     * @return Redis|int|false  The total number of members writtern to the destination set or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/zinterstore/
     * @see https://redis.io/docs/latest/commands/zinter/
     *
     * @example
     * $redis->zAdd('zs1', 3, 'apples', 2, 'pears');
     * $redis->zAdd('zs2', 4, 'pears', 3, 'bananas');
     * $redis->zAdd('zs3', 2, 'figs', 3, 'pears');
     *
     * $redis->zInterStore('fruit-sum', ['zs1', 'zs2', 'zs3']);
     * $redis->zInterStore('fruit-max', ['zs1', 'zs2', 'zs3'], NULL, 'MAX');
     */
    public function zinterstore(string $dst, array $keys, ?array $weights = null, ?string $aggregate = null): Redis|int|false;

    /**
     * Scan the members of a sorted set incrementally, using a cursor
     *
     * @param string          $key      The sorted set to scan.
     * @param int|string|null $iterator A reference to an iterator that should be initialized to NULL initially, that
     *                                  will be updated after each subsequent call to ZSCAN.  Once the iterator
     *                                  has returned to zero the scan is complete
     * @param string|null     $pattern  An optional glob-style pattern that limits which members are returned during
     *                                  the scanning process.
     * @param int             $count    A hint for Redis that tells it how many elements it should test before returning
     *                                  from the call.  The higher the more work Redis may do in any one given call to
     *                                  ZSCAN potentially blocking for longer periods of time.
     *
     * @return Redis|array|false An array of elements or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/zscan/
     * @see https://redis.io/docs/latest/commands/scan/
     * @see Redis::scan()
     *
     * NOTE:  See Redis::scan() for detailed example code on how to call SCAN like commands.
     *
     * @example
     * $it = null;
     * while ($members = $redis->zscan('leaders', $it)) {
     *     foreach ($members as $member => $score) {
     *         printf('%s => %s' . PHP_EOL, $member, $score);
     *     }
     * }
     *
     */
    public function zscan(string $key, null|int|string &$iterator, ?string $pattern = null, int $count = 0): Redis|array|false;

    /**
     * Retrieve the union of one or more sorted sets
     *
     * @param array      $keys     One or more sorted set key names
     * @param array|null $weights  An optional array with floating point weights used when performing the union.
     *                             Note that if this argument is passed, it must contain the same number of
     *                             elements as the $keys array.
     * @param array|null $options  An array that modifies how this command functions.
     *
     *                             ```php
     *                             $options = [
     *                                 # By default when members exist in more than one set Redis will SUM
     *                                 # total score for each match.  Instead, it can return the AVG, MIN,
     *                                 # or MAX value based on this option.
     *                                 'AGGREGATE' => 'sum' | 'min' | 'max'
     *
     *                                 # Whether Redis should also return each members aggregated score.
     *                                 'WITHSCORES' => true | false
     *                             ]
     *                             ```
     *
     * @return Redis|array|false The union of each sorted set or false on failure
     *
     * @see https://redis.io/docs/latest/commands/zunion/
     *
     * @example
     * $redis->del('store1', 'store2', 'store3');
     * $redis->zAdd('store1', 1, 'apples', 3, 'pears', 6, 'bananas');
     * $redis->zAdd('store2', 3, 'apples', 5, 'coconuts', 2, 'bananas');
     * $redis->zAdd('store3', 2, 'bananas', 6, 'apples', 4, 'figs');
     *
     * $redis->zUnion(['store1', 'store2', 'store3'], NULL, ['withscores' => true]);
     * $redis->zUnion(['store1', 'store3'], [2, .5], ['withscores' => true]);
     * $redis->zUnion(['store1', 'store3'], [2, .5], ['withscores' => true, 'aggregate' => 'MIN']);
     */
    public function zunion(array $keys, ?array $weights = null, ?array $options = null): Redis|array|false;

    /**
     * Perform a union on one or more Redis sets and store the result in a destination sorted set.
     *
     * @param string      $dst       The destination set to store the union.
     * @param array       $keys      One or more input keys on which to perform our union.
     * @param array|null  $weights   An optional weights array used to weight each input set.
     * @param string|null $aggregate An optional modifier in how Redis will combine duplicate members.
     *                               Valid:  'MIN', 'MAX', 'SUM'.
     *
     * @return Redis|int|false The number of members stored in the destination set or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/zunionstore/
     * @see Redis::zunion()
     *
     * @example
     * $redis->zAdd('zs1', 1, 'one', 3, 'three');
     * $redis->zAdd('zs1', 2, 'two', 4, 'four');
     * $redis->zadd('zs3', 1, 'one', 7, 'five');
     *
     * $redis->zUnionStore('dst', ['zs1', 'zs2', 'zs3']);
     */
    public function zunionstore(string $dst, array $keys, ?array $weights = null, ?string $aggregate = null): Redis|int|false;

    /**
     * Ask the server for the XXH3 digest of a given key's value
     *
     * @param strinig $key The key to retrieve the digest for.
     * @return Redis|string|false The XXH3 digest as a string or false on failure.
     *
     * @see https://redis.io/docs/latest/commands/digest/
     *
     * @example
     * $redis->digest('session:42');
     *
     */
    public function digest(string $key): Redis|string|false;
}



class RedisException extends RuntimeException {}
