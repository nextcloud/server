<?php
/**
 * @author Omer Hassan
 * @author Ryan Parman
 * @license MIT
 */
class Array2DOM
{
	const ATTRIBUTES = '__attributes__';
	const CONTENT = '__content__';

	/**
	 * @param array $source
	 * @param string $rootTagName
	 * @return DOMDocument
	 */
	public static function arrayToDOMDocument(array $source, $rootTagName = 'root')
	{
		$document = new DOMDocument();
		$document->appendChild(self::createDOMElement($source, $rootTagName, $document));

		return $document;
	}

	/**
	 * @param array $source
	 * @param string $rootTagName
	 * @param bool $formatOutput
	 * @return string
	 */
	public static function arrayToXMLString(array $source, $rootTagName = 'root', $formatOutput = true)
	{
		$document = self::arrayToDOMDocument($source, $rootTagName);
		$document->formatOutput = $formatOutput;

		return $document->saveXML();
	}

	/**
	 * @param DOMDocument $document
	 * @return array
	 */
	public static function domDocumentToArray(DOMDocument $document)
	{
		return self::createArray($document->documentElement);
	}

	/**
	 * @param string $xmlString
	 * @return array
	 */
	public static function xmlStringToArray($xmlString)
	{
		$document = new DOMDocument();

		return $document->loadXML($xmlString) ? self::domDocumentToArray($document) : array();
	}

	/**
	 * @param mixed $source
	 * @param string $tagName
	 * @param DOMDocument $document
	 * @return DOMNode
	 */
	private static function createDOMElement($source, $tagName, DOMDocument $document)
	{
		if (!is_array($source))
		{
			$element = $document->createElement($tagName);
			$element->appendChild($document->createCDATASection($source));

			return $element;
		}

		$element = $document->createElement($tagName);

		foreach ($source as $key => $value)
		{
			if (is_string($key) && !is_numeric($key))
			{
				if ($key === self::ATTRIBUTES)
				{
					foreach ($value as $attributeName => $attributeValue)
					{
						 $element->setAttribute($attributeName, $attributeValue);
					}
				}
				elseif ($key === self::CONTENT)
				{
					$element->appendChild($document->createCDATASection($value));
				}
				elseif (is_string($value) && !is_numeric($value))
				{
					$element->appendChild(self::createDOMElement($value, $key, $document));
				}
				elseif (is_array($value) && count($value))
				{
					$keyNode = $document->createElement($key);

					foreach ($value as $elementKey => $elementValue)
					{
						if (is_string($elementKey) && !is_numeric($elementKey))
						{
							$keyNode->appendChild(self::createDOMElement($elementValue, $elementKey, $document));
						}
						else
						{
							$element->appendChild(self::createDOMElement($elementValue, $key, $document));
						}
					}

					if ($keyNode->hasChildNodes())
					{
						$element->appendChild($keyNode);
					}
				}
				else
				{
					if (is_bool($value))
					{
						$value = $value ? 'true' : 'false';
					}

					$element->appendChild(self::createDOMElement($value, $key, $document));
				}
			}
			else
			{
				$element->appendChild(self::createDOMElement($value, $tagName, $document));
			}
		}

		return $element;
	}

	/**
	 * @param DOMNode $domNode
	 * @return array
	 */
	private static function createArray(DOMNode $domNode)
	{
		$array = array();

		for ($i = 0; $i < $domNode->childNodes->length; $i++)
		{
			$item = $domNode->childNodes->item($i);

			if ($item->nodeType === XML_ELEMENT_NODE)
			{
				$arrayElement = array();

				for ($attributeIndex = 0; !is_null($attribute = $item->attributes->item($attributeIndex)); $attributeIndex++)
				{
					if ($attribute->nodeType === XML_ATTRIBUTE_NODE)
					{
						$arrayElement[self::ATTRIBUTES][$attribute->nodeName] = $attribute->nodeValue;
					}
				}

				$children = self::createArray($item);

				if (is_array($children))
				{
					$arrayElement = array_merge($arrayElement, $children);
				}
				else
				{
					$arrayElement[self::CONTENT] = $children;
				}

				$array[$item->nodeName][] = $arrayElement;
			}
			elseif ($item->nodeType === XML_CDATA_SECTION_NODE || ($item->nodeType === XML_TEXT_NODE && trim($item->nodeValue) !== ''))
			{
				return $item->nodeValue;
			}
		}

		return $array;
	}
}
