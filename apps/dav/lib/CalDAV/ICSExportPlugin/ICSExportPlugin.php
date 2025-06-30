<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\ICSExportPlugin;

use OCP\IConfig;
use Psr\Log\LoggerInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\InvalidDataException;
use Sabre\VObject\Property\ICalendar\Duration;

/**
 * Class ICSExportPlugin
 *
 * @package OCA\DAV\CalDAV\ICSExportPlugin
 */
class ICSExportPlugin extends \Sabre\CalDAV\ICSExportPlugin {
	/** @var string */
	private const DEFAULT_REFRESH_INTERVAL = 'PT4H';

	/**
	 * ICSExportPlugin constructor.
	 */
	public function __construct(
		private IConfig $config,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @inheritDoc
	 */
	protected function generateResponse($path, $start, $end, $expand, $componentType, $format, $properties, ResponseInterface $response) {
		if (!isset($properties['{http://nextcloud.com/ns}refresh-interval'])) {
			$value = $this->config->getAppValue('dav', 'defaultRefreshIntervalExportedCalendars', self::DEFAULT_REFRESH_INTERVAL);
			$properties['{http://nextcloud.com/ns}refresh-interval'] = $value;
		}

		return parent::generateResponse($path, $start, $end, $expand, $componentType, $format, $properties, $response);
	}

	/**
	 * @inheritDoc
	 */
	public function mergeObjects(array $properties, array $inputObjects) {
		$vcalendar = parent::mergeObjects($properties, $inputObjects);

		if (isset($properties['{http://nextcloud.com/ns}refresh-interval'])) {
			$refreshIntervalValue = $properties['{http://nextcloud.com/ns}refresh-interval'];
			try {
				DateTimeParser::parseDuration($refreshIntervalValue);
			} catch (InvalidDataException $ex) {
				$this->logger->debug('Invalid refresh interval for exported calendar, falling back to default value ...');
				$refreshIntervalValue = self::DEFAULT_REFRESH_INTERVAL;
			}

			// https://tools.ietf.org/html/rfc7986#section-5.7
			$refreshInterval = new Duration($vcalendar, 'REFRESH-INTERVAL', $refreshIntervalValue);
			$refreshInterval->add('VALUE', 'DURATION');
			$vcalendar->add($refreshInterval);

			// Legacy property for compatibility
			$vcalendar->{'X-PUBLISHED-TTL'} = $refreshIntervalValue;
		}

		return $vcalendar;
	}
}
