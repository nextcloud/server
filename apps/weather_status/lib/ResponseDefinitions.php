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
 * https://api.met.no/doc/ForecastJSON
 * @psalm-type WeatherStatusForecast = array{
 *     time: string,
 *     data: array{
 *         instant: array{
 *             details: array{
 *                 air_pressure_at_sea_level: float,
 *                 air_temperature: float,
 *                 cloud_area_fraction: float,
 *                 cloud_area_fraction_high: float,
 *                 cloud_area_fraction_low: float,
 *                 cloud_area_fraction_medium: float,
 *                 dew_point_temperature: float,
 *                 fog_area_fraction: float,
 *                 relative_humidity: float,
 *                 ultraviolet_index_clear_sky: float,
 *                 wind_from_direction: float,
 *                 wind_speed: float,
 *                 wind_speed_of_gust: float,
 *             },
 *         },
 *         next_12_hours: array{
 *             summary: array{
 *                 symbol_code: string,
 *             },
 *             details: array{
 *                 probability_of_precipitation: float,
 *             },
 *         },
 *         next_1_hours: array{
 *             summary: array{
 *                 symbol_code: string,
 *             },
 *             details: array{
 *                 precipitation_amount: float,
 *                 precipitation_amount_max: float,
 *                 precipitation_amount_min: float,
 *                 probability_of_precipitation: float,
 *                 probability_of_thunder: float,
 *             },
 *         },
 *         next_6_hours: array{
 *             summary: array{
 *                 symbol_code: string,
 *             },
 *             details: array{
 *                 air_temperature_max: float,
 *                 air_temperature_min: float,
 *                 precipitation_amount: float,
 *                 precipitation_amount_max: float,
 *                 precipitation_amount_min: float,
 *                 probability_of_precipitation: float,
 *             },
 *         },
 *     },
 * }
 */
class ResponseDefinitions {
}
