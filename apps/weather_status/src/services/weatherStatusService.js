/**
 * @copyright Copyright (c) 2020, Julien Veyssier
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import HttpClient from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

/**
 *
 *
 * @param {string} lat the latitude
 * @param {string} lon the longitude
 * @return {Promise<object>}
 */
const setLocation = async (lat, lon) => {
	const url = generateOcsUrl('apps/weather_status/api/v1/location')
	const response = await HttpClient.put(url, {
		address: '',
		lat,
		lon,
	})

	return response.data.ocs.data
}

/**
 *
 * @param {string} address The location
 * @return {Promise<object>}
 */
const setAddress = async (address) => {
	const url = generateOcsUrl('apps/weather_status/api/v1/location')
	const response = await HttpClient.put(url, {
		address,
		lat: null,
		lon: null,
	})

	return response.data.ocs.data
}

/**
 *
 * @param {string} mode can be 1 browser or 2 custom
 * @return {Promise<object>}
 */
const setMode = async (mode) => {
	const url = generateOcsUrl('apps/weather_status/api/v1/mode')
	const response = await HttpClient.put(url, {
		mode,
	})

	return response.data.ocs.data
}

/**
 *
 * @return {Promise<object>}
 */
const usePersonalAddress = async () => {
	const url = generateOcsUrl('apps/weather_status/api/v1/use-personal')
	const response = await HttpClient.put(url)

	return response.data.ocs.data
}

/**
 * Fetches the location information for current user
 *
 * @return {Promise<object>}
 */
const getLocation = async () => {
	const url = generateOcsUrl('apps/weather_status/api/v1/location')
	const response = await HttpClient.get(url)

	return response.data.ocs.data
}

/**
 * Fetches the weather forecast
 *
 * @return {Promise<object>}
 */
const fetchForecast = async () => {
	const url = generateOcsUrl('apps/weather_status/api/v1/forecast')
	const response = await HttpClient.get(url)

	return response.data.ocs.data
}

/**
 * Fetches the location favorites
 *
 * @return {Promise<object>}
 */
const getFavorites = async () => {
	const url = generateOcsUrl('apps/weather_status/api/v1/favorites')
	const response = await HttpClient.get(url)

	return response.data.ocs.data
}

/**
 *
 * @param {Array} favorites List of favorite addresses
 * @return {Promise<object>}
 */
const saveFavorites = async (favorites) => {
	const url = generateOcsUrl('apps/weather_status/api/v1/favorites')
	const response = await HttpClient.put(url, {
		favorites,
	})

	return response.data.ocs.data
}

export {
	usePersonalAddress,
	setMode,
	getLocation,
	setLocation,
	setAddress,
	fetchForecast,
	getFavorites,
	saveFavorites,
}
