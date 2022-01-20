<!--
	- @copyright 2021, Christopher Ng <chrng8@gmail.com>
	-
	- @author Christopher Ng <chrng8@gmail.com>
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
	<div class="role">
		<input id="role"
			type="text"
			:placeholder="t('settings', 'Your role')"
			:value="role"
			autocapitalize="none"
			autocomplete="on"
			autocorrect="off"
			@input="onRoleChange">

		<div class="role__actions-container">
			<transition name="fade">
				<span v-if="showCheckmarkIcon" class="icon-checkmark" />
				<span v-else-if="showErrorIcon" class="icon-error" />
			</transition>
		</div>
	</div>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import debounce from 'debounce'

import { ACCOUNT_PROPERTY_ENUM } from '../../../constants/AccountPropertyConstants'
import { savePrimaryAccountProperty } from '../../../service/PersonalInfo/PersonalInfoService'

export default {
	name: 'Role',

	props: {
		role: {
			type: String,
			required: true,
		},
		scope: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			initialRole: this.role,
			localScope: this.scope,
			showCheckmarkIcon: false,
			showErrorIcon: false,
		}
	},

	methods: {
		onRoleChange(e) {
			this.$emit('update:role', e.target.value)
			this.debounceRoleChange(e.target.value.trim())
		},

		debounceRoleChange: debounce(async function(role) {
			await this.updatePrimaryRole(role)
		}, 500),

		async updatePrimaryRole(role) {
			try {
				const responseData = await savePrimaryAccountProperty(ACCOUNT_PROPERTY_ENUM.ROLE, role)
				this.handleResponse({
					role,
					status: responseData.ocs?.meta?.status,
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to update role'),
					error: e,
				})
			}
		},

		handleResponse({ role, status, errorMessage, error }) {
			if (status === 'ok') {
				// Ensure that local state reflects server state
				this.initialRole = role
				emit('settings:role:updated', role)
				this.showCheckmarkIcon = true
				setTimeout(() => { this.showCheckmarkIcon = false }, 2000)
			} else {
				showError(errorMessage)
				this.logger.error(errorMessage, error)
				this.showErrorIcon = true
				setTimeout(() => { this.showErrorIcon = false }, 2000)
			}
		},

		onScopeChange(scope) {
			this.$emit('update:scope', scope)
		},
	},
}
</script>

<style lang="scss" scoped>
.role {
	display: grid;
	align-items: center;

	input {
		grid-area: 1 / 1;
		width: 100%;
		height: 34px;
		margin: 3px 3px 3px 0;
		padding: 7px 6px;
		color: var(--color-main-text);
		border: 1px solid var(--color-border-dark);
		border-radius: var(--border-radius);
		background-color: var(--color-main-background);
		font-family: var(--font-face);
		cursor: text;
	}

	.role__actions-container {
		grid-area: 1 / 1;
		justify-self: flex-end;
		height: 30px;

		display: flex;
		gap: 0 2px;
		margin-right: 5px;

		.icon-checkmark,
		.icon-error {
			height: 30px !important;
			min-height: 30px !important;
			width: 30px !important;
			min-width: 30px !important;
			top: 0;
			right: 0;
			float: none;
		}
	}
}

.fade-enter,
.fade-leave-to {
	opacity: 0;
}

.fade-enter-active {
	transition: opacity 200ms ease-out;
}

.fade-leave-active {
	transition: opacity 300ms ease-out;
}
</style>
