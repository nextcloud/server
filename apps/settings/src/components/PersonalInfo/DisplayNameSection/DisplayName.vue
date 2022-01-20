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
	<div class="displayname">
		<input id="displayname"
			type="text"
			:placeholder="t('settings', 'Your full name')"
			:value="displayName"
			autocapitalize="none"
			autocomplete="on"
			autocorrect="off"
			@input="onDisplayNameChange">

		<div class="displayname__actions-container">
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
import { validateStringInput } from '../../../utils/validate'

// TODO Global avatar updating on events (e.g. updating the displayname) is currently being handled by global js, investigate using https://github.com/nextcloud/nextcloud-event-bus for global avatar updating

export default {
	name: 'DisplayName',

	props: {
		displayName: {
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
			initialDisplayName: this.displayName,
			localScope: this.scope,
			showCheckmarkIcon: false,
			showErrorIcon: false,
		}
	},

	methods: {
		onDisplayNameChange(e) {
			this.$emit('update:display-name', e.target.value)
			this.debounceDisplayNameChange(e.target.value.trim())
		},

		debounceDisplayNameChange: debounce(async function(displayName) {
			if (validateStringInput(displayName)) {
				await this.updatePrimaryDisplayName(displayName)
			}
		}, 500),

		async updatePrimaryDisplayName(displayName) {
			try {
				const responseData = await savePrimaryAccountProperty(ACCOUNT_PROPERTY_ENUM.DISPLAYNAME, displayName)
				this.handleResponse({
					displayName,
					status: responseData.ocs?.meta?.status,
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to update full name'),
					error: e,
				})
			}
		},

		handleResponse({ displayName, status, errorMessage, error }) {
			if (status === 'ok') {
				// Ensure that local state reflects server state
				this.initialDisplayName = displayName
				emit('settings:display-name:updated', displayName)
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
.displayname {
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

	.displayname__actions-container {
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
