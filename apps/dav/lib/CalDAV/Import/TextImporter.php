<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Import;

use Exception;

class TextImporter {

	public const OBJECT_PREFIX = 'BEGIN:VCALENDAR' . PHP_EOL;
	public const OBJECT_SUFFIX = PHP_EOL . 'END:VCALENDAR';
	private const COMPONENT_TYPES = ['VEVENT', 'VTODO', 'VJOURNAL', 'VTIMEZONE'];

	private bool $analyzed = false;
	private array $structure = ['VCALENDAR' => [], 'VEVENT' => [], 'VTODO' => [], 'VJOURNAL' => [], 'VTIMEZONE' => []];

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
		$componentStart = null;
		$componentEnd = null;
		$componentId = null;
		$componentType = null;
		$tagName = null;
		$tagValue = null;

		// iterate through the source data line by line
		fseek($this->source, 0);
		while (!feof($this->source)) {
			$data = fgets($this->source);
			// skip empty lines
			if ($data === false || empty(trim($data))) {
				continue;
			}
			// lines with whitespace at the beginning are continuations of the previous line
			if (ctype_space($data[0]) === false) {
				// detect the line TAG
				// detect the first occurrence of ':' or ';'
				$colonPos = strpos($data, ':');
				$semicolonPos = strpos($data, ';');
				if ($colonPos !== false && $semicolonPos !== false) {
					$splitPosition = min($colonPos, $semicolonPos);
				} elseif ($colonPos !== false) {
					$splitPosition = $colonPos;
				} elseif ($semicolonPos !== false) {
					$splitPosition = $semicolonPos;
				} else {
					continue;
				}
				$tagName = strtoupper(trim(substr($data, 0, $splitPosition)));
				$tagValue = trim(substr($data, $splitPosition + 1));
				$tagContinuation = false;
			} else {
				$tagContinuation = true;
				$tagValue .= trim($data);
			}

			if ($tagContinuation === false) {
				// check line for component start, remember the position and determine the type
				if ($tagName === 'BEGIN' && in_array($tagValue, self::COMPONENT_TYPES, true)) {
					$componentStart = ftell($this->source) - strlen($data);
					$componentType = $tagValue;
				}
				// check line for component end, remember the position
				if ($tagName === 'END' && $componentType === $tagValue) {
					$componentEnd = ftell($this->source);
				}
				// check line for component id
				if ($componentStart !== null && ($tagName === 'UID' || $tagName === 'TZID')) {
					$componentId = $tagValue;
				}
			} else {
				// check line for component id
				if ($componentStart !== null && ($tagName === 'UID' || $tagName === 'TZID')) {
					$componentId = $tagValue;
				}
			}
			// any line(s) not inside a component are VCALENDAR properties
			if ($componentStart === null) {
				if ($tagName !== 'BEGIN' && $tagName !== 'END' && $tagValue === 'VCALENDAR') {
					$components['VCALENDAR'][] = $data;
				}
			}
			// if component start and end are found, add the component to the structure
			if ($componentStart !== null && $componentEnd !== null) {
				if ($componentId !== null) {
					$this->structure[$componentType][$componentId][] = [
						$componentType,
						$componentId,
						$componentStart,
						$componentEnd
					];
				} else {
					$this->structure[$componentType][] = [
						$componentType,
						$componentId,
						$componentStart,
						$componentEnd
					];
				}
				$componentId = null;
				$componentType = null;
				$componentStart = null;
				$componentEnd = null;
			}
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
