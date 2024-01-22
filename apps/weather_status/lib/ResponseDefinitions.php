<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Kate Döen <kate.doeen@nextcloud.com>
 *
 * @author Kate Döen <kate.doeen@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
