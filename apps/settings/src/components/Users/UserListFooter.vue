<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<tr class="footer">
		<th scope="row">
			<!-- TRANSLATORS Label for a table footer which summarizes the columns of the table -->
			<span class="hidden-visually">{{ t('settings', 'Total rows summary') }}</span>
		</th>
		<td class="footer__cell footer__cell--loading">
			<NcLoadingIcon v-if="loading"
				:title="t('settings', 'Loading accounts …')"
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
					'{userCount} account …',
					'{userCount} accounts …',
					this.filteredUsers.length,
					{
						userCount: this.filteredUsers.length,
					},
				)
			}
			return this.n(
				'settings',
				'{userCount} account',
				'{userCount} accounts',
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
@use './shared/styles';

.footer {
	@include styles.row;
	@include styles.cell;

	&__cell {
		position: sticky;
		color: var(--color-text-maxcontrast);

		&--loading {
			inset-inline-start: 0;
			min-width: var(--avatar-cell-width);
			width: var(--avatar-cell-width);
			align-items: center;
			padding: 0;
		}

		&--count {
			inset-inline-start: var(--avatar-cell-width);
			min-width: var(--cell-width);
			width: var(--cell-width);
		}
	}
}
</style>
