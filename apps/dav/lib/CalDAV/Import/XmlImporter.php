<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Import;

use Exception;
use XMLParser;

class XmlImporter {

	public const OBJECT_PREFIX = '<?xml version="1.0" encoding="UTF-8"?><icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0"><vcalendar><components>';
	public const OBJECT_SUFFIX = '</components></vcalendar></icalendar>';
	private const COMPONENT_TYPES = ['VEVENT', 'VTODO', 'VJOURNAL', 'VTIMEZONE'];

	private bool $analyzed = false;
	private array $structure = ['VCALENDAR' => [], 'VEVENT' => [], 'VTODO' => [], 'VJOURNAL' => [], 'VTIMEZONE' => []];
	private int $praseLevel = 0;
	private array $prasePath = [];
	private ?int $componentStart = null;
	private ?int $componentEnd = null;
	private int $componentLevel = 0;
	private ?string $componentId = null;
	private ?string $componentType = null;
	private bool $componentIdProperty = false;

	/**
	 * @param resource $source
	 */
	public function __construct(
		private $source,
	) {
		// Ensure that source is a stream resource
		if (!is_resource($source) || get_resource_type($source) !== 'stream') {
			throw new Exception('Source must be a stream resource');
		}
	}

	/**
	 * Analyzes the source data and creates a structure of components
	 */
	private function analyze() {
		$this->praseLevel = 0;
		$this->prasePath = [];
		$this->componentStart = null;
		$this->componentEnd = null;
		$this->componentLevel = 0;
		$this->componentId = null;
		$this->componentType = null;
		$this->componentIdProperty = false;
		// Create the parser and assign tag handlers
		$parser = xml_parser_create();
		xml_set_object($parser, $this);
		xml_set_element_handler($parser, $this->tagStart(...), $this->tagEnd(...));
		xml_set_default_handler($parser, $this->tagContents(...));
		// iterate through the source data chuck by chunk to trigger the handlers
		@fseek($this->source, 0);
		while ($chunk = fread($this->source, 4096)) {
			if (!xml_parse($parser, $chunk, feof($this->source))) {
				throw new Exception(
					xml_error_string(xml_get_error_code($parser))
					. ' At line: '
					. xml_get_current_line_number($parser)
				);
			}
		}
		//Free up the parser
		xml_parser_free($parser);
	}

	/**
	 * Handles start of tag events from the parser for all tags
	 */
	private function tagStart(XMLParser $parser, string $tag, array $attributes): void {
		// add the tag to the path tracker and increment depth the level
		$this->praseLevel++;
		$this->prasePath[$this->praseLevel] = $tag;
		// determine if the tag is a component type and remember the byte position
		if (in_array($tag, self::COMPONENT_TYPES, true)) {
			$this->componentStart = xml_get_current_byte_index($parser) - (strlen($tag) + 1);
			$this->componentType = $tag;
			$this->componentLevel = $this->praseLevel;
		}
		// determine if the tag is a sub tag of the component and an id property
		if ($this->componentStart !== null
			&& ($this->componentLevel + 2) === $this->praseLevel
			&& ($tag === 'UID' || $tag === 'TZID')
		) {
			$this->componentIdProperty = true;
		}
	}

	/**
	 * Handles end of tag events from the parser for all tags
	 */
	private function tagEnd(XMLParser $parser, string $tag): void {
		// if the end tag matched the component type or the component id property
		// then add the component to the structure
		if ($tag === 'UID' || $tag === 'TZID') {
			$this->componentIdProperty = false;
		} elseif ($this->componentType === $tag) {
			$this->componentEnd = xml_get_current_byte_index($parser);
			if ($this->componentId !== null) {
				$this->structure[$this->componentType][$this->componentId][] = [
					$this->componentType,
					$this->componentId,
					$this->componentStart,
					$this->componentEnd,
					implode('/', $this->prasePath)
				];
			} else {
				$this->structure[$this->componentType][] = [
					$this->componentType,
					$this->componentId,
					$this->componentStart,
					$this->componentEnd,
					implode('/', $this->prasePath)
				];
			}
			$this->componentStart = null;
			$this->componentEnd = null;
			$this->componentId = null;
			$this->componentType = null;
			$this->componentIdProperty = false;
		}
		// remove the tag from the path tacker and depth the level
		unset($this->prasePath[$this->praseLevel]);
		$this->praseLevel--;
	}

	/**
	 * Handles tag contents events from the parser for all tags
	 */
	private function tagContents(XMLParser $parser, string $data): void {
		if ($this->componentIdProperty) {
			$this->componentId = $data;
		}
	}

	/**
	 * Returns the analyzed structure of the source data
	 * the analyzed structure is a collection of components organized by type,
	 * each entry is a collection of instances
	 * [
	 *  'VEVENT' => [
	 *    '7456f141-b478-4cb9-8efc-1427ba0d6839' => [
	 *      ['VEVENT', '7456f141-b478-4cb9-8efc-1427ba0d6839', 0, 100 ],
	 *      ['VEVENT', '7456f141-b478-4cb9-8efc-1427ba0d6839', 100, 200 ]
	 *    ]
	 *  ]
	 * ]
	 */
	public function structure(): array {
		if (!$this->analyzed) {
			$this->analyze();
		}
		return $this->structure;
	}

	/**
	 * Extracts a string chuck from the source data
	 *
	 * @param int $start starting byte position
	 * @param int $end ending byte position
	 */
	public function extract(int $start, int $end): string {
		fseek($this->source, $start);
		return fread($this->source, $end - $start);
	}
}
