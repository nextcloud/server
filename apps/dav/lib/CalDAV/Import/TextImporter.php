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

	protected function analyze() {

		$componentStart = null;
		$componentEnd = null;
		$componentId = null;
		$componentType = null;

		fseek($this->source, 0);
		while (!feof($this->source)) {
			$data = fgets($this->source);

			if ($data === false || empty(trim($data))) {
				continue;
			}
			
			if (ctype_space($data[0]) === false) {
			
				if (str_starts_with($data, 'BEGIN:')) {
					$type = trim(substr($data, 6));
					if (in_array($type, $this->types)) {
						$componentStart = ftell($this->source) - strlen($data);
						$componentType = $type;
					}
					unset($type);
				}
				
				if (str_starts_with($data, 'END:')) {
					$type = trim(substr($data, 4));
					if ($componentType === $type) {
						$componentEnd = ftell($this->source);
					}
					unset($type);
				}

				if ($componentStart !== null && str_starts_with($data, 'UID:')) {
					$componentId = trim(substr($data, 5));
				}

				if ($componentStart !== null && str_starts_with($data, 'TZID:')) {
					$componentId = trim(substr($data, 5));
				}
				
			}

			if ($componentStart === null) {
				if (!str_starts_with($data, 'BEGIN:VCALENDAR') && !str_starts_with($data, 'END:VCALENDAR')) {
					$components['VCALENDAR'][] = $data;
				}
			}

			if ($componentEnd !== null) {
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
