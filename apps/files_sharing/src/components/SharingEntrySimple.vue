<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<li class="sharing-entry">
		<slot name="avatar" />
		<div class="sharing-entry__desc">
			<span class="sharing-entry__title">{{ title }}</span>
			<p v-if="subtitle">
				{{ subtitle }}
			</p>
		</div>
		<!-- Standalone action(s) shown before the overflow menu (e.g. a caret) -->
		<slot name="action" />
		<NcActions
			v-if="$slots['default']"
			ref="actionsComponent"
			class="sharing-entry__actions"
			menu-align="right"
			:force-menu="forceMenu"
			:aria-expanded="ariaExpandedValue">
			<slot />
		</NcActions>
	</li>
</template>

<script>
import NcActions from '@nextcloud/vue/components/NcActions'

export default {
	name: 'SharingEntrySimple',

	components: {
		NcActions,
	},

	props: {
		title: {
			type: String,
			required: true,
		},

		subtitle: {
			type: String,
			default: '',
		},

		isUnique: {
			type: Boolean,
			default: true,
		},

		ariaExpanded: {
			type: Boolean,
			default: null,
		},

		// Force the overflow menu even with a single action (keeps destructive
		// actions in a menu instead of rendering them inline).
		forceMenu: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		ariaExpandedValue() {
			if (this.ariaExpanded === null) {
				return this.ariaExpanded
			}
			return this.ariaExpanded ? 'true' : 'false'
		},
	},
}
</script>

<style lang="scss" scoped>
.sharing-entry {
	display: flex;
	align-items: center;
	min-height: 44px;
	&__desc {
		padding: 8px;
		padding-inline-start: 10px;
		line-height: 1.2em;
		position: relative;
		flex: 1 1;
		min-width: 0;
		p {
			color: var(--color-text-maxcontrast);
		}
	}
	&__title {
		white-space: nowrap;
		text-overflow: ellipsis;
		overflow: hidden;
		max-width: inherit;
	}
	&__actions {
		margin-inline-start: auto !important;
	}
}
</style>
