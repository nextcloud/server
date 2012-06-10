<?php
/*
 * Copyright 2010-2012 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 *  http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */


/*%******************************************************************************************%*/
// CLASS

/**
 * The <CFArray> object extends PHP's built-in <php:ArrayObject> object by providing convenience methods for
 * rapidly manipulating array data. Specifically, the `CFArray` object is intended for working with
 * <CFResponse> and <CFSimpleXML> objects that are returned by AWS services.
 *
 * @version 2012.01.17
 * @license See the included NOTICE.md file for more information.
 * @copyright See the included NOTICE.md file for more information.
 * @link http://aws.amazon.com/php/ PHP Developer Center
 * @link http://php.net/ArrayObject ArrayObject
 */
class CFArray extends ArrayObject
{
	/**
	 * Constructs a new instance of <CFArray>.
	 *
	 * @param mixed $input (Optional) The input parameter accepts an array or an Object. The default value is an empty array.
	 * @param integer $flags (Optional) Flags to control the behavior of the ArrayObject object. Defaults to <STD_PROP_LIST>.
	 * @param string $iterator_class (Optional) Specify the class that will be used for iteration of the <php:ArrayObject> object. <php:ArrayIterator> is the default class used.
	 * @return mixed Either an array of matches, or a single <CFSimpleXML> element.
	 */
	public function __construct($input = array(), $flags = self::STD_PROP_LIST, $iterator_class = 'ArrayIterator')
	{
		// Provide a default value
		$input = $input ? $input : array();

		try {
			return parent::__construct($input, $flags, $iterator_class);
		}
		catch (InvalidArgumentException $e)
		{
			throw new CFArray_Exception($e->getMessage());
		}
	}

	/**
	 * Alternate approach to constructing a new instance. Supports chaining.
	 *
	 * @param mixed $input (Optional) The input parameter accepts an array or an Object. The default value is an empty array.
	 * @param integer $flags (Optional) Flags to control the behavior of the ArrayObject object. Defaults to <STD_PROP_LIST>.
	 * @param string $iterator_class (Optional) Specify the class that will be used for iteration of the <php:ArrayObject> object. <php:ArrayIterator> is the default class used.
	 * @return mixed Either an array of matches, or a single <CFSimpleXML> element.
	 */
	public static function init($input = array(), $flags = self::STD_PROP_LIST, $iterator_class = 'ArrayIterator')
	{
		if (version_compare(PHP_VERSION, '5.3.0', '<'))
		{
			throw new Exception('PHP 5.3 or newer is required to instantiate a new class with CLASS::init().');
		}

		$self = get_called_class();
		return new $self($input, $flags, $iterator_class);
	}

	/**
	 * Handles how the object is rendered when cast as a string.
	 *
	 * @return string The word "Array".
	 */
	public function __toString()
	{
		return 'Array';
	}


	/*%******************************************************************************************%*/
	// REFORMATTING

	/**
	 * Maps each element in the <CFArray> object as an integer.
	 *
	 * @return array The contents of the <CFArray> object mapped as integers.
	 */
	public function map_integer()
	{
		return array_map('intval', $this->getArrayCopy());
	}

	/**
	 * Maps each element in the CFArray object as a string.
	 *
	 * @param string $pcre (Optional) A Perl-Compatible Regular Expression (PCRE) to filter the names against.
	 * @return array The contents of the <CFArray> object mapped as strings. If there are no results, the method will return an empty array.
	 */
	public function map_string($pcre = null)
	{
		$list = array_map('strval', $this->getArrayCopy());
		$dlist = array();

		if ($pcre)
		{
			foreach ($list as $item)
			{
				$dlist[] = preg_match($pcre, $item) ? $item : null;
			}

			$list = array_values(array_filter($dlist));
		}

		return $list;
	}


	/*%******************************************************************************************%*/
	// CONFIRMATION

	/**
	 * Verifies that _all_ responses were successful. A single failed request will cause <areOK()> to return false. Equivalent to <CFResponse::isOK()>, except it applies to all responses.
	 *
	 * @return boolean Whether _all_ requests were successful or not.
	 */
	public function areOK()
	{
		$dlist = array();
		$list = $this->getArrayCopy();

		foreach ($list as $response)
		{
			if ($response instanceof CFResponse)
			{
				$dlist[] = $response->isOK();
			}
		}

		return (array_search(false, $dlist, true) !== false) ? false : true;
	}


	/*%******************************************************************************************%*/
	// ITERATING AND EXECUTING

	/**
	 * Iterates over a <CFArray> object, and executes a function for each matched element.
	 *
	 * The callback function takes three parameters: <ul>
	 * 	<li><code>$item</code> - <code>mixed</code> - Optional - The individual node in the array.</li>
	 * 	<li><code>$key</code> - <code>mixed</code> - Optional - The key for the array node.</li>
	 * 	<li><code>$bind</code> - <code>mixed</code> - Optional - The variable that was passed into the $bind parameter.</li></ul>
	 *
	 * @param string|function $callback (Required) The callback function to execute. PHP 5.3 or newer can use an anonymous function.
	 * @param mixed $bind (Optional) A variable from the calling scope to pass-by-reference into the local scope of the callback function.
	 * @return CFArray The original <CFArray> object.
	 */
	public function each($callback, &$bind = null)
	{
		$items = $this->getArrayCopy();

		foreach ($items as $key => &$item)
		{
			$callback($item, $key, $bind);
		}

		return $this;
	}

	/**
	 * Passes each element in the current <CFArray> object through a function, and produces a new <CFArray> object containing the return values.
	 *
	 * The callback function takes three parameters: <ul>
	 * 	<li><code>$item</code> - <code>mixed</code> - Optional - The individual node in the array.</li>
	 * 	<li><code>$key</code> - <code>mixed</code> - Optional - The key for the array node.</li>
	 * 	<li><code>$bind</code> - <code>mixed</code> - Optional - The variable that was passed into the $bind parameter.</li></ul>
	 *
	 * @param string|function $callback (Required) The callback function to execute. PHP 5.3 or newer can use an anonymous function.
	 * @param mixed $bind (Optional) A variable from the calling scope to pass-by-reference into the local scope of the callback function.
	 * @return CFArray A new <CFArray> object containing the return values.
	 */
	public function map($callback, &$bind = null)
	{
		$items = $this->getArrayCopy();
		$collect = array();

		foreach ($items as $key => &$item)
		{
			$collect[] = $callback($item, $key, $bind);
		}

		return new CFArray($collect);
	}

	/**
	 * Filters the list of nodes by passing each value in the current <CFArray> object through a function. The node will be removed if the function returns `false`.
	 *
	 * The callback function takes three parameters: <ul>
	 * 	<li><code>$item</code> - <code>mixed</code> - Optional - The individual node in the array.</li>
	 * 	<li><code>$key</code> - <code>mixed</code> - Optional - The key for the array node.</li>
	 * 	<li><code>$bind</code> - <code>mixed</code> - Optional - The variable that was passed into the $bind parameter.</li></ul>
	 *
	 * @param string|function $callback (Required) The callback function to execute. PHP 5.3 or newer can use an anonymous function.
	 * @param mixed $bind (Optional) A variable from the calling scope to pass-by-reference into the local scope of the callback function.
	 * @return CFArray A new <CFArray> object containing the return values.
	 */
	public function filter($callback, &$bind = null)
	{
		$items = $this->getArrayCopy();
		$collect = array();

		foreach ($items as $key => &$item)
		{
			if ($callback($item, $key, $bind) !== false)
			{
				$collect[] = $item;
			}
		}

		return new CFArray($collect);
	}

	/**
	 * Alias for <filter()>. This functionality was incorrectly named _reduce_ in earlier versions of the SDK.
	 *
	 * @param string|function $callback (Required) The callback function to execute. PHP 5.3 or newer can use an anonymous function.
	 * @param mixed $bind (Optional) A variable from the calling scope to pass-by-reference into the local scope of the callback function.
	 * @return CFArray A new <CFArray> object containing the return values.
	 */
	public function reduce($callback, &$bind = null)
	{
		return $this->filter($callback, $bind);
	}


	/*%******************************************************************************************%*/
	// TRAVERSAL

	/**
	 * Gets the first result in the array.
	 *
	 * @return mixed The first result in the <CFArray> object. Returns `false` if there are no items in the array.
	 */
	public function first()
	{
		$items = $this->getArrayCopy();
		return count($items) ? $items[0] : false;
	}

	/**
	 * Gets the last result in the array.
	 *
	 * @return mixed The last result in the <CFArray> object. Returns `false` if there are no items in the array.
	 */
	public function last()
	{
		$items = $this->getArrayCopy();
		return count($items) ? end($items) : false;
	}

	/**
	 * Removes all `null` values from an array.
	 *
	 * @return CFArray A new <CFArray> object containing the non-null values.
	 */
	public function compress()
	{
		return new CFArray(array_filter($this->getArrayCopy()));
	}

	/**
	 * Reindexes the array, starting from zero.
	 *
	 * @return CFArray A new <CFArray> object with indexes starting at zero.
	 */
	public function reindex()
	{
		return new CFArray(array_values($this->getArrayCopy()));
	}


	/*%******************************************************************************************%*/
	// ALTERNATE FORMATS

	/**
	 * Gets the current XML node as a JSON string.
	 *
	 * @return string The current XML node as a JSON string.
	 */
	public function to_json()
	{
		return json_encode($this->getArrayCopy());
	}

	/**
	 * Gets the current XML node as a YAML string.
	 *
	 * @return string The current XML node as a YAML string.
	 */
	public function to_yaml()
	{
		return sfYaml::dump($this->getArrayCopy(), 5);
	}
}

class CFArray_Exception extends Exception {}
