<?php

/**
 * League.Uri (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Uri\Contracts;

/**
 * @extends \IteratorAggregate<array{0:string, 1:string|null}>
 */
interface QueryInterface extends \Countable, \IteratorAggregate, UriComponentInterface
{
    /**
     * Returns the query separator.
     */
    public function getSeparator(): string;

    /**
     * Returns the number of key/value pairs present in the object.
     */
    public function count(): int;

    /**
     * Returns an iterator allowing to go through all key/value pairs contained in this object.
     *
     * The pair is represented as an array where the first value is the pair key
     * and the second value the pair value.
     *
     * The key of each pair is a string
     * The value of each pair is a scalar or the null value
     *
     * @return \Iterator<int, array{0:string, 1:string|null}>
     */
    public function getIterator(): \Iterator;

    /**
     * Returns an iterator allowing to go through all key/value pairs contained in this object.
     *
     * The return type is as a Iterator where its offset is the pair key and its value the pair value.
     *
     * The key of each pair is a string
     * The value of each pair is a scalar or the null value
     *
     * @return iterable<string, string|null>
     */
    public function pairs(): iterable;

    /**
     * Tells whether a pair with a specific name exists.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-has
     */
    public function has(string $key): bool;

    /**
     * Returns the first value associated to the given pair name.
     *
     * If no value is found null is returned
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-get
     */
    public function get(string $key): ?string;

    /**
     * Returns all the values associated to the given pair name as an array or all
     * the instance pairs.
     *
     * If no value is found an empty array is returned
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-getall
     *
     * @return array<int, string|null>
     */
    public function getAll(string $key): array;

    /**
     * Returns the store PHP variables as elements of an array.
     *
     * The result is similar as PHP parse_str when used with its
     * second argument with the difference that variable names are
     * not mangled.
     *
     * If a key is submitted it will returns the value attached to it or null
     *
     * @see http://php.net/parse_str
     * @see https://wiki.php.net/rfc/on_demand_name_mangling
     *
     * @param  ?string $key
     * @return mixed   the collection of stored PHP variables or the empty array if no input is given,
     *                     the single value of a stored PHP variable or null if the variable is not present in the collection
     */
    public function params(?string $key = null);

    /**
     * Returns the RFC1738 encoded query.
     */
    public function toRFC1738(): ?string;

    /**
     * Returns the RFC3986 encoded query.
     *
     * @see ::getContent
     */
    public function toRFC3986(): ?string;

    /**
     * Returns an instance with a different separator.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the query component with a different separator
     */
    public function withSeparator(string $separator): self;

    /**
     * Sorts the query string by offset, maintaining offset to data correlations.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the modified query
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-sort
     */
    public function sort(): self;

    /**
     * Returns an instance without duplicate key/value pair.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the query component normalized by removing
     * duplicate pairs whose key/value are the same.
     */
    public function withoutDuplicates(): self;

    /**
     * Returns an instance without empty key/value where the value is the null value.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the query component normalized by removing
     * empty pairs.
     *
     * A pair is considered empty if its value is equal to the null value
     */
    public function withoutEmptyPairs(): self;

    /**
     * Returns an instance where numeric indices associated to PHP's array like key are removed.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the query component normalized so that numeric indexes
     * are removed from the pair key value.
     *
     * ie.: toto[3]=bar[3]&foo=bar becomes toto[]=bar[3]&foo=bar
     */
    public function withoutNumericIndices(): self;

    /**
     * Returns an instance with the a new key/value pair added to it.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the modified query
     *
     * If the pair already exists the value will replace the existing value.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-set
     *
     * @param ?string $value
     */
    public function withPair(string $key, ?string $value): self;

    /**
     * Returns an instance with the new pairs set to it.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the modified query
     *
     * @see ::withPair
     */
    public function merge(string $query): self;

    /**
     * Returns an instance without the specified keys.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the modified component
     *
     * @param string ...$keys
     */
    public function withoutPair(string ...$keys): self;

    /**
     * Returns a new instance with a specified key/value pair appended as a new pair.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the modified query
     *
     * @param ?string $value
     */
    public function appendTo(string $key, ?string $value): self;

    /**
     * Returns an instance with the new pairs appended to it.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the modified query
     *
     * If the pair already exists the value will be added to it.
     */
    public function append(string $query): self;

    /**
     * Returns an instance without the specified params.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the modified component without PHP's value.
     * PHP's mangled is not taken into account.
     *
     * @param string ...$keys
     */
    public function withoutParam(string ...$keys): self;
}
