<!--
	- @copyright 2021, Christopher Ng <chrng8@gmail.com>
	-
	- @author Christopher Ng <chrng8@gmail.com>
	- @author Ferdinand Thiessen <opensource@fthiessen.de>
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
	<NcActions ref="federationActions"
		class="federation-actions"
		:class="{ 'federation-actions--additional': additional }"
		:aria-label="ariaLabel"
		:disabled="disabled">
		<template #icon>
			<NcIconSvgWrapper :path="scopeIcon" />
		</template>
		<FederationControlActions :additional="additional"
			:additional-value="additionalValue"
			:handle-additional-scope-change="handleAdditionalScopeChange"
			:readable="readable"
			:scope="scope"
			@update:scope="onUpdateScope" />
	</NcActions>
</template>

<script>
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import {
	ACCOUNT_PROPERTY_READABLE_ENUM,
	ACCOUNT_SETTING_PROPERTY_READABLE_ENUM,
	PROFILE_READABLE_ENUM,
	SCOPE_PROPERTY_ENUM,
} from '../../../constants/AccountPropertyConstants.js'
import FederationControlActions from './FederationControlActions.vue'

export default {
	name: 'FederationControl',

	components: {
		NcActions,
		NcIconSvgWrapper,
		FederationControlActions,
	},

	props: {
		readable: {
			type: String,
			required: true,
			validator: (value) => Object.values(ACCOUNT_PROPERTY_READABLE_ENUM).includes(value) || Object.values(ACCOUNT_SETTING_PROPERTY_READABLE_ENUM).includes(value) || value === PROFILE_READABLE_ENUM.PROFILE_VISIBILITY,
		},
		additional: {
			type: Boolean,
			default: false,
		},
		additionalValue: {
			type: String,
			default: '',
		},
		disabled: {
			type: Boolean,
			default: false,
		},
		handleAdditionalScopeChange: {
			type: Function,
			default: null,
		},
		scope: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			readableLowerCase: this.readable.toLocaleLowerCase(),
		}
	},

	computed: {
		ariaLabel() {
			return t('settings', 'Change scope level of {property}, current scope is {scope}', { property: this.readableLowerCase, scope: this.scopeDisplayNameLowerCase })
		},

		scopeDisplayNameLowerCase() {
			return SCOPE_PROPERTY_ENUM[this.scope].displayName.toLocaleLowerCase()
		},

		scopeIcon() {
			return SCOPE_PROPERTY_ENUM[this.scope].icon
		},
	},

	methods: {
		onUpdateScope(scope) {
			this.$emit('update:scope', scope)
			// TODO: provide focus method from NcActions
			this.$refs.federationActions.$refs.menuButton.$el.focus()
		},
	},
}
</script>

<style lang="scss" scoped>
.federation-actions {
	&--additional {
		&:deep(button) {
			// TODO remove this hack
			height: 30px !important;
			min-height: 30px !important;
			width: 30px !important;
			min-width: 30px !important;
		}
	}
}
</style>
