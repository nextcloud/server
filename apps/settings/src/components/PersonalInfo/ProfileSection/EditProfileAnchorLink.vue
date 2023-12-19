<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<a :class="{ disabled }"
		href="#profile-visibility"
		v-on="$listeners">
		<ChevronDownIcon class="anchor-icon"
			:size="22" />
		{{ t('settings', 'Edit your Profile visibility') }}
	</a>
</template>

<script>
import ChevronDownIcon from 'vue-material-design-icons/ChevronDown.vue'

export default {
	name: 'EditProfileAnchorLink',

	components: {
		ChevronDownIcon,
	},

	props: {
		profileEnabled: {
			type: Boolean,
			required: true,
		},
	},

	computed: {
		disabled() {
			return !this.profileEnabled
		},
	},
}
</script>

<style lang="scss">
html {
	scroll-behavior: smooth;

	@media screen and (prefers-reduced-motion: reduce) {
		scroll-behavior: auto;
	}
}
</style>

<style lang="scss" scoped>
a {
	display: block;
	height: 44px;
	width: min(100%, 290px);
	overflow: hidden;
	text-overflow: ellipsis;
	line-height: 44px;
	padding: 0 16px;
	margin: 14px auto;
	border-radius: var(--border-radius-pill);
	color: var(--color-text-maxcontrast);
	background-color: transparent;

	.anchor-icon {
		display: inline-block;
		vertical-align: middle;
		margin-top: 6px;
		margin-inline-end: 8px;
	}

	&:hover,
	&:focus,
	&:active {
		color: var(--color-main-text);
		background-color: var(--color-background-dark);
	}

	&.disabled {
		pointer-events: none;
	}
}
</style>
