<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WeatherStatus;

/**
 * https://api.met.no/doc/ForecastJSON compact format according to https://docs.api.met.no/doc/locationforecast/datamodel
 * @psalm-type WeatherStatusForecast = array{
 *     time: string,
 *     data: array{
 *         instant: array{
 *             details: array{
 *                 air_pressure_at_sea_level: numeric,
 *                 air_temperature: numeric,
 *                 cloud_area_fraction: numeric,
 *                 relative_humidity: numeric,
 *                 wind_from_direction: numeric,
 *                 wind_speed: numeric,
 *             },
 *         },
 *         next_12_hours: array{
 *             summary: array{
 *                 symbol_code: string,
 *             },
 *             details: array{
 *                 precipitation_amount?: numeric,
 *             },
 *         },
 *         next_1_hours: array{
 *             summary: array{
 *                 symbol_code: string,
 *             },
 *             details: array{
 *                 precipitation_amount?: numeric,
 *             },
 *         },
 *         next_6_hours: array{
 *             summary: array{
 *                 symbol_code: string,
 *             },
 *             details: array{
 *                 precipitation_amount?: numeric,
 *             },
 *         },
 *     },
 * }
 *
 * @psalm-type WeatherStatusSuccess = array{
 *     success: bool,
 * }
 *
 * @psalm-type WeatherStatusMode = array{
 *     mode: int,
 * }
 * @psalm-type WeatherStatusLocation = array{
 *     lat?: string,
 *     lon?: string,
 *     address?: ?string,
 * }
 *
 * @psalm-type WeatherStatusLocationWithSuccess = WeatherStatusLocation&WeatherStatusSuccess
 *
 * @psalm-type WeatherStatusLocationWithMode = WeatherStatusLocation&WeatherStatusMode
 */
class ResponseDefinitions {
}
