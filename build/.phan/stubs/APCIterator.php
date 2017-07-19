<?php
/**
 * The APCIterator class
 *
 * The APCIterator class makes it easier to iterate over large APC caches.
 * This is helpful as it allows iterating over large caches in steps, while grabbing a defined number
 * of entries per lock instance, so it frees the cache locks for other activities rather than hold up
 * the entire cache to grab 100 (the default) entries. Also, using regular expression matching is more
 * efficient as it's been moved to the C level.
 *
 * @link http://php.net/manual/en/class.apciterator.php
 */
class APCIterator implements Iterator
{
	/**
	 * Constructs an APCIterator iterator object
	 * @link http://php.net/manual/en/apciterator.construct.php
	 * @param string $cache The cache type, which will be 'user' or 'file'.
	 * @param string|string[]|null $search A PCRE regular expression that matches against APC key names,
	 * either as a string for a single regular expression, or as an array of regular expressions.
	 * Or, optionally pass in NULL to skip the search.
	 * @param int $format The desired format, as configured with one ore more of the APC_ITER_* constants.
	 * @param int $chunk_size The chunk size. Must be a value greater than 0. The default value is 100.
	 * @param int $list The type to list. Either pass in APC_LIST_ACTIVE  or APC_LIST_INACTIVE.
	 */
	public function __construct($cache, $search = null, $format = APC_ITER_ALL, $chunk_size = 100, $list = APC_LIST_ACTIVE){}

	/**
	 * Rewinds back the iterator to the first element
	 * @link http://php.net/manual/en/apciterator.rewind.php
	 */
	public function rewind(){}

	/**
	 * Checks if the current iterator position is valid
	 * @link http://php.net/manual/en/apciterator.valid.php
	 * @return bool Returns TRUE if the current iterator position is valid, otherwise FALSE.
	 */
	public function valid(){}

	/**
	 * Gets the current item from the APCIterator stack
	 * @link http://php.net/manual/en/apciterator.current.php
	 * @return mixed Returns the current item on success, or FALSE if no more items or exist, or on failure.
	 */
	public function current(){}

	/**
	 * Gets the current iterator key
	 * @link http://php.net/manual/en/apciterator.key.php
	 * @return string|int|bool Returns the key on success, or FALSE upon failure.
	 */
	public function key(){}

	/**
	 * Moves the iterator pointer to the next element
	 * @link http://php.net/manual/en/apciterator.next.php
	 * @return bool Returns TRUE on success or FALSE on failure.
	 */
	public function next(){}

	/**
	 * Gets the total number of cache hits
	 * @link http://php.net/manual/en/apciterator.gettotalhits.php
	 * @return int|bool The number of hits on success, or FALSE on failure.
	 */
	public function getTotalHits(){}

	/**
	 * Gets the total cache size
	 * @link http://php.net/manual/en/apciterator.gettotalsize.php
	 * @return int|bool The total cache size.
	 */
	public function getTotalSize(){}

	/**
	 * Get the total count
	 * @link http://php.net/manual/en/apciterator.gettotalcount.php
	 * @return int|bool The total count.
	 */
	public function getTotalCount(){}
}
