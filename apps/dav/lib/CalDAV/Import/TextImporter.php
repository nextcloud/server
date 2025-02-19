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

	protected array $types = ['VEVENT', 'VTODO', 'VJOURNAL', 'VTIMEZONE'];
	protected bool $analyzed = false;
	protected array $structure = ['VCALENDAR' => [], 'VEVENT' => [], 'VTODO' => [], 'VJOURNAL' => [], 'VTIMEZONE' => []];

	public function __construct(
		protected $source,
	) {
		//Ensure that the $data var is of the right type
		if (!is_string($source) && (!is_resource($source) || get_resource_type($source) !== 'stream')) {
			throw new Exception('Source must be a string or a stream resource');
		}
	}

	/**
	 * Analyzes the source data and creates a structure of components
	 */
	protected function analyze() {
		$componentStart = null;
		$componentEnd = null;
		$componentId = null;
		$componentType = null;
		// iterate through the source data line by line
		fseek($this->source, 0);
		while (!feof($this->source)) {
			$data = fgets($this->source);
			// skip empty lines
			if ($data === false || empty(trim($data))) {
				continue;
			}
			// check for withspace at the beginning of the line
			// lines with whitespace at the beginning are continuations of the pervious line
			if (ctype_space($data[0]) === false) {
				// check line for component start, remember the position and determine the type
				if (str_starts_with($data, 'BEGIN:')) {
					$type = trim(substr($data, 6));
					if (in_array($type, $this->types)) {
						$componentStart = ftell($this->source) - strlen($data);
						$componentType = $type;
					}
					unset($type);
				}
				// check line for component end, remember the position
				if (str_starts_with($data, 'END:')) {
					$type = trim(substr($data, 4));
					if ($componentType === $type) {
						$componentEnd = ftell($this->source);
					}
					unset($type);
				}
				// check line for component id
				if ($componentStart !== null && str_starts_with($data, 'UID:')) {
					$componentId = trim(substr($data, 5));
				}
				if ($componentStart !== null && str_starts_with($data, 'TZID:')) {
					$componentId = trim(substr($data, 5));
				}
			}
			// any line(s) not inside a component are VCALENDAR properties
			if ($componentStart === null) {
				if (!str_starts_with($data, 'BEGIN:VCALENDAR') && !str_starts_with($data, 'END:VCALENDAR')) {
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
