<!--
 - @copyright Copyright (c) 2020 Daniel Kesselberg <mail@danielkesselberg.de>
 -
 - @author Daniel Kesselberg <mail@danielkesselberg.de>
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
	<VuePlyr v-if="davPath"
		ref="plyr"
		:options="options"
		:style="{
			height: height + 'px',
			width: width + 'px'
		}">
		<audio
			ref="audio"
			:autoplay="active"
			:src="davPath"
			preload="metadata"
			@ended="donePlaying"
			@canplay="doneLoading">

			<!-- Omitting `type` on purpose because most of the
				browsers auto detect the appropriate codec.
				Having it set force the browser to comply to
				the provided mime instead of detecting a potential
				compatibility. -->

			{{ t('viewer', 'Your browser does not support audio.') }}
		</audio>
	</VuePlyr>
</template>

<script>
import Vue from 'vue'
import VuePlyr from 'vue-plyr'
import { generateFilePath } from '@nextcloud/router'

Vue.use(VuePlyr)

export default {
	name: 'Audios',

	computed: {
		player() {
			return this.$refs.plyr.player
		},
		options() {
			return {
				controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'settings'],
				iconUrl: generateFilePath('viewer', 'img', 'plyr.svg'),
			}
		},
	},

	watch: {
		active(val, old) {
			// the item was hidden before and is now the current view
			if (val === true && old === false) {
				this.player.play()

			// the item was playing before and is now hidden
			} else if (val === false && old === true) {
				this.player.pause()
			}
		},
	},

	methods: {
		donePlaying() {
			this.$refs.audio.autoplay = false
			this.$refs.audio.load()
		},
	},
}
</script>

<style scoped lang="scss">
audio {
	background-color: black;
	max-width: 100%;
	max-height: 100%;
	align-self: center;
	justify-self: center;
	/* over arrows in tiny screens */
	z-index: 20050;
}

::v-deep {
	.plyr__progress__container {
		flex: 1 1;
	}
	.plyr__volume {
		min-width: 80px;
	}
	// plyr buttons style
	.plyr--audio .plyr__progress__buffer,
	.plyr--audio .plyr__control {
		&.plyr__tab-focus,
		&:hover,
		&[aria-expanded=true] {
			background-color: var(--color-primary-element);
			color: var(--color-primary-text);
			box-shadow: none !important;
		}
	}
	// plyr volume control
	.plyr--full-ui input[type=range] {
		color: var(--color-primary-element);
	}
}
</style>
