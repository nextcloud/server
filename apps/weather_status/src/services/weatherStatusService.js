/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
