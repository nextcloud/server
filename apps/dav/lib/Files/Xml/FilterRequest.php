<?php

namespace OCA\DAV\Files\Xml;

use Sabre\Xml\Element\Base;
use Sabre\Xml\Element\KeyValue;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

class FilterRequest implements XmlDeserializable {

	/**
	 * An array with requested properties.
	 *
	 * @var array
	 */
	public $properties;

	/**
	 * @var array
	 */
	public $filters;

	/**
	 * @var array
	 */
	public $limit;

	/**
	 * The deserialize method is called during xml parsing.
	 *
	 * This method is called statically, this is because in theory this method
	 * may be used as a type of constructor, or factory method.
	 *
	 * Often you want to return an instance of the current class, but you are
	 * free to return other data as well.
	 *
	 * You are responsible for advancing the reader to the next element. Not
	 * doing anything will result in a never-ending loop.
	 *
	 * If you just want to skip parsing for this element altogether, you can
	 * just call $reader->next();
	 *
	 * $reader->parseInnerTree() will parse the entire sub-tree, and advance to
	 * the next element.
	 *
	 * @param Reader $reader
	 * @return mixed
	 */
	static function xmlDeserialize(Reader $reader) {
		$elems = (array)$reader->parseInnerTree([
			'{DAV:}prop' => KeyValue::class,
			'{http://owncloud.org/ns}filter-rules' => Base::class,
			'{http://owncloud.org/ns}limit' => Base::class
		]);

		$newProps = [
			'filters'    => [
				'systemtag' => [],
				'favorite' => null
			],
			'properties' => [],
			'limit'      => null,
		];

		if (!is_array($elems)) {
			$elems = [];
		}

		foreach ($elems as $elem) {

			switch ($elem['name']) {

				case '{DAV:}prop' :
					$newProps['properties'] = array_keys($elem['value']);
					break;
				case '{http://owncloud.org/ns}filter-rules' :

					foreach ($elem['value'] as $tag) {
						if ($tag['name'] === '{http://owncloud.org/ns}systemtag') {
							$newProps['filters']['systemtag'][] = $tag['value'];
						}
						if ($tag['name'] === '{http://owncloud.org/ns}favorite') {
							$newProps['filters']['favorite'] = true;
						}
					}
					break;
				case '{http://owncloud.org/ns}limit' :
					// TODO verify page and size
					$newProps['limit'] = $elem['attributes'];
					break;

			}

		}

		$obj = new self();
		foreach ($newProps as $key => $value) {
			$obj->$key = $value;
		}

		return $obj;
	}
}
