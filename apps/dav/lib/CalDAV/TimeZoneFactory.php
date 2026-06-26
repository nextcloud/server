<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV;

use DateTimeZone;

/**
 * Class to generate DateTimeZone object with automated Microsoft and IANA handling
 *
 * @since 31.0.0
 */
class TimeZoneFactory {

	/**
	 * conversion table of Microsoft time zones to IANA time zones
	 *
	 * @var array<string,string> MS2IANA
	 */
	private const MS2IANA = [
		'AUS Central Standard Time' => 'Australia/Darwin',
		'Aus Central W. Standard Time' => 'Australia/Eucla',
		'AUS Eastern Standard Time' => 'Australia/Sydney',
		'Afghanistan Standard Time' => 'Asia/Kabul',
		'Alaskan Standard Time' => 'America/Anchorage',
		'Aleutian Standard Time' => 'America/Adak',
		'Altai Standard Time' => 'Asia/Barnaul',
		'Arab Standard Time' => 'Asia/Riyadh',
		'Arabian Standard Time' => 'Asia/Dubai',
		'Arabic Standard Time' => 'Asia/Baghdad',
		'Argentina Standard Time' => 'America/Buenos_Aires',
		'Astrakhan Standard Time' => 'Europe/Astrakhan',
		'Atlantic Standard Time' => 'America/Halifax',
		'Azerbaijan Standard Time' => 'Asia/Baku',
		'Azores Standard Time' => 'Atlantic/Azores',
		'Bahia Standard Time' => 'America/Bahia',
		'Bangladesh Standard Time' => 'Asia/Dhaka',
		'Belarus Standard Time' => 'Europe/Minsk',
		'Bougainville Standard Time' => 'Pacific/Bougainville',
		'Cape Verde Standard Time' => 'Atlantic/Cape_Verde',
		'Canada Central Standard Time' => 'America/Regina',
		'Caucasus Standard Time' => 'Asia/Yerevan',
		'Cen. Australia Standard Time' => 'Australia/Adelaide',
		'Central America Standard Time' => 'America/Guatemala',
		'Central Asia Standard Time' => 'Asia/Almaty',
		'Central Brazilian Standard Time' => 'America/Cuiaba',
		'Central Europe Standard Time' => 'Europe/Budapest',
		'Central European Standard Time' => 'Europe/Warsaw',
		'Central Pacific Standard Time' => 'Pacific/Guadalcanal',
		'Central Standard Time' => 'America/Chicago',
		'Central Standard Time (Mexico)' => 'America/Mexico_City',
		'Chatham Islands Standard Time' => 'Pacific/Chatham',
		'China Standard Time' => 'Asia/Shanghai',
		'Coordinated Universal Time' => 'UTC',
		'Cuba Standard Time' => 'America/Havana',
		'Dateline Standard Time' => 'Etc/GMT+12',
		'E. Africa Standard Time' => 'Africa/Nairobi',
		'E. Australia Standard Time' => 'Australia/Brisbane',
		'E. Europe Standard Time' => 'Europe/Chisinau',
		'E. South America Standard Time' => 'America/Sao_Paulo',
		'Easter Island Standard Time' => 'Pacific/Easter',
		'Eastern Standard Time' => 'America/Toronto',
		'Eastern Standard Time (Mexico)' => 'America/Cancun',
		'Egypt Standard Time' => 'Africa/Cairo',
		'Ekaterinburg Standard Time' => 'Asia/Yekaterinburg',
		'FLE Standard Time' => 'Europe/Kiev',
		'Fiji Standard Time' => 'Pacific/Fiji',
		'GMT Standard Time' => 'Europe/London',
		'GTB Standard Time' => 'Europe/Bucharest',
		'Georgian Standard Time' => 'Asia/Tbilisi',
		'Greenland Standard Time' => 'America/Godthab',
		'Greenland (Danmarkshavn)' => 'America/Godthab',
		'Greenwich Standard Time' => 'Atlantic/Reykjavik',
		'Haiti Standard Time' => 'America/Port-au-Prince',
		'Hawaiian Standard Time' => 'Pacific/Honolulu',
		'India Standard Time' => 'Asia/Kolkata',
		'Iran Standard Time' => 'Asia/Tehran',
		'Israel Standard Time' => 'Asia/Jerusalem',
		'Jordan Standard Time' => 'Asia/Amman',
		'Kaliningrad Standard Time' => 'Europe/Kaliningrad',
		'Kamchatka Standard Time' => 'Asia/Kamchatka',
		'Korea Standard Time' => 'Asia/Seoul',
		'Libya Standard Time' => 'Africa/Tripoli',
		'Line Islands Standard Time' => 'Pacific/Kiritimati',
		'Lord Howe Standard Time' => 'Australia/Lord_Howe',
		'Magadan Standard Time' => 'Asia/Magadan',
		'Magallanes Standard Time' => 'America/Punta_Arenas',
		'Malaysia Standard Time' => 'Asia/Kuala_Lumpur',
		'Marquesas Standard Time' => 'Pacific/Marquesas',
		'Mauritius Standard Time' => 'Indian/Mauritius',
		'Mid-Atlantic Standard Time' => 'Atlantic/South_Georgia',
		'Middle East Standard Time' => 'Asia/Beirut',
		'Montevideo Standard Time' => 'America/Montevideo',
		'Morocco Standard Time' => 'Africa/Casablanca',
		'Mountain Standard Time' => 'America/Denver',
		'Mountain Standard Time (Mexico)' => 'America/Chihuahua',
		'Myanmar Standard Time' => 'Asia/Rangoon',
		'N. Central Asia Standard Time' => 'Asia/Novosibirsk',
		'Namibia Standard Time' => 'Africa/Windhoek',
		'Nepal Standard Time' => 'Asia/Kathmandu',
		'New Zealand Standard Time' => 'Pacific/Auckland',
		'Newfoundland Standard Time' => 'America/St_Johns',
		'Norfolk Standard Time' => 'Pacific/Norfolk',
		'North Asia East Standard Time' => 'Asia/Irkutsk',
		'North Asia Standard Time' => 'Asia/Krasnoyarsk',
		'North Korea Standard Time' => 'Asia/Pyongyang',
		'Omsk Standard Time' => 'Asia/Omsk',
		'Pacific SA Standard Time' => 'America/Santiago',
		'Pacific Standard Time' => 'America/Los_Angeles',
		'Pacific Standard Time (Mexico)' => 'America/Tijuana',
		'Pakistan Standard Time' => 'Asia/Karachi',
		'Paraguay Standard Time' => 'America/Asuncion',
		'Qyzylorda Standard Time' => 'Asia/Qyzylorda',
		'Romance Standard Time' => 'Europe/Paris',
		'Russian Standard Time' => 'Europe/Moscow',
		'Russia Time Zone 10' => 'Asia/Srednekolymsk',
		'Russia Time Zone 3' => 'Europe/Samara',
		'SA Eastern Standard Time' => 'America/Cayenne',
		'SA Pacific Standard Time' => 'America/Bogota',
		'SA Western Standard Time' => 'America/La_Paz',
		'SE Asia Standard Time' => 'Asia/Bangkok',
		'Saint Pierre Standard Time' => 'America/Miquelon',
		'Sakhalin Standard Time' => 'Asia/Sakhalin',
		'Samoa Standard Time' => 'Pacific/Apia',
		'Sao Tome Standard Time' => 'Africa/Sao_Tome',
		'Saratov Standard Time' => 'Europe/Saratov',
		'Singapore Standard Time' => 'Asia/Singapore',
		'South Africa Standard Time' => 'Africa/Johannesburg',
		'South Sudan Standard Time' => 'Africa/Juba',
		'Sri Lanka Standard Time' => 'Asia/Colombo',
		'Sudan Standard Time' => 'Africa/Khartoum',
		'Syria Standard Time' => 'Asia/Damascus',
		'Taipei Standard Time' => 'Asia/Taipei',
		'Tasmania Standard Time' => 'Australia/Hobart',
		'Tocantins Standard Time' => 'America/Araguaina',
		'Tokyo Standard Time' => 'Asia/Tokyo',
		'Tomsk Standard Time' => 'Asia/Tomsk',
		'Tonga Standard Time' => 'Pacific/Tongatapu',
		'Transbaikal Standard Time' => 'Asia/Chita',
		'Turkey Standard Time' => 'Europe/Istanbul',
		'Turks And Caicos Standard Time' => 'America/Grand_Turk',
		'US Eastern Standard Time' => 'America/Indianapolis',
		'US Mountain Standard Time' => 'America/Phoenix',
		'UTC' => 'Etc/GMT',
		'UTC+13' => 'Etc/GMT-13',
		'UTC+12' => 'Etc/GMT-12',
		'UTC-02' => 'Etc/GMT+2',
		'UTC-09' => 'Etc/GMT+9',
		'UTC-11' => 'Etc/GMT+11',
		'Ulaanbaatar Standard Time' => 'Asia/Ulaanbaatar',
		'Venezuela Standard Time' => 'America/Caracas',
		'Vladivostok Standard Time' => 'Asia/Vladivostok',
		'Volgograd Standard Time' => 'Europe/Volgograd',
		'W. Australia Standard Time' => 'Australia/Perth',
		'W. Central Africa Standard Time' => 'Africa/Lagos',
		'W. Europe Standard Time' => 'Europe/Berlin',
		'W. Mongolia Standard Time' => 'Asia/Hovd',
		'West Asia Standard Time' => 'Asia/Tashkent',
		'West Bank Standard Time' => 'Asia/Hebron',
		'West Pacific Standard Time' => 'Pacific/Port_Moresby',
		'West Samoa Standard Time' => 'Pacific/Apia',
		'Yakutsk Standard Time' => 'Asia/Yakutsk',
		'Yukon Standard Time' => 'America/Whitehorse',
		'Yekaterinburg Standard Time' => 'Asia/Yekaterinburg',
	];

	/**
	 * Determines if given time zone name is a Microsoft time zone
	 *
	 * @since 31.0.0
	 *
	 * @param string $name time zone name
	 *
	 * @return bool
	 */
	public static function isMS(string $name): bool {
		return isset(self::MS2IANA[$name]);
	}

	/**
	 * Converts Microsoft time zone name to IANA time zone name
	 *
	 * @since 31.0.0
	 *
	 * @param string $name microsoft time zone
	 *
	 * @return string|null valid IANA time zone name on success, or null on failure
	 */
	public static function toIANA(string $name): ?string {
		return isset(self::MS2IANA[$name]) ? self::MS2IANA[$name] : null;
	}

	/**
	 * Generates DateTimeZone object for given time zone name
	 *
	 * @since 31.0.0
	 *
	 * @param string $name time zone name
	 *
	 * @return DateTimeZone|null
	 */
	public function fromName(string $name): ?DateTimeZone {
		// if zone name is MS convert to IANA, otherwise just assume the zone is IANA
		$zone = @timezone_open(self::toIANA($name) ?? $name);
		return ($zone instanceof DateTimeZone) ? $zone : null;
	}
}
