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
 * Wraps the underlying `SimpleXMLIterator` class with enhancements for rapidly traversing the DOM tree,
 * converting types, and comparisons.
 *
 * @version 2012.01.17
 * @license See the included NOTICE.md file for more information.
 * @copyright See the included NOTICE.md file for more information.
 * @link http://aws.amazon.com/php/ PHP Developer Center
 * @link http://php.net/SimpleXML SimpleXML
 */
class CFSimpleXML extends SimpleXMLIterator
{
	/**
	 * Stores the namespace name to use in XPath queries.
	 */
	public $xml_ns;

	/**
	 * Stores the namespace URI to use in XPath queries.
	 */
	public $xml_ns_url;

	/**
	 * Catches requests made to methods that don't exist. Specifically, looks for child nodes via XPath.
	 *
	 * @param string $name (Required) The name of the method.
	 * @param array $arguments (Required) The arguments passed to the method.
	 * @return mixed Either an array of matches, or a single <CFSimpleXML> element.
	 */
	public function __call($name, $arguments)
	{
		// Remap $this
		$self = $this;

		// Re-base the XML
		$self = new CFSimpleXML($self->asXML());

		// Determine XPath query
		$self->xpath_expression = 'descendant-or-self::' . $name;

		// Get the results and augment with CFArray
		$results = $self->xpath($self->xpath_expression);
		if (!count($results)) return false;
		$results = new CFArray($results);

		// If an integer was passed, return only that result
		if (isset($arguments[0]) && is_int($arguments[0]))
		{
			if (isset($results[$arguments[0]]))
			{
				return $results[$arguments[0]];
			}

			return false;
		}

		return $results;
	}

	/**
	 * Alternate approach to constructing a new instance. Supports chaining.
	 *
	 * @param string $data (Required) A well-formed XML string or the path or URL to an XML document if $data_is_url is <code>true</code>.
	 * @param integer $options (Optional) Used to specify additional LibXML parameters. The default value is <code>0</code>.
	 * @param boolean $data_is_url (Optional) Specify a value of <code>true</code> to specify that data is a path or URL to an XML document instead of string data. The default value is <code>false</code>.
	 * @param string $ns (Optional) The XML namespace to return values for.
	 * @param boolean $is_prefix (Optional) (No description provided by PHP.net.)
	 * @return CFSimpleXML Creates a new <CFSimpleXML> element.
	 */
	public static function init($data, $options = 0, $data_is_url, $ns, $is_prefix = false)
	{
		if (version_compare(PHP_VERSION, '5.3.0', '<'))
		{
			throw new Exception('PHP 5.3 or newer is required to instantiate a new class with CLASS::init().');
		}

		$self = get_called_class();
		return new $self($data, $options, $data_is_url, $ns, $is_prefix);
	}


	/*%******************************************************************************************%*/
	// TRAVERSAL

	/**
	 * Wraps the results of an XPath query in a <CFArray> object.
	 *
	 * @param string $expr (Required) The XPath expression to use to query the XML response.
	 * @return CFArray A <CFArray> object containing the results of the XPath query.
	 */
	public function query($expr)
	{
		return new CFArray($this->xpath($expr));
	}

	/**
	 * Gets the parent or a preferred ancestor of the current element.
	 *
	 * @param string $node (Optional) Name of the ancestor element to match and return.
	 * @return CFSimpleXML A <CFSimpleXML> object containing the requested node.
	 */
	public function parent($node = null)
	{
		if ($node)
		{
			$parents = $this->xpath('ancestor-or-self::' . $node);
		}
		else
		{
			$parents = $this->xpath('parent::*');
		}

		return $parents[0];
	}


	/*%******************************************************************************************%*/
	// ALTERNATE FORMATS

	/**
	 * Gets the current XML node as a true string.
	 *
	 * @return string The current XML node as a true string.
	 */
	public function to_string()
	{
		return (string) $this;
	}

	/**
	 * Gets the current XML node as <CFArray>, a child class of PHP's <php:ArrayObject> class.
	 *
	 * @return CFArray The current XML node as a <CFArray> object.
	 */
	public function to_array()
	{
		return new CFArray(json_decode(json_encode($this), true));
	}

	/**
	 * Gets the current XML node as a stdClass object.
	 *
	 * @return array The current XML node as a stdClass object.
	 */
	public function to_stdClass()
	{
		return json_decode(json_encode($this));
	}

	/**
	 * Gets the current XML node as a JSON string.
	 *
	 * @return string The current XML node as a JSON string.
	 */
	public function to_json()
	{
		return json_encode($this);
	}

	/**
	 * Gets the current XML node as a YAML string.
	 *
	 * @return string The current XML node as a YAML string.
	 */
	public function to_yaml()
	{
		return sfYaml::dump(json_decode(json_encode($this), true), 5);
	}


	/*%******************************************************************************************%*/
	// COMPARISONS

	/**
	 * Whether or not the current node exactly matches the compared value.
	 *
	 * @param string $value (Required) The value to compare the current node to.
	 * @return boolean Whether or not the current node exactly matches the compared value.
	 */
	public function is($value)
	{
		return ((string) $this === $value);
	}

	/**
	 * Whether or not the current node contains the compared value.
	 *
	 * @param string $value (Required) The value to use to determine whether it is contained within the node.
	 * @return boolean Whether or not the current node contains the compared value.
	 */
	public function contains($value)
	{
		return (stripos((string) $this, $value) !== false);
	}

	/**
	 * Whether or not the current node matches the regular expression pattern.
	 *
	 * @param string $pattern (Required) The pattern to match the current node against.
	 * @return boolean Whether or not the current node matches the pattern.
	 */
	public function matches($pattern)
	{
		return (bool) preg_match($pattern, (string) $this);
	}

	/**
	 * Whether or not the current node starts with the compared value.
	 *
	 * @param string $value (Required) The value to compare the current node to.
	 * @return boolean Whether or not the current node starts with the compared value.
	 */
	public function starts_with($value)
	{
		return $this->matches("@^$value@u");
	}

	/**
	 * Whether or not the current node ends with the compared value.
	 *
	 * @param string $value (Required) The value to compare the current node to.
	 * @return boolean Whether or not the current node ends with the compared value.
	 */
	public function ends_with($value)
	{
		return $this->matches("@$value$@u");
	}
}
