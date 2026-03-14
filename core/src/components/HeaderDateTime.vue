<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<span v-if="enabled" class="header-date-time" :title="localizedDateTime">
		{{ localizedDateTime }}
	</span>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
	name: 'HeaderDateTime',

	props: {
		enabled: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			localizedDateTime: '',
			clockInterval: null as number | null,
		}
	},

	watch: {
		enabled: {
			immediate: true,
			handler(enabled: boolean) {
				if (!enabled) {
					this.stopClock()
					return
				}

				this.updateDateTime()
				this.startClock()
			},
		},
	},

	beforeDestroy() {
		this.stopClock()
	},

	methods: {
		startClock() {
			if (this.clockInterval !== null) {
				return
			}

			this.clockInterval = window.setInterval(this.updateDateTime, 30000)
		},

		stopClock() {
			if (this.clockInterval === null) {
				return
			}

			window.clearInterval(this.clockInterval)
			this.clockInterval = null
		},

		updateDateTime() {
			this.localizedDateTime = new Intl.DateTimeFormat(undefined, {
				dateStyle: 'short',
				timeStyle: 'short',
			}).format(new Date())
		},
	},
})
</script>

<style lang="scss" scoped>
.header-date-time {
	margin-inline-end: calc(2 * var(--default-grid-baseline));
	white-space: nowrap;
	font-size: var(--font-size-small);
	line-height: var(--header-height);
	color: var(--color-background-plain-text);
	user-select: none;
}
</style>