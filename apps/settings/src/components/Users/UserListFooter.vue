<!--
	- @copyright 2023 Christopher Ng <chrng8@gmail.com>
	-
	- @author Christopher Ng <chrng8@gmail.com>
	-
	- @license AGPL-3.0-or-later
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
	<tr class="footer">
		<th scope="row">
			<span class="hidden-visually">{{ t('settings', 'Total rows summary') }}</span>
		</th>
		<td class="footer__cell footer__cell--loading">
			<NcLoadingIcon v-if="loading"
				:title="t('settings', 'Loading users …')"
				:size="32" />
		</td>
		<td class="footer__cell footer__cell--count footer__cell--multiline">
			<span aria-describedby="user-count-desc">{{ userCount }}</span>
			<span id="user-count-desc"
				class="hidden-visually">
				{{ t('settings', 'Scroll to load more rows') }}
			</span>
		</td>
	</tr>
</template>

<script lang="ts">
import Vue from 'vue'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

import {
	translate as t,
	translatePlural as n,
} from '@nextcloud/l10n'

export default Vue.extend({
	name: 'UserListFooter',

	components: {
		NcLoadingIcon,
	},

	props: {
		loading: {
			type: Boolean,
			required: true,
		},
		filteredUsers: {
			type: Array,
			required: true,
		},
	},

	computed: {
		userCount(): string {
			if (this.loading) {
				return this.n(
					'settings',
					'{userCount} user …',
					'{userCount} users …',
					this.filteredUsers.length,
					{
						userCount: this.filteredUsers.length,
					},
				)
			}
			return this.n(
				'settings',
				'{userCount} user',
				'{userCount} users',
				 this.filteredUsers.length,
				{
					userCount: this.filteredUsers.length,
				},
			)
		},
	},

	methods: {
		t,
		n,
	},
})
</script>

<style lang="scss" scoped>
@import './shared/styles.scss';

.footer {
	@include row;
	@include cell;

	&__cell {
		position: sticky;
		color: var(--color-text-maxcontrast);

		&--loading {
			left: 0;
			min-width: var(--avatar-cell-width);
			width: var(--avatar-cell-width);
			align-items: center;
			padding: 0;
		}

		&--count {
			left: var(--avatar-cell-width);
			min-width: var(--cell-width);
			width: var(--cell-width);
		}
	}
}
</style>
