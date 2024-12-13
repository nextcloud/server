<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div id="weather-status-menu-item">
		<NcActions class="weather-status-menu-item__subheader"
			:aria-hidden="true"
			:aria-label="currentWeatherMessage"
			:menu-name="currentWeatherMessage">
			<template #icon>
				<NcLoadingIcon v-if="loading" />
				<img v-else
					:src="weatherIconUrl"
					alt=""
					class="weather-image">
			</template>
			<NcActionText v-if="gotWeather"
				:aria-hidden="true">
				<template #icon>
					<NcLoadingIcon v-if="loading" />
					<div v-else class="weather-action-image-container">
						<img :src="futureWeatherIconUrl"
							alt=""
							class="weather-image">
					</div>
				</template>
				{{ forecastMessage }}
			</NcActionText>
			<NcActionLink v-if="gotWeather"
				target="_blank"
				:aria-hidden="true"
				:href="weatherLinkTarget"
				:close-after-click="true">
				<template #icon>
					<NcIconSvgWrapper name="MapMarker"
						:svg="mapMarkerSvg"
						:size="20" />
				</template>
				{{ locationText }}
			</NcActionLink>
			<NcActionButton v-if="gotWeather"
				:aria-hidden="true"
				@click="onAddRemoveFavoriteClick">
				<template #icon>
					<NcIconSvgWrapper name="Star"
						:svg="addRemoveFavoriteSvg"
						:size="20"
						class="favorite-color" />
				</template>
				{{ addRemoveFavoriteText }}
			</NcActionButton>
			<NcActionSeparator v-if="address && !errorMessage" />
			<NcActionButton :close-after-click="true"
				:aria-hidden="true"
				@click="onBrowserLocationClick">
				<template #icon>
					<NcIconSvgWrapper name="Crosshairs"
						:svg="crosshairsSvg"
						:size="20" />
				</template>
				{{ t('weather_status', 'Detect location') }}
			</NcActionButton>
			<NcActionInput ref="addressInput"
				:label="t('weather_status', 'Set custom address')"
				:disabled="false"
				icon="icon-rename"
				:aria-hidden="true"
				type="text"
				value=""
				@submit="onAddressSubmit" />
			<template v-if="favorites.length > 0">
				<NcActionCaption :name="t('weather_status', 'Favorites')" />
				<NcActionButton v-for="favorite in favorites"
					:key="favorite"
					:aria-hidden="true"
					@click="onFavoriteClick($event, favorite)">
					<template #icon>
						<NcIconSvgWrapper name="Star"
							:svg="starSvg"
							:size="20"
							:class="{'favorite-color': address === favorite}" />
					</template>
					{{ favorite }}
				</NcActionButton>
			</template>
		</NcActions>
	</div>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import moment from '@nextcloud/moment'
import { getLocale } from '@nextcloud/l10n'
import { imagePath } from '@nextcloud/router'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActionCaption from '@nextcloud/vue/dist/Components/NcActionCaption.js'
import NcActionInput from '@nextcloud/vue/dist/Components/NcActionInput.js'
import NcActionLink from '@nextcloud/vue/dist/Components/NcActionLink.js'
import NcActionSeparator from '@nextcloud/vue/dist/Components/NcActionSeparator.js'
import NcActionText from '@nextcloud/vue/dist/Components/NcActionText.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import * as network from './services/weatherStatusService.js'
import crosshairsSvg from '@mdi/svg/svg/crosshairs.svg?raw'
import mapMarkerSvg from '@mdi/svg/svg/map-marker.svg?raw'
import starSvg from '@mdi/svg/svg/star.svg?raw'
import starOutlineSvg from '@mdi/svg/svg/star-outline.svg?raw'

const MODE_BROWSER_LOCATION = 1
const MODE_MANUAL_LOCATION = 2
const weatherOptions = {
	clearsky_day: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} clear sky later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} clear sky', { temperature, unit }),
	},
	clearsky_night: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} clear sky later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} clear sky', { temperature, unit }),
	},
	cloudy: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} cloudy later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} cloudy', { temperature, unit }),
	},
	snowandthunder: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} snow and thunder later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} snow and thunder', { temperature, unit }),
	},
	snowshowersandthunder_day: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} snow showers and thunder later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} snow showers and thunder', { temperature, unit }),
	},
	snowshowersandthunder_night: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} snow showers and thunder later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} snow showers and thunder', { temperature, unit }),
	},
	snowshowersandthunder_polartwilight: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} snow showers, thunder and polar twilight later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} snow showers, thunder and polar twilight', { temperature, unit }),
	},
	snowshowers_day: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} snow showers later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} snow showers', { temperature, unit }),
	},
	snowshowers_night: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} snow showers later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} snow showers', { temperature, unit }),
	},
	snowshowers_polartwilight: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} snow showers and polar twilight later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} snow showers and polar twilight', { temperature, unit }),
	},
	snow: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} snow later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} snow', { temperature, unit }),
	},
	fair_day: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} fair weather later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} fair weather', { temperature, unit }),
	},
	fair_night: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} fair weather later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} fair weather', { temperature, unit }),
	},
	partlycloudy_day: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} partly cloudy later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} partly cloudy', { temperature, unit }),
	},
	partlycloudy_night: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} partly cloudy later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} partly cloudy', { temperature, unit }),
	},
	fog: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} foggy later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} foggy', { temperature, unit }),
	},
	lightrain: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} light rainfall later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} light rainfall', { temperature, unit }),
	},
	rain: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} rainfall later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} rainfall', { temperature, unit }),
	},
	heavyrain: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} heavy rainfall later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} heavy rainfall', { temperature, unit }),
	},
	rainshowers_day: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} rainfall showers later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} rainfall showers', { temperature, unit }),
	},
	rainshowers_night: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} rainfall showers later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} rainfall showers', { temperature, unit }),
	},
	lightrainshowers_day: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} light rainfall showers later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} light rainfall showers', { temperature, unit }),
	},
	lightrainshowers_night: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} light rainfall showers later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} light rainfall showers', { temperature, unit }),
	},
	heavyrainshowers_day: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} heavy rainfall showers later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} heavy rainfall showers', { temperature, unit }),
	},
	heavyrainshowers_night: {
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} heavy rainfall showers later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} heavy rainfall showers', { temperature, unit }),
	},
}

export default {
	name: 'App',
	components: {
		NcActions,
		NcActionButton,
		NcActionCaption,
		NcActionInput,
		NcActionLink,
		NcActionSeparator,
		NcActionText,
		NcLoadingIcon,
		NcIconSvgWrapper,
	},
	data() {
		return {
			crosshairsSvg,
			mapMarkerSvg,
			starSvg,
			starOutlineSvg,
			locale: getLocale(),
			loading: true,
			errorMessage: '',
			mode: MODE_BROWSER_LOCATION,
			address: null,
			lat: null,
			lon: null,
			// how many hours ahead do we want to see the forecast?
			offset: 5,
			forecasts: [],
			loop: null,
			favorites: [],
		}
	},
	computed: {
		useFahrenheitLocale() {
			return ['en_US', 'en_MH', 'en_FM', 'en_PW', 'en_KY', 'en_LR'].includes(this.locale)
		},
		temperatureUnit() {
			return this.useFahrenheitLocale ? '°F' : '°C'
		},
		locationText() {
			return t('weather_status', 'More weather for {adr}', { adr: this.address })
		},
		temperature() {
			return this.getTemperature(this.forecasts, 0)
		},
		futureTemperature() {
			return this.getTemperature(this.forecasts, this.offset)
		},
		weatherCode() {
			return this.getWeatherCode(this.forecasts, 0)
		},
		futureWeatherCode() {
			return this.getWeatherCode(this.forecasts, this.offset)
		},
		weatherIconUrl() {
			return this.getWeatherIconUrl(this.weatherCode)
		},
		futureWeatherIconUrl() {
			return this.getWeatherIconUrl(this.futureWeatherCode)
		},
		/**
		 * The message displayed in the top right corner
		 *
		 * @return {string}
		 */
		currentWeatherMessage() {
			if (this.loading) {
				return t('weather_status', 'Loading weather')
			} else if (this.errorMessage) {
				return this.errorMessage
			} else if (this.gotWeather) {
				return this.getWeatherMessage(this.weatherCode, this.temperature)
			} else {
				return t('weather_status', 'Set location for weather')
			}
		},
		forecastMessage() {
			if (this.loading) {
				return t('weather_status', 'Loading weather')
			} else if (this.gotWeather) {
				return this.getWeatherMessage(this.futureWeatherCode, this.futureTemperature, true)
			} else {
				return t('weather_status', 'Set location for weather')
			}
		},
		weatherLinkTarget() {
			return 'https://www.windy.com/-Rain-thunder-rain?rain,' + this.lat + ',' + this.lon + ',11'
		},
		gotWeather() {
			return this.address && !this.errorMessage
		},
		addRemoveFavoriteSvg() {
			return this.currentAddressIsFavorite
				? starSvg
				: starOutlineSvg
		},
		addRemoveFavoriteText() {
			return this.currentAddressIsFavorite
				? t('weather_status', 'Remove from favorites')
				: t('weather_status', 'Add as favorite')
		},
		currentAddressIsFavorite() {
			return this.favorites.find((f) => {
				return f === this.address
			})
		},
	},
	mounted() {
		this.initWeatherStatus()
	},
	methods: {
		async initWeatherStatus() {
			try {
				const loc = await network.getLocation()
				this.lat = loc.lat
				this.lon = loc.lon
				this.address = loc.address
				this.mode = loc.mode

				if (this.mode === MODE_BROWSER_LOCATION) {
					this.askBrowserLocation()
				} else if (this.mode === MODE_MANUAL_LOCATION) {
					this.startLoop()
				}
				const favs = await network.getFavorites()
				this.favorites = favs
			} catch (err) {
				if (err?.code === 'ECONNABORTED') {
					console.info('The weather status request was cancelled because the user navigates.')
					return
				}
				if (err.response && err.response.status === 401) {
					showError(t('weather_status', 'You are not logged in.'))
				} else {
					showError(t('weather_status', 'There was an error getting the weather status information.'))
				}
				console.error(err)
			}
		},
		startLoop() {
			clearInterval(this.loop)
			if (this.lat && this.lon) {
				this.loop = setInterval(() => this.getForecast(), 60 * 1000 * 60)
				this.getForecast()
			} else {
				this.loading = false
			}
		},
		askBrowserLocation() {
			this.loading = true
			this.errorMessage = ''
			if (navigator.geolocation && window.isSecureContext) {
				navigator.geolocation.getCurrentPosition((position) => {
					console.debug('browser location success')
					this.lat = position.coords.latitude
					this.lon = position.coords.longitude
					this.saveMode(MODE_BROWSER_LOCATION)
					this.mode = MODE_BROWSER_LOCATION
					this.saveLocation(this.lat, this.lon)
				},
				(error) => {
					console.debug('location permission refused')
					console.debug(error)
					this.saveMode(MODE_MANUAL_LOCATION)
					this.mode = MODE_MANUAL_LOCATION
					// fallback on what we have if possible
					if (this.lat && this.lon) {
						this.startLoop()
					} else {
						this.usePersonalAddress()
					}
				})
			} else {
				console.debug('no secure context!')
				this.saveMode(MODE_MANUAL_LOCATION)
				this.mode = MODE_MANUAL_LOCATION
				this.startLoop()
			}
		},
		async getForecast() {
			try {
				this.forecasts = await network.fetchForecast()
			} catch (err) {
				this.errorMessage = t('weather_status', 'No weather information found')
				console.debug(err)
			}
			this.loading = false
		},
		async setAddress(address) {
			this.loading = true
			this.errorMessage = ''
			try {
				const loc = await network.setAddress(address)
				if (loc.success) {
					this.lat = loc.lat
					this.lon = loc.lon
					this.address = loc.address
					this.mode = MODE_MANUAL_LOCATION
					this.startLoop()
				} else {
					this.errorMessage = t('weather_status', 'Location not found')
					this.loading = false
				}
			} catch (err) {
				if (err.response && err.response.status === 401) {
					showError(t('weather_status', 'You are not logged in.'))
				} else {
					showError(t('weather_status', 'There was an error setting the location address.'))
				}
				this.loading = false
			}
		},
		async saveLocation(lat, lon) {
			try {
				const loc = await network.setLocation(lat, lon)
				this.address = loc.address
				this.startLoop()
			} catch (err) {
				if (err.response && err.response.status === 401) {
					showError(t('weather_status', 'You are not logged in.'))
				} else {
					showError(t('weather_status', 'There was an error setting the location.'))
				}
				console.debug(err)
			}
		},
		async saveMode(mode) {
			try {
				await network.setMode(mode)
			} catch (err) {
				if (err.response && err.response.status === 401) {
					showError(t('weather_status', 'You are not logged in.'))
				} else {
					showError(t('weather_status', 'There was an error saving the mode.'))
				}
				console.debug(err)
			}
		},
		onBrowserLocationClick() {
			this.askBrowserLocation()
		},
		async usePersonalAddress() {
			this.loading = true
			try {
				const loc = await network.usePersonalAddress()
				this.lat = loc.lat
				this.lon = loc.lon
				this.address = loc.address
				this.mode = MODE_MANUAL_LOCATION
				this.startLoop()
			} catch (err) {
				if (err.response && err.response.status === 401) {
					showError(t('weather_status', 'You are not logged in.'))
				} else {
					showError(t('weather_status', 'There was an error using personal address.'))
				}
				console.debug(err)
				this.loading = false
			}
		},
		onAddressSubmit() {
			const newAddress = this.$refs.addressInput.$el.querySelector('input[type="text"]').value
			this.setAddress(newAddress)
		},
		getLocalizedTemperature(celcius) {
			return this.useFahrenheitLocale
				? (celcius * (9 / 5)) + 32
				: celcius
		},
		onAddRemoveFavoriteClick() {
			const currentIsFavorite = this.currentAddressIsFavorite
			if (currentIsFavorite) {
				const i = this.favorites.indexOf(currentIsFavorite)
				if (i !== -1) {
					this.favorites.splice(i, 1)
				}
			} else {
				this.favorites.push(this.address)
			}
			network.saveFavorites(this.favorites)
		},
		onFavoriteClick(e, favAddress) {
			// clicked on the icon
			if (e.target.classList.contains('action-button__icon')) {
				const i = this.favorites.indexOf(favAddress)
				if (i !== -1) {
					this.favorites.splice(i, 1)
				}
				network.saveFavorites(this.favorites)
			} else if (favAddress !== this.address) {
				// clicked on the text
				this.setAddress(favAddress)
			}
		},
		formatTime(time) {
			return moment(time).format('LT')
		},
		getTemperature(forecasts, offset = 0) {
			return forecasts.length > offset ? forecasts[offset].data.instant.details.air_temperature : ''
		},
		getWeatherCode(forecasts, offset = 0) {
			return forecasts.length > offset ? forecasts[offset].data.next_1_hours.summary.symbol_code : ''
		},
		getWeatherIconUrl(weatherCode) {
			// those icons were obtained there: https://github.com/metno/weathericons/tree/main/weather/svg
			return (weatherCode && weatherCode in weatherOptions)
				? imagePath('weather_status', 'met.no.icons/' + weatherCode + '.svg')
				: imagePath('weather_status', 'met.no.icons/fair_day.svg')
		},
		getWeatherMessage(weatherCode, temperature, later = false) {
			return weatherCode && weatherCode in weatherOptions
				? weatherOptions[weatherCode].text(
					Math.round(this.getLocalizedTemperature(temperature)),
					this.temperatureUnit,
					later,
				)
				: t('weather_status', 'Unknown weather code')
		},
	},
}
</script>

<style lang="scss">
.weather-action-image-container {
	width: var(--default-clickable-area);
	height: var(--default-clickable-area);
	display: flex;
	align-items: center;
	justify-content: center;
}

.weather-image {
	width: calc(var(--default-clickable-area) - 2 * var(--default-grid-baseline));
}

// Set color to primary element for current / active favorite address
.favorite-color {
	color: var(--color-favorite);
}
</style>
