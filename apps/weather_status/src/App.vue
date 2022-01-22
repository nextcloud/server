<!--
  - @copyright Copyright (c) 2020 Julien Veyssier <eneiluj@posteo.net>
  - @author Julien Veyssier <eneiluj@posteo.net>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<li :class="{ inline }">
		<div id="weather-status-menu-item">
			<Actions class="weather-status-menu-item__subheader"
				:default-icon="weatherIcon"
				:menu-title="currentWeatherMessage">
				<ActionText v-if="gotWeather"
					:icon="futureWeatherIcon">
					{{ forecastMessage }}
				</ActionText>
				<ActionLink v-if="gotWeather"
					icon="icon-address"
					target="_blank"
					:href="weatherLinkTarget"
					:close-after-click="true">
					{{ locationText }}
				</ActionLink>
				<ActionButton v-if="gotWeather"
					:icon="addRemoveFavoriteIcon"
					@click="onAddRemoveFavoriteClick">
					{{ addRemoveFavoriteText }}
				</ActionButton>
				<ActionSeparator v-if="address && !errorMessage" />
				<ActionButton icon="icon-crosshair"
					:close-after-click="true"
					@click="onBrowserLocationClick">
					{{ t('weather_status', 'Detect location') }}
				</ActionButton>
				<ActionInput ref="addressInput"
					:disabled="false"
					icon="icon-rename"
					type="text"
					value=""
					@submit="onAddressSubmit">
					{{ t('weather_status', 'Set custom address') }}
				</ActionInput>
				<ActionButton v-show="favorites.length > 0"
					:icon="toggleFavoritesIcon"
					@click="showFavorites = !showFavorites">
					{{ t('weather_status', 'Favorites') }}
				</ActionButton>
				<ActionButton v-for="f in displayedFavorites"
					:key="f"
					icon="icon-starred"
					@click="onFavoriteClick($event, f)">
					{{ f }}
				</ActionButton>
			</Actions>
		</div>
	</li>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import moment from '@nextcloud/moment'
import { getLocale } from '@nextcloud/l10n'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import ActionLink from '@nextcloud/vue/dist/Components/ActionLink'
import ActionSeparator from '@nextcloud/vue/dist/Components/ActionSeparator'
import ActionText from '@nextcloud/vue/dist/Components/ActionText'
import * as network from './services/weatherStatusService'

const MODE_BROWSER_LOCATION = 1
const MODE_MANUAL_LOCATION = 2
const weatherOptions = {
	clearsky_day: {
		icon: 'icon-clearsky-day',
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} clear sky later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} clear sky', { temperature, unit }),
	},
	clearsky_night: {
		icon: 'icon-clearsky-night',
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} clear sky later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} clear sky', { temperature, unit }),
	},
	cloudy: {
		icon: 'icon-cloudy',
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} cloudy later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} cloudy', { temperature, unit }),
	},
	fair_day: {
		icon: 'icon-fair-day',
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} fair weather later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} fair weather', { temperature, unit }),
	},
	fair_night: {
		icon: 'icon-fair-night',
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} fair weather later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} fair weather', { temperature, unit }),
	},
	partlycloudy_day: {
		icon: 'icon-partlycloudy-day',
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} partly cloudy later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} partly cloudy', { temperature, unit }),
	},
	partlycloudy_night: {
		icon: 'icon-partlycloudy-night',
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} partly cloudy later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} partly cloudy', { temperature, unit }),
	},
	fog: {
		icon: 'icon-fog',
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} foggy later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} foggy', { temperature, unit }),
	},
	lightrain: {
		icon: 'icon-lightrain',
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} light rain later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} light rain', { temperature, unit }),
	},
	rain: {
		icon: 'icon-rain',
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} rain later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} rain', { temperature, unit }),
	},
	heavyrain: {
		icon: 'icon-heavyrain',
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} heavy rain later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} heavy rain', { temperature, unit }),
	},
	rainshowers_day: {
		icon: 'icon-rainshowers-day',
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} rain showers later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} rain showers', { temperature, unit }),
	},
	rainshowers_night: {
		icon: 'icon-rainshowers-night',
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} rain showers later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} rain showers', { temperature, unit }),
	},
	lightrainshowers_day: {
		icon: 'icon-light-rainshowers-day',
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} light rain showers later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} light rain showers', { temperature, unit }),
	},
	lightrainshowers_night: {
		icon: 'icon-light-rainshowers-night',
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} light rain showers later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} light rain showers', { temperature, unit }),
	},
	heavyrainshowers_day: {
		icon: 'icon-heavy-rainshowers-day',
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} heavy rain showers later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} heavy rain showers', { temperature, unit }),
	},
	heavyrainshowers_night: {
		icon: 'icon-heavy-rainshowers-night',
		text: (temperature, unit, later = false) => later
			? t('weather_status', '{temperature} {unit} heavy rain showers later today', { temperature, unit })
			: t('weather_status', '{temperature} {unit} heavy rain showers', { temperature, unit }),
	},
}

export default {
	name: 'App',
	components: {
		Actions, ActionButton, ActionInput, ActionLink, ActionSeparator, ActionText,
	},
	props: {
		inline: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
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
			showFavorites: false,
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
		weatherIcon() {
			return this.getWeatherIcon(this.weatherCode, this.loading)
		},
		futureWeatherIcon() {
			return this.getWeatherIcon(this.futureWeatherCode, this.loading)
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
			} else {
				return this.getWeatherMessage(this.weatherCode, this.temperature)
			}
		},
		forecastMessage() {
			if (this.loading) {
				return t('weather_status', 'Loading weather')
			} else {
				return this.getWeatherMessage(this.futureWeatherCode, this.futureTemperature, true)
			}
		},
		weatherLinkTarget() {
			return 'https://www.windy.com/-Rain-thunder-rain?rain,' + this.lat + ',' + this.lon + ',11'
		},
		gotWeather() {
			return this.address && !this.errorMessage
		},
		addRemoveFavoriteIcon() {
			return this.currentAddressIsFavorite
				? 'icon-starred'
				: 'icon-star'
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
		toggleFavoritesIcon() {
			return this.showFavorites
				? 'icon-triangle-s'
				: 'icon-triangle-e'
		},
		displayedFavorites() {
			return this.showFavorites
				? this.favorites
				: []
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
		getWeatherIcon(weatherCode, loading) {
			if (loading) {
				return 'icon-loading-small'
			} else {
				return weatherCode && weatherCode in weatherOptions
					? weatherOptions[weatherCode].icon
					: 'icon-fair-day'
			}
		},
		getWeatherMessage(weatherCode, temperature, later = false) {
			return weatherCode && weatherCode in weatherOptions
				? weatherOptions[weatherCode].text(
					Math.round(this.getLocalizedTemperature(temperature)),
					this.temperatureUnit,
					later
				)
				: t('weather_status', 'Set location for weather')
		},
	},
}
</script>

<style lang="scss">
.icon-weather-status {
	background-image: url('./../img/app-dark.svg');
}
body.theme--dark .icon-weather-status {
	background-image: url('./../img/app.svg');
}
.icon-clearsky-day {
	background-image: url('./../img/sun.svg');
}
.icon-clearsky-night {
	background-image: url('./../img/moon.svg');
}
.icon-cloudy {
	background-image: url('./../img/cloud-cloud.svg');
}
.icon-fair-day {
	background-image: url('./../img/sun-small-cloud.svg');
}
.icon-fair-night {
	background-image: url('./../img/moon-small-cloud.svg');
}
.icon-partlycloudy-day {
	background-image: url('./../img/sun-cloud.svg');
}
.icon-partlycloudy-night {
	background-image: url('./../img/moon-cloud.svg');
}
.icon-fog {
	background-image: url('./../img/fog.svg');
}
.icon-lightrain {
	background-image: url('./../img/light-rain.svg');
}
.icon-rain {
	background-image: url('./../img/rain.svg');
}
.icon-heavyrain {
	background-image: url('./../img/heavy-rain.svg');
}
.icon-light-rainshowers-day {
	background-image: url('./../img/sun-cloud-light-rain.svg');
}
.icon-light-rainshowers-night {
	background-image: url('./../img/moon-cloud-light-rain.svg');
}
.icon-rainshowers-day {
	background-image: url('./../img/sun-cloud-rain.svg');
}
.icon-rainshowers-night {
	background-image: url('./../img/moon-cloud-rain.svg');
}
.icon-heavy-rainshowers-day {
	background-image: url('./../img/sun-cloud-heavy-rain.svg');
}
.icon-heavy-rainshowers-night {
	background-image: url('./../img/moon-cloud-heavy-rain.svg');
}
.icon-crosshair {
    background-color: var(--color-main-text);
    padding: 0 !important;
    mask: url(./../img/cross.svg) no-repeat;
    mask-size: 18px 18px;
    mask-position: center;
    -webkit-mask: url(./../img/cross.svg) no-repeat;
    -webkit-mask-size: 18px 18px;
    -webkit-mask-position: center;
    min-width: 44px !important;
    min-height: 44px !important;
}

li:not(.inline) .weather-status-menu-item {
	&__header {
		display: block;
		align-items: center;
		color: var(--color-main-text);
		padding: 10px 12px 5px 12px;
		box-sizing: border-box;
		opacity: 1;
		white-space: nowrap;
		width: 100%;
		text-align: center;
		max-width: 250px;
		text-overflow: ellipsis;
		min-width: 175px;
	}

	&__subheader {
		width: 100%;

		.trigger > .icon {
			background-color: var(--color-main-background);
			background-size: 16px;
			border: 0;
			border-radius: 0;
			font-weight: normal;
			padding-left: 40px;

			&:hover,
			&:focus {
				box-shadow: inset 4px 0 var(--color-primary-element);
			}
		}
	}
}

.inline .weather-status-menu-item__subheader {
	width: 100%;

	.trigger > .icon {
		background-size: 16px;
		border: 0;
		border-radius: var(--border-radius-pill);
		font-weight: normal;
		padding-left: 40px;

		&.icon-loading-small {
			&::after {
				left: 21px;
			}
		}
	}
}

li {
	list-style-type: none;
}
</style>
