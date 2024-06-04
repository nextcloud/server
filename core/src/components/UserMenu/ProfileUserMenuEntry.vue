<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<li :id="id"
		class="menu-entry">
		<component :is="profileEnabled ? 'a' : 'span'"
			class="menu-entry__wrapper"
			:class="{
				active,
				'menu-entry__wrapper--link': profileEnabled,
			}"
			:href="profileEnabled ? href : undefined"
			@click.exact="handleClick">
			<span class="menu-entry__content">
				<span class="menu-entry__displayname">{{ displayName }}</span>
				<NcLoadingIcon v-if="loading" :size="18" />
			</span>
			<span v-if="profileEnabled">{{ name }}</span>
		</component>
	</li>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { getCurrentUser } from '@nextcloud/auth'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'

import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

const { profileEnabled } = loadState('user_status', 'profileEnabled', false)

export default {
	name: 'ProfileUserMenuEntry',

	components: {
		NcLoadingIcon,
	},

	props: {
		id: {
			type: String,
			required: true,
		},
		name: {
			type: String,
			required: true,
		},
		href: {
			type: String,
			required: true,
		},
		active: {
			type: Boolean,
			required: true,
		},
	},

	data() {
		return {
			profileEnabled,
			displayName: getCurrentUser().displayName,
			loading: false,
		}
	},

	mounted() {
		subscribe('settings:profile-enabled:updated', this.handleProfileEnabledUpdate)
		subscribe('settings:display-name:updated', this.handleDisplayNameUpdate)
	},

	beforeDestroy() {
		unsubscribe('settings:profile-enabled:updated', this.handleProfileEnabledUpdate)
		unsubscribe('settings:display-name:updated', this.handleDisplayNameUpdate)
	},

	methods: {
		handleClick() {
			if (this.profileEnabled) {
				this.loading = true
			}
		},

		handleProfileEnabledUpdate(profileEnabled) {
			this.profileEnabled = profileEnabled
		},

		handleDisplayNameUpdate(displayName) {
			this.displayName = displayName
		},
	},
}
</script>

<style lang="scss" scoped>
.menu-entry {
	&__wrapper {
		box-sizing: border-box;
		display: inline-flex;
		flex-direction: column;
		align-items: flex-start !important;
		padding: 10px 12px 5px 12px !important;
		height: var(--header-menu-item-height);
		color: var(--color-text-maxcontrast);

		&--link {
			height: calc(var(--header-menu-item-height) * 1.5) !important;
			color: var(--color-main-text);
		}
	}

	&__content {
		display: inline-flex;
		gap: 0 10px;
	}

	&__displayname {
		font-weight: bold;
	}
}
</style>
