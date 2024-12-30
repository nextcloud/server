<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Import;

use Exception;

class XmlImporter {

	public const OBJECT_PREFIX = '<?xml version="1.0" encoding="UTF-8"?><icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0"><vcalendar><components>';
	public const OBJECT_SUFFIX = '</components></vcalendar></icalendar>';

	protected array $types = ['VEVENT', 'VTODO', 'VJOURNAL', 'VTIMEZONE'];
	protected bool $analyzed = false;
	protected array $structure = ['VCALENDAR' => [], 'VEVENT' => [], 'VTODO' => [], 'VJOURNAL' => [], 'VTIMEZONE' => []];
	protected int $praseLevel = 0;
	protected array $prasePath = [];
	protected ?int $componentStart = null;
	protected ?int $componentEnd = null;
	protected int $componentLevel = 0;
	protected ?string $componentId = null;
	protected ?string $componentType = null;
	protected bool $componentIdProperty = false;
	

	public function __construct(
		protected $source,
	) {
		//Ensure that the $data var is of the right type
		if (!is_string($source) && (!is_resource($source) || get_resource_type($source) !== 'stream')) {
			throw new Exception('Source must be a string or a stream resource');
		}
	}

	protected function analyze() {

		$this->praseLevel = 0;
		$this->prasePath = [];
		$this->componentStart = null;
		$this->componentEnd = null;
		$this->componentLevel = 0;
		$this->componentId = null;
		$this->componentType = null;
		$this->componentIdProperty = false;
		//Create the parser
		$parser = xml_parser_create();
		// assign handlers
		xml_set_object($parser, $this);
		xml_set_element_handler($parser, $this->tagStart(...), $this->tagEnd(...));
		xml_set_default_handler($parser, $this->tagContents(...));
		//If the data is a resource then loop through it, otherwise just parse the string
		if (is_resource($this->source)) {
			//Not all resources support fseek. For those that don't, suppress the error
			@fseek($this->source, 0);
			while ($chunk = fread($this->source, 4096)) {
				if (!xml_parse($parser, $chunk, feof($this->source))) {
					throw new Exception(
						xml_error_string(xml_get_error_code($parser))
						. ' At line: ' .
						xml_get_current_line_number($parser)
					);
				}
			}
		} else {
			if (!xml_parse($parser, $this->source, true)) {
				throw new Exception(
					xml_error_string(xml_get_error_code($parser))
					. ' At line: ' .
					xml_get_current_line_number($parser)
				);
			}
		}

		//Free up the parser
		xml_parser_free($parser);

	}

	protected function tagStart($parser, $tag, $attributes) {
		
		$this->praseLevel++;
		$this->prasePath[$this->praseLevel] = $tag;

		if (in_array($tag, $this->types)) {
			$this->componentStart = xml_get_current_byte_index($parser) - (strlen($tag) + 1);
			$this->componentType = $tag;
			$this->componentLevel = $this->praseLevel;
		}
	
		if ($this->componentStart !== null &&
			($this->componentLevel + 2) === $this->praseLevel &&
			($tag === 'UID' || $tag === 'TZID')
		) {
			$this->componentIdProperty = true;
		}

		return $parser;
	}

	protected function tagEnd($parser, $tag) {

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

		unset($this->prasePath[$this->praseLevel]);
		$this->praseLevel--;
		
		return $parser;
	}

	protected function tagContents($parser, $data) {

		if ($this->componentIdProperty) {
			$this->componentId = $data;
		}

		return $parser;
	}


	public function structure(): array {

		if (!$this->analyzed) {
			$this->analyze();
		}
		
		return $this->structure;
	}

	public function extract(int $start, int $end): string {
	
		fseek($this->source, $start);
		return fread($this->source, $end - $start);

	}

}
