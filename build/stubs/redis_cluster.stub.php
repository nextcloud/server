<?php

/*
 * SPDX-FileCopyrightText: PHPRedis contributors <https://github.com/phpredis/phpredis>
 * SPDX-License-Identifier: PHP-3.01
 * Source: https://raw.githubusercontent.com/phpredis/phpredis/refs/heads/develop/redis_cluster.stub.php
 */

/**
 * @generate-function-entries
 * @generate-legacy-arginfo
 * @generate-class-entries
 */

class RedisCluster {
    /**
     * Used to configure how `PhpRedis` will failover to replica nodes when a
     * primary node fails to respond.
     *
     * @var int
     * @cvalue REDIS_OPT_FAILOVER
     *
     */
    public const OPT_SLAVE_FAILOVER = UNKNOWN;

    /**
     * Never read from replicas.
     *
     * @var int
     * @cvalue REDIS_FAILOVER_NONE
     *
     */
    public const FAILOVER_NONE = UNKNOWN;

    /**
     * Attempt to read from replicas when the primary errors out or is down.
     *
     * @var int
     * @cvalue REDIS_FAILOVER_ERROR
     *
     */
    public const FAILOVER_ERROR = UNKNOWN;

    /**
     * Distribute readonly commands at random between the primary and
     * replica(s).
     *
     * @var int
     * @cvalue REDIS_FAILOVER_DISTRIBUTE
     *
     */
    public const FAILOVER_DISTRIBUTE = UNKNOWN;

    /**
     * Distribute readonly commands between the replicas only.
     *
     * @var int
     * @cvalue REDIS_FAILOVER_DISTRIBUTE_SLAVES
     *
     */
    public const FAILOVER_DISTRIBUTE_SLAVES = UNKNOWN;

    public function __construct(string|null $name, ?array $seeds = null, int|float $timeout = 0, int|float $read_timeout = 0, bool $persistent = false, #[\SensitiveParameter] mixed $auth = null, ?array $context = null);

    /**
     * {@see \Redis::_compress()}
     */
    public function _compress(string $value): string;

    /**
     * @see \Redis::_uncompress()
     */
    public function _uncompress(string $value): string;

    /**
     * @see \Redis::_serialize()
     */
    public function _serialize(mixed $value): bool|string;

    /**
     * @see \Redis::_unserialize()
     */
    public function _unserialize(string $value): mixed;

    /**
     * @see \Redis::_pack()
     */
    public function _pack(mixed $value): string;

    /**
     * @see \Redis::_digest()
     */
    public function _digest(mixed $value): string;

    /**
     * @see \Redis::_unpack()
     */
    public function _unpack(string $value): mixed;

    /**
     * @see \Redis::_prefix()
     */
    public function _prefix(string $key): bool|string;

    public function _masters(): array;

    public function _redir(): string|null;

    /**
     * @see \Redis::acl()
     */
    public function acl(string|array $key_or_address, string $subcmd, string ...$args): mixed;

    /**
     * @see \Redis::append()
     */
    public function append(string $key, mixed $value): RedisCluster|bool|int;

    /**
     * @see \Redis::bgrewriteaof()
     */
    public function bgrewriteaof(string|array $key_or_address): RedisCluster|bool;

    /**
     * @see \Redis::wait()
     */
    public function wait(string|array $key_or_address, int $numreplicas, int $timeout): RedisCluster|int|false;

    /**
     * @see \Redis::waitaof()
     */
    public function waitaof(string|array $key_or_address, int $numlocal,
                            int $numreplicas, int $timeout): RedisCluster|array|false;

    /**
     * @see \Redis::bgSave()
     */
    public function bgsave(string|array $key_or_address): RedisCluster|bool;

    /**
     * @see \Redis::bitcount()
     */
    public function bitcount(string $key, int $start = 0, int $end = -1, bool $bybit = false): RedisCluster|bool|int;

    /**
     * @see \Redis::bitop()
     */
    public function bitop(string $operation, string $deskey, string $srckey, string ...$otherkeys): RedisCluster|bool|int;

    /**
     * Return the position of the first bit set to 0 or 1 in a string.
     *
     * @see https://https://redis.io/commands/bitpos/
     *
     * @param string $key   The key to check (must be a string)
     * @param bool   $bit   Whether to look for an unset (0) or set (1) bit.
     * @param int    $start Where in the string to start looking.
     * @param int    $end   Where in the string to stop looking.
     * @param bool   $bybit If true, Redis will treat $start and $end as BIT values and not bytes, so if start
     *                      was 0 and end was 2, Redis would only search the first two bits.
     */
    public function bitpos(string $key, bool $bit, int $start = 0, int $end = -1, bool $bybit = false): RedisCluster|int|false;

    /**
     * @see \Redis::blPop()
     */
    public function blpop(string|array $key, string|float|int $timeout_or_key, mixed ...$extra_args): RedisCluster|array|null|false;

    /**
     * @see \Redis::brPop()
     */
    public function brpop(string|array $key, string|float|int $timeout_or_key, mixed ...$extra_args): RedisCluster|array|null|false;

    /**
     * @see \Redis::brpoplpush()
     */
    public function brpoplpush(string $srckey, string $deskey, int $timeout): mixed;

    /**
     * Move an element from one list into another.
     *
     * @see \Redis::lMove()
     */
    public function lmove(string $src, string $dst, string $wherefrom, string $whereto): RedisCluster|string|false;

    /**
     * Move an element from one list to another, blocking up to a timeout until an element is available.
     *
     * @see \Redis::blmove()
     *
     */
    public function blmove(string $src, string $dst, string $wherefrom, string $whereto, float $timeout): RedisCluster|string|false;

    /**
     * @see \Redis::bzPopMax()
     */
    public function bzpopmax(string|array $key, string|int $timeout_or_key, mixed ...$extra_args): array;

    /**
     * @see \Redis::bzPopMin()
     */
    public function bzpopmin(string|array $key, string|int $timeout_or_key, mixed ...$extra_args): array;

    /**
     * @see \Redis::bzmpop()
     */
    public function bzmpop(float $timeout, array $keys, string $from, int $count = 1): RedisCluster|array|null|false;

    /**
     * @see \Redis::zmpop()
     */
    public function zmpop(array $keys, string $from, int $count = 1): RedisCluster|array|null|false;

    /**
     * @see \Redis::blmpop()
     */
    public function blmpop(float $timeout, array $keys, string $from, int $count = 1): RedisCluster|array|null|false;

    /**
     * @see \Redis::lmpop()
     */
    public function lmpop(array $keys, string $from, int $count = 1): RedisCluster|array|null|false;

    /**
     * @see \Redis::clearLastError()
     */
    public function clearlasterror(): bool;

    /**
     * @see \Redis::client()
     */
    public function client(string|array $key_or_address, string $subcommand, ?string $arg = null): array|string|bool;

    /**
     * @see \Redis::close()
     */
    public function close(): bool;

    /**
     * @see \Redis::cluster()
     */
    public function cluster(string|array $key_or_address, string $command, mixed ...$extra_args): mixed;

    /**
     * @see \Redis::command()
     */
    public function command(mixed ...$extra_args): mixed;

    /**
     * @see \Redis::config()
     */
    public function config(string|array $key_or_address, string $subcommand, mixed ...$extra_args): mixed;

    /**
     * @see \Redis::dbSize()
     */
    public function dbsize(string|array $key_or_address): RedisCluster|int;

    /**
     * @see https://redis.io/commands/copy
     */
    public function copy(string $src, string $dst, ?array $options = null): RedisCluster|bool;

    /**
     * @see \Redis::decr()
     */
    public function decr(string $key, int $by = 1): RedisCluster|int|false;

    /**
     * @see \Redis::decrBy()
     */
    public function decrby(string $key, int $value): RedisCluster|int|false;

    /**
     * @see \Redis::decrbyfloat()
     */
    public function decrbyfloat(string $key, float $value): float;

    /**
     * @see \Redis::del()
     */
    public function del(array|string $key, string ...$other_keys): RedisCluster|int|false;

    /**
     * Delete a key conditionally based on its value or hash digest
     *
     * @param string $key         The key to delete
     * @param array|null $options An array with options to modify how DELX works.
     *
     * @return RedisCluster|int|false Returns 1 if the key was deleted, 0 if it was not.
     */
    public function delex(string $key, ?array $options = null): RedisCluster|int|false;

    /**
     * Delete a key if it's equal to the specified value. This command is
     * specific to Valkey >= 9.0
     *
     * @param string $key   The key to delete
     * @param mixed  $value The value to compare against the key's value.
     * @return RedisCluster|int|false Returns 1 if the key was deleted, 0 if it was not.
     */
    public function delifeq(string $key, mixed $value): RedisCluster|int|false;

    /**
     * @see \Redis::discard()
     */
    public function discard(): bool;

    /**
     * @see \Redis::dump()
     */
    public function dump(string $key): RedisCluster|string|false;

    /**
     * @see \Redis::echo()
     */
    public function echo(string|array $key_or_address, string $msg): RedisCluster|string|false;

    /**
     * @see \Redis::eval()
     */
    public function eval(string $script, array $args = [], int $num_keys = 0): mixed;

    /**
     * @see \Redis::eval_ro()
     */
    public function eval_ro(string $script, array $args = [], int $num_keys = 0): mixed;

    /**
     * @see \Redis::evalsha()
     */
    public function evalsha(string $script_sha, array $args = [], int $num_keys = 0): mixed;

    /**
     * @see \Redis::evalsha_ro()
     */
    public function evalsha_ro(string $script_sha, array $args = [], int $num_keys = 0): mixed;

    /**
     * @see \Redis::exec()
     */
    public function exec(): array|false;

    /**
     * @see \Redis::exists()
     */
    public function exists(mixed $key, mixed ...$other_keys): RedisCluster|int|bool;

    /**
     * @see \Redis::touch()
     */
    public function touch(mixed $key, mixed ...$other_keys): RedisCluster|int|bool;

    /**
     * @see \Redis::expire()
     */
    public function expire(string $key, int $timeout, ?string $mode = null): RedisCluster|bool;

    /**
     * @see \Redis::expireAt()
     */
    public function expireat(string $key, int $timestamp, ?string $mode = null): RedisCluster|bool;

    /**
     * @see \Redis::expiretime()
     */
    public function expiretime(string $key): RedisCluster|int|false;

    /**
     * @see \Redis::pexpiretime()
     */
    public function pexpiretime(string $key): RedisCluster|int|false;

    /**
     * @see \Redis::flushAll()
     */
    public function flushall(string|array $key_or_address, bool $async = false): RedisCluster|bool;

    /**
     * @see \Redis::flushDB()
     */
    public function flushdb(string|array $key_or_address, bool $async = false): RedisCluster|bool;

    /**
     * @see \Redis::geoadd()
     */
    public function geoadd(string $key, float $lng, float $lat, string $member, mixed ...$other_triples_and_options): RedisCluster|int|false;

    /**
     * @see \Redis::geodist()
     */
    public function geodist(string $key, string $src, string $dest, ?string $unit = null): RedisCluster|float|false;

    /**
     * @see \Redis::geohash()
     */
    public function geohash(string $key, string $member, string ...$other_members): RedisCluster|array|false;

    /**
     * @see \Redis::geopos()
     */
    public function geopos(string $key, string $member, string ...$other_members): RedisCluster|array|false;

    /**
     * @see \Redis::georadius()
     */
    public function georadius(string $key, float $lng, float $lat, float $radius, string $unit, array $options = []): mixed;

    /**
     * @see \Redis::georadius_ro()
     */
    public function georadius_ro(string $key, float $lng, float $lat, float $radius, string $unit, array $options = []): mixed;

    /**
     * @see \Redis::georadiusbymember()
     */
    public function georadiusbymember(string $key, string $member, float $radius, string $unit, array $options = []): mixed;

    /**
     * @see \Redis::georadiusbymember_ro()
     */
    public function georadiusbymember_ro(string $key, string $member, float $radius, string $unit, array $options = []): mixed;

    /**
     * @see https://redis.io/commands/geosearch
     */
    public function geosearch(string $key, array|string $position, array|int|float $shape, string $unit, array $options = []): RedisCluster|array;

    /**
     * @see https://redis.io/commands/geosearchstore
     */
    public function geosearchstore(string $dst, string $src, array|string $position, array|int|float $shape, string $unit, array $options = []): RedisCluster|array|int|false;

    /**
     * @see \Redis::get()
     */
    public function get(string $key): mixed;

    /**
     * @see \Redis::getDel()
     */
    public function getdel(string $key): mixed;

    /**
     * @see \Redis::getWithMeta()
     */
    public function getWithMeta(string $key): RedisCluster|array|false;

    /**
     * @see \Redis::getEx()
     */
    public function getex(string $key, array $options = []): RedisCluster|string|false;

    /**
     * @see \Redis::getBit()
     */
    public function getbit(string $key, int $value): RedisCluster|int|false;

    /**
     * @see \Redis::getLastError()
     */
    public function getlasterror(): string|null;

    /**
     * @see \Redis::getMode()
     */
    public function getmode(): int;

    /**
     * @see \Redis::getOption()
     */
    public function getoption(int $option): mixed;

    /**
     * @see \Redis::getRange()
     */
    public function getrange(string $key, int $start, int $end): RedisCluster|string|false;

    /**
     * @see \Redis::lcs()
     */
    public function lcs(string $key1, string $key2, ?array $options = null): RedisCluster|string|array|int|false;

    /**
     * @see \Redis::getset()
     */
    public function getset(string $key, mixed $value): RedisCluster|string|bool;

    /**
     * @see \Redis::getTransferredBytes()
     */
    public function gettransferredbytes(): array|false;

    /**
     * @see \Redis::clearTransferredBytes()
     */
    public function cleartransferredbytes(): void;

    /**
     * @see \Redis::hDel()
     */
    public function hdel(string $key, string $member, string ...$other_members): RedisCluster|int|false;

    /**
     * @see \Redis::hExists()
     */
    public function hexists(string $key, string $member): RedisCluster|bool;

    /**
     * @see \Redis::hGet()
     */
    public function hget(string $key, string $member): mixed;

    /**
     * @see \Redis::hGetAll()
     */
    public function hgetall(string $key): RedisCluster|array|false;

    /**
     * @see \Redis::hGetWithMeta()
     */
    public function hgetWithMeta(string $key, string $member): mixed;

    /**
     * @see \Redis::hIncrBy()
     */
    public function hincrby(string $key, string $member, int $value): RedisCluster|int|false;

    /**
     * @see \Redis::hIncrByFloat()
     */
    public function hincrbyfloat(string $key, string $member, float $value): RedisCluster|float|false;

    /**
     * @see \Redis::hKeys()
     */
    public function hkeys(string $key): RedisCluster|array|false;

    /**
     * @see \Redis::hLen()
     */
    public function hlen(string $key): RedisCluster|int|false;

    /**
     * @see \Redis::hMget()
     */
    public function hmget(string $key, array $keys): RedisCluster|array|false;

    /**
     * @see \Redis::hgetex()
     */
    public function hgetex(string $key, array $fields, string|array|null $expiry = null): RedisCluster|array|false;

    /**
     * @see \Redis::hsetex()
     */
    public function hsetex(string $key, array $fields, ?array $expiry = null): RedisCluster|int|false;

    /**
     * @see \Redis::hgetdel()
     */
    public function hgetdel(string $key, array $fields): RedisCluster|array|false;

    /**
     * @see \Redis::hMset()
     */
    public function hmset(string $key, array $key_values): RedisCluster|bool;

    /**
     * @see \Redis::hscan()
     */
    public function hscan(string $key, null|int|string &$iterator, ?string $pattern = null, int $count = 0): array|bool;

    /**
     * @see \Redis::expiremember()
     */
    public function expiremember(string $key, string $field, int $ttl, ?string $unit = null): RedisCluster|int|false;

    /**
     * @see \Redis::expirememberat()
     */
    public function expirememberat(string $key, string $field, int $timestamp): RedisCluster|int|false;

    /**
     * @see https://redis.io/commands/hrandfield
     */
    public function hrandfield(string $key, ?array $options = null): RedisCluster|string|array;

    /**
     * @see \Redis::hSet()
     */
    public function hset(string $key, string $member, mixed $value): RedisCluster|int|false;

    /**
     * @see \Redis::hSetNx()
     */
    public function hsetnx(string $key, string $member, mixed $value): RedisCluster|bool;

    /**
     * @see \Redis::hStrLen()
     */
    public function hstrlen(string $key, string $field): RedisCluster|int|false;

    /**
     * @see \Redis::hexpire()
     */
    public function hexpire(string $key, int $ttl, array $fields,
                            ?string $mode = NULL): RedisCluster|array|false;

    /**
     * @see \Redis::hpexpire()
     */
    public function hpexpire(string $key, int $ttl, array $fields,
                            ?string $mode = NULL): RedisCluster|array|false;

    /**
     * @see \Redis::hexpireat()
     */
    public function hexpireat(string $key, int $time, array $fields,
                              ?string $mode = NULL): RedisCluster|array|false;

    /**
     * @see \Redis::hpexpireat()
     */
    public function hpexpireat(string $key, int $mstime, array $fields,
                               ?string $mode = NULL): RedisCluster|array|false;

    /**
     * @see \Redis::httl()
     */
    public function httl(string $key, array $fields): RedisCluster|array|false;

    /**
     * @see \Redis::hpttl()
     */
    public function hpttl(string $key, array $fields): RedisCluster|array|false;

    /**
     * @see \Redis::hexpiretime()
     */
    public function hexpiretime(string $key, array $fields): RedisCluster|array|false;

    /**
     * @see \Redis::hpexpiretime()
     */
    public function hpexpiretime(string $key, array $fields): RedisCluster|array|false;

    /**
     * @see \Redis::hpexpiretime()
     */
    public function hpersist(string $key, array $fields): RedisCluster|array|false;

    /**
     * @see \Redis::hVals()
     */
    public function hvals(string $key): RedisCluster|array|false;

    /**
     * @see \Redis::incr()
     */
    public function incr(string $key, int $by = 1): RedisCluster|int|false;

    /**
     * @see \Redis::incrBy()
     */
    public function incrby(string $key, int $value): RedisCluster|int|false;

    /**
     * @see \Redis::incrByFloat()
     */
    public function incrbyfloat(string $key, float $value): RedisCluster|float|false;

    /**
     * Retrieve information about the connected redis-server.  If no arguments are passed to
     * this function, redis will return every info field.  Alternatively you may pass a specific
     * section you want returned (e.g. 'server', or 'memory') to receive only information pertaining
     * to that section.
     *
     * If connected to Redis server >= 7.0.0 you may pass multiple optional sections.
     *
     * @see https://redis.io/commands/info/
     *
     * @param string|array $key_or_address Either a key name or array with host and port indicating
     *                                     which cluster node we want to send the command to.
     * @param string       $sections       Optional section(s) you wish Redis server to return.
     *
     * @return RedisCluster|array|false
     */
    public function info(string|array $key_or_address, string ...$sections): RedisCluster|array|false;

    /**
     * @see \Redis::keys()
     */
    public function keys(string $pattern): RedisCluster|array|false;

    /**
     * @see \Redis::lastSave()
     */
    public function lastsave(string|array $key_or_address): RedisCluster|int|false;

    /**
     * @see \Redis::lget()
     */
    public function lget(string $key, int $index): RedisCluster|string|bool;

    /**
     * @see \Redis::lindex()
     */
    public function lindex(string $key, int $index): mixed;

    /**
     * @see \Redis::lInsert()
     */
    public function linsert(string $key, string $pos, mixed $pivot, mixed $value): RedisCluster|int|false;

    /**
     * @see \Redis::lLen()
     */
    public function llen(string $key): RedisCluster|int|bool;

    /**
     * @see \Redis::lPop()
     */
    public function lpop(string $key, int $count = 0): RedisCluster|bool|string|array;

    /**
     * @see \Redis::lPos()
     */
    public function lpos(string $key, mixed $value, ?array $options = null): RedisCluster|null|bool|int|array;

    /**
     * @see \Redis::lPush()
     */
    public function lpush(string $key, mixed $value, mixed ...$other_values): RedisCluster|int|bool;

    /**
     * @see \Redis::lPushx()
     */
    public function lpushx(string $key, mixed $value): RedisCluster|int|bool;

    /**
     * @see \Redis::lrange()
     */
    public function lrange(string $key, int $start, int $end): RedisCluster|array|false;

    /**
     * @see \Redis::lrem()
     */
    public function lrem(string $key, mixed $value, int $count = 0): RedisCluster|int|bool;

    /**
     * @see \Redis::lSet()
     */
    public function lset(string $key, int $index, mixed $value): RedisCluster|bool;

    /**
     * @see \Redis::ltrim()
     */
    public function ltrim(string $key, int $start, int $end): RedisCluster|bool;

    /**
     * @see \Redis::mget()
     */
    public function mget(array $keys): RedisCluster|array|false;

    /**
     * @see \Redis::mset()
     */
    public function mset(array $key_values): RedisCluster|bool;

    /**
     * @see \Redis::msetnx()
     */
    public function msetnx(array $key_values): RedisCluster|array|false;

    /**
     * @see \Redis::msetex()
     */
    public function msetex(array $key_vals, int|float|array|null $expiry = null): Redis|int|false;

    /* We only support Redis::MULTI in RedisCluster but take the argument
       so we can test MULTI..EXEC with RedisTest.php and in the event
       we add pipeline support in the future. */
    public function multi(int $value = Redis::MULTI): RedisCluster|bool;

    /**
     * @see \Redis::object()
     */
    public function object(string $subcommand, string $key): RedisCluster|int|string|false;

    /**
     * @see \Redis::persist()
     */
    public function persist(string $key): RedisCluster|bool;

    /**
     * @see \Redis::pexpire()
     */
    public function pexpire(string $key, int $timeout, ?string $mode = null): RedisCluster|bool;

    /**
     * @see \Redis::pexpireAt()
     */
    public function pexpireat(string $key, int $timestamp, ?string $mode = null): RedisCluster|bool;


    /**
     * @see \Redis::pfadd()
     */
    public function pfadd(string $key, array $elements): RedisCluster|bool;

    /**
     * @see \Redis::pfcount()
     */
    public function pfcount(string $key): RedisCluster|int|false;

    /**
     * @see \Redis::pfmerge()
     */
    public function pfmerge(string $key, array $keys): RedisCluster|bool;

    /**
     * PING an instance in the redis cluster.
     *
     * @see \Redis::ping()
     *
     * @param string|array $key_or_address Either a key name or a two element array with host and
     *                                     address, informing RedisCluster which node to ping.
     *
     * @param string|null  $message        An optional message to send.
     *
     * @return mixed This method always returns `true` if no message was sent, and the message itself
     *               if one was.
     */
    public function ping(string|array $key_or_address, ?string $message = null): mixed;

    /**
     * @see \Redis::psetex()
     */
    public function psetex(string $key, int $timeout, string $value): RedisCluster|bool;

    /**
     * @see \Redis::psubscribe()
     */
    public function psubscribe(array $patterns, callable $callback): void;

    /**
     * @see \Redis::pttl()
     */
    public function pttl(string $key): RedisCluster|int|false;

    /**
     * @see \Redis::publish()
     */
    public function publish(string $channel, string $message): RedisCluster|bool|int;

    /**
     * @see \Redis::pubsub()
     */
    public function pubsub(string|array $key_or_address, string ...$values): mixed;

    /**
     * @see \Redis::punsubscribe()
     */
    public function punsubscribe(string $pattern, string ...$other_patterns): bool|array;

    /**
     * @see \Redis::randomKey()
     */
    public function randomkey(string|array $key_or_address): RedisCluster|bool|string;

    /**
     * @see \Redis::rawcommand()
     */
    public function rawcommand(string|array $key_or_address, string $command, mixed ...$args): mixed;

    /**
     * @see \Redis::rename()
     */
    public function rename(string $key_src, string $key_dst): RedisCluster|bool;

    /**
     * @see \Redis::renameNx()
     */
    public function renamenx(string $key, string $newkey): RedisCluster|bool;

    /**
     * @see \Redis::restore()
     */
    public function restore(string $key, int $timeout, string $value, ?array $options = null): RedisCluster|bool;

    /**
     * @see \Redis::role()
     */
    public function role(string|array $key_or_address): mixed;

    /**
     * @see \Redis::rPop()
     */
    public function rpop(string $key, int $count = 0): RedisCluster|bool|string|array;

    /**
     * @see \Redis::rpoplpush()
     */
    public function rpoplpush(string $src, string $dst): RedisCluster|bool|string;

    /**
     * @see \Redis::rPush()
     */
    public function rpush(string $key, mixed ...$elements): RedisCluster|int|false;

    /**
     * @see \Redis::rPushx()
     */
    public function rpushx(string $key, string $value): RedisCluster|bool|int;

    /**
     * @see \Redis::sAdd()
     */
    public function sadd(string $key, mixed $value, mixed ...$other_values): RedisCluster|int|false;

    /**
     * @see \Redis::sAddArray()
     */
    public function saddarray(string $key, array $values): RedisCluster|bool|int;

    /**
     * @see \Redis::save()
     */
    public function save(string|array $key_or_address): RedisCluster|bool;

    /**
     * @see \Redis::scan()
     */
    public function scan(null|int|string &$iterator, string|array $key_or_address, ?string $pattern = null, int $count = 0): bool|array;

    /**
     * @see \Redis::scard()
     */
    public function scard(string $key): RedisCluster|int|false;

    /**
     * @see \Redis::script()
     */
    public function script(string|array $key_or_address, mixed ...$args): mixed;

    /**
     * @see \Redis::sDiff()
     */
    public function sdiff(string $key, string ...$other_keys): RedisCluster|array|false;

    /**
     * @see \Redis::sDiffStore()
     */
    public function sdiffstore(string $dst, string $key, string ...$other_keys): RedisCluster|int|false;

    /**
     * @see https://redis.io/commands/set
     */
    public function set(string $key, mixed $value, mixed $options = null): RedisCluster|string|bool;

    /**
     * @see \Redis::setBit()
     */
    public function setbit(string $key, int $offset, bool $onoff): RedisCluster|int|false;

    /**
     * @see \Redis::setex()
     */
    public function setex(string $key, int $expire, mixed $value): RedisCluster|bool;

    /**
     * @see \Redis::setnx()
     */
    public function setnx(string $key, mixed $value): RedisCluster|bool;

    /**
     * @see \Redis::setOption()
     */
    public function setoption(int $option, mixed $value): bool;

    /**
     * @see \Redis::setRange()
     */
    public function setrange(string $key, int $offset, string $value): RedisCluster|int|false;

    /**
     * @see \Redis::sInter()
     */
    public function sinter(array|string $key, string ...$other_keys): RedisCluster|array|false;

    /**
     * @see \Redis::sintercard()
     */
    public function sintercard(array $keys, int $limit = -1): RedisCluster|int|false;

    /**
     * @see \Redis::sunioncard()
     */
    public function sunioncard(array $keys, ?array $options = null): RedisCluster|int|false;

    /**
     * @see \Redis::sdiffcard()
     */
    public function sdiffcard(array $keys, ?array $options = null): RedisCluster|int|false;

    /**
     * @see \Redis::sInterStore()
     */
    public function sinterstore(array|string $key, string ...$other_keys): RedisCluster|int|false;

    /**
     * @see \Redis::sismember()
     */
    public function sismember(string $key, mixed $value): RedisCluster|bool;

    /**
     * @see \Redis::sMisMember()
     */
    public function smismember(string $key, string $member, string ...$other_members): RedisCluster|array|false;

    /**
     * @see \Redis::slowlog()
     */
    public function slowlog(string|array $key_or_address, mixed ...$args): mixed;

    /**
     * @see \Redis::sMembers()
     */
    public function smembers(string $key): RedisCluster|array|false;

    /**
     * @see \Redis::sMove()
     */
    public function smove(string $src, string $dst, string $member): RedisCluster|bool;

    /**
     * @see \Redis::sort()
     */
    public function sort(string $key, ?array $options = null): RedisCluster|array|bool|int|string;

    /**
     * @see \Redis::sort_ro()
     */
    public function sort_ro(string $key, ?array $options = null): RedisCluster|array|bool|int|string;

    /**
     * @see \Redis::sPop()
     */
    public function spop(string $key, int $count = 0): RedisCluster|string|array|false;

    /**
     * @see \Redis::sRandMember()
     */
    public function srandmember(string $key, int $count = 0): RedisCluster|string|array|false;

    /**
     * @see \Redis::srem()
     */
    public function srem(string $key, mixed $value, mixed ...$other_values): RedisCluster|int|false;

    /**
     * @see \Redis::sscan()
     */
    public function sscan(string $key, null|int|string &$iterator, ?string $pattern = null, int $count = 0): array|false;

    /**
     * @see \Redis::strlen()
     */
    public function strlen(string $key): RedisCluster|int|false;

    /**
     * @see \Redis::subscribe()
     */
    public function subscribe(array $channels, callable $cb): void;

    /**
     * @see \Redis::sUnion()
     */
    public function sunion(string $key, string ...$other_keys): RedisCluster|bool|array;

    /**
     * @see \Redis::sUnionStore()
     */
    public function sunionstore(string $dst, string $key, string ...$other_keys): RedisCluster|int|false;

    /**
     * @see \Redis::time()
     */
    public function time(string|array $key_or_address): RedisCluster|bool|array;

    /**
     * @see \Redis::ttl()
     */
    public function ttl(string $key): RedisCluster|int|false;

    /**
     * @see \Redis::type()
     */
    public function type(string $key): RedisCluster|int|false;

    /**
     * @see \Redis::unsubscribe()
     */
    public function unsubscribe(array $channels): bool|array;

    /**
     * @see \Redis::unlink()
     */
    public function unlink(array|string $key, string ...$other_keys): RedisCluster|int|false;

    /**
     * @see \Redis::unwatch()
     */
    public function unwatch(): bool;

    /**
     * @see \Redis::watch()
     */
    public function watch(string $key, string ...$other_keys): RedisCluster|bool;

    /**
     * @see \Redis::vadd()
     */
    public function vadd(string $key, array $values, mixed $element, array|null $options = null): RedisCluster|int|false;

    /**
     * @see \Redis::vsim()
     */
    public function vsim(string $key, mixed $member, array|null $options = null): RedisCluster|array|false;

    /**
     * @see \Redis::vcard()
     */
    public function vcard(string $key): RedisCluster|int|false;

    /**
     * @see \Redis::vdim()
     */
    public function vdim(string $key): RedisCluster|int|false;

    /**
     * @see \Redis::vinfo()
     */
    public function vinfo(string $key): RedisCluster|array|false;

    /**
     * Check if an element is a member of a vectorset
     *
     * @param string $key    The vector set to query.
     * @param mixed  $member The member to check for.
     *
     * @return RedisCluster|bool true if the member exists, false if it does not.
     */
    public function vismember(string $key, mixed $member): RedisCluster|bool;

    /**
     * @see \Redis::vemb()
     */
    public function vemb(string $key, mixed $member, bool $raw = false): RedisCluster|array|false;

    /**
     * @see \Redis::vrandmember()
     */
    public function vrandmember(string $key, int $count = 0): RedisCluster|array|string|false;

    /**
     * Retreive a lexographical range of elements from a vector set
     *
     * @param string $key        The vector set to query.
     * @param string $min        The minimum element to return.
     * @param string $max        The maximum element to return.
     * @param int    $count      An optional maximum number of elements to return.
     *
     * @return RedisCluster|array|false An array of elements in the specified range.`
     */
    public function vrange(string $key, string $min, string $max, int $count = -1): RedisCluster|array|false;


    /**
     * @see \Redis::vrem()
     */
    public function vrem(string $key, mixed $member): RedisCluster|int|false;

    /**
     * @see \Redis::vlinks()
     */
    public function vlinks(string $key, mixed $member, bool $withscores = false): RedisCluster|array|false;

    /**
     * @see \Redis::vgetattr()
     */
    public function vgetattr(string $key, mixed $member, bool $decode = true): RedisCluster|array|string|false;

    /**
     * @see \Redis::vsetattr()
     */
    public function vsetattr(string $key, mixed $member, array|string $attributes): RedisCluster|int|false;

    /**
     * @see \Redis::gcra()
     */
    public function gcra(string $key, int $maxBurst, int $requestsPerPeriod,
                         int $period, int $tokens = 0): RedisCluster|array|false;


    /**
     * @see \Redis::xack()
     */
    public function xack(string $key, string $group, array $ids): RedisCluster|int|false;

    /**
     * @see \Redis::xadd()
     */
    public function xadd(string $key, string $id, array $values, int $maxlen = 0, bool $approx = false): RedisCluster|string|false;

    /**
     * @see \Redis::xclaim()
     */
    public function xclaim(string $key, string $group, string $consumer, int $min_iddle, array $ids, array $options): RedisCluster|string|array|false;

    /**
     * @see \Redis::xdel()
     */
    public function xdel(string $key, array $ids): RedisCluster|int|false;

    /**
     * @see \Redis::xdelex()
     */
    public function xdelex(string $key, array $ids, ?string $mode = null): RedisCluster|array|false;

    /**
     * @see \Redis::xgroup()
     */
    public function xgroup(string $operation, ?string $key = null, ?string $group = null, ?string $id_or_consumer = null,
                           bool $mkstream = false, int $entries_read = -2): mixed;

    /**
     * @see \Redis::xautoclaim()
     */
    public function xautoclaim(string $key, string $group, string $consumer, int $min_idle, string $start, int $count = -1, bool $justid = false): RedisCluster|bool|array;

    /**
     * @see \Redis::xinfo()
     */
    public function xinfo(string $operation, ?string $arg1 = null, ?string $arg2 = null, int $count = -1): mixed;

    /**
     * @see \Redis::xlen()
     */
    public function xlen(string $key): RedisCluster|int|false;

    /**
     * @see \Redis::xpending()
     */
    public function xpending(string $key, string $group, ?string $start = null, ?string $end = null, int $count = -1, ?string $consumer = null): RedisCluster|array|false;

    /**
     * @see \Redis::xrange()
     */
    public function xrange(string $key, string $start, string $end, int $count = -1): RedisCluster|bool|array;

    /**
     * @see \Redis::xread()
     */
    public function xread(array $streams, int $count = -1, int $block = -1): RedisCluster|bool|array;

    /**
     * @see \Redis::xreadgroup()
     */
    public function xreadgroup(string $group, string $consumer, array $streams, int $count = 1, int $block = 1): RedisCluster|bool|array;

    /**
     * @see \Redis::xrevrange()
     */
    public function xrevrange(string $key, string $start, string $end, int $count = -1): RedisCluster|bool|array;

    /**
     * @see \Redis::xtrim()
     */
    public function xtrim(string $key, int $maxlen, bool $approx = false, bool $minid = false, int $limit = -1): RedisCluster|int|false;

    /**
     * @see \Redis::zAdd()
     */
    public function zadd(string $key, array|float $score_or_options, mixed ...$more_scores_and_mems): RedisCluster|int|float|false;

    /**
     * @see \Redis::zCard()
     */
    public function zcard(string $key): RedisCluster|int|false;

    /**
     * @see \Redis::zCount()
     */
    public function zcount(string $key, string $start, string $end): RedisCluster|int|false;

    /**
     * @see \Redis::zIncrBy()
     */
    public function zincrby(string $key, float $value, string $member): RedisCluster|float|false;

    /**
     * @see \Redis::zinterstore()
     */
    public function zinterstore(string $dst, array $keys, ?array $weights = null, ?string $aggregate = null): RedisCluster|int|false;

    /**
     * @see \Redis::zintercard()
     */
    public function zintercard(array $keys, int $limit = -1): RedisCluster|int|false;

    /**
     * @see \Redis::zLexCount()
     */
    public function zlexcount(string $key, string $min, string $max): RedisCluster|int|false;

    /**
     * @see \Redis::zPopMax()
     */
    public function zpopmax(string $key, ?int $value = null): RedisCluster|bool|array;

    /**
     * @see \Redis::zPopMin()
     */
    public function zpopmin(string $key, ?int $value = null): RedisCluster|bool|array;

    /**
     * @see \Redis::zRange()
     */
    public function zrange(string $key, mixed $start, mixed $end, array|bool|null $options = null): RedisCluster|array|bool;

    /**
     * @see \Redis::zrangestore()
     */
    public function zrangestore(string $dstkey, string $srckey, int $start, int $end,
                                array|bool|null $options = null): RedisCluster|int|false;

    /**
     * @see https://redis.io/commands/zrandmember
     */
    public function zrandmember(string $key, ?array $options = null): RedisCluster|string|array;

    /**
     * @see \Redis::zRangeByLex()
     */
    public function zrangebylex(string $key, string $min, string $max, int $offset = -1, int $count = -1): RedisCluster|array|false;

    /**
     * @see \Redis::zRangeByScore()
     */
    public function zrangebyscore(string $key, string $start, string $end, array $options = []): RedisCluster|array|false;

    /**
     * @see \Redis::zRank()
     */
    public function zrank(string $key, mixed $member): RedisCluster|int|false;

    /**
     * @see \Redis::zRem()
     */
    public function zrem(string $key, string $value, string ...$other_values): RedisCluster|int|false;

    /**
     * @see \Redis::zRemRangeByLex()
     */
    public function zremrangebylex(string $key, string $min, string $max): RedisCluster|int|false;

    /**
     * @see \Redis::zRemRangeByRank()
     */
    public function zremrangebyrank(string $key, string $min, string $max): RedisCluster|int|false;

    /**
     * @see \Redis::zRemRangeByScore()
     */
    public function zremrangebyscore(string $key, string $min, string $max): RedisCluster|int|false;

    /**
     * @see \Redis::zRevRange()
     */
    public function zrevrange(string $key, string $min, string $max, ?array $options = null): RedisCluster|bool|array;

    /**
     * @see \Redis::zRevRangeByLex()
     */
    public function zrevrangebylex(string $key, string $min, string $max, ?array $options = null): RedisCluster|bool|array;

    /**
     * @see \Redis::zRevRangeByScore()
     */
    public function zrevrangebyscore(string $key, string $min, string $max, ?array $options = null): RedisCluster|bool|array;

    /**
     * @see \Redis::zRevRank()
     */
    public function zrevrank(string $key, mixed $member): RedisCluster|int|false;

    /**
     * @see \Redis::zscan()
     */
    public function zscan(string $key, null|int|string &$iterator, ?string $pattern = null, int $count = 0): RedisCluster|bool|array;

    /**
     * @see \Redis::zScore()
     */
    public function zscore(string $key, mixed $member): RedisCluster|float|false;

    /**
     * @see https://redis.io/commands/zmscore
     */
    public function zmscore(string $key, mixed $member, mixed ...$other_members): RedisCluster|array|false;

    /**
     * @see \Redis::zunionstore()
     */
    public function zunionstore(string $dst, array $keys, ?array $weights = null, ?string $aggregate = null): RedisCluster|int|false;

    /**
     * @see https://redis.io/commands/zinter
     */
    public function zinter(array $keys, ?array $weights = null, ?array $options = null): RedisCluster|array|false;

    /**
     * @see https://redis.io/commands/zdiffstore
     */
    public function zdiffstore(string $dst, array $keys): RedisCluster|int|false;

    /**
     * @see https://redis.io/commands/zunion
     */
    public function zunion(array $keys, ?array $weights = null, ?array $options = null): RedisCluster|array|false;

    /**
     * @see https://redis.io/commands/zdiff
     */
    public function zdiff(array $keys, ?array $options = null): RedisCluster|array|false;

    /**
     * @see https://redis.io/commands/digest
     */
    public function digest(string $key): RedisCluster|string|false;
}

class RedisClusterException extends RuntimeException {}
