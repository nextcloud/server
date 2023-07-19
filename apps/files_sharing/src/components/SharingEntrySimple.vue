<!--
  - @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
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
		<NcActions v-if="$slots['default']"
			ref="actionsComponent"
			class="sharing-entry__actions"
			menu-align="right"
			:aria-expanded="ariaExpandedValue">
			<slot />
		</NcActions>
	</li>
</template>

<script>
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'

export default {
	name: 'SharingEntrySimple',

	components: {
		NcActions,
	},

	props: {
		title: {
			type: String,
			default: '',
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
		margin-left: auto !important;
	}
}
</style>
