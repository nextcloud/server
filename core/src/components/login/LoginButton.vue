<!--
  - @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<div class="submit-wrapper" @click="$emit('click')">
		<input type="submit"
			class="submit-wrapper__input primary"
			title=""
			:value="!loading ? value : valueLoading">
		<div v-if="loading" class="submit-wrapper__icon icon-loading-small-dark" />
		<ArrowRight v-else class="submit-wrapper__icon" />
	</div>
</template>

<script>
import ArrowRight from 'vue-material-design-icons/ArrowRight.vue'

export default {
	name: 'LoginButton',
	components: {
		ArrowRight,
	},
	props: {
		value: {
			type: String,
			default: t('core', 'Log in'),
		},
		valueLoading: {
			type: String,
			default: t('core', 'Logging in …'),
		},
		loading: {
			type: Boolean,
			required: true,
		},
		invertedColors: {
			type: Boolean,
			default: false,
		},
	},
}
</script>

<style scoped lang="scss">
.submit-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px 5px;
    position: relative;
	margin: 0 auto;

	&__input {
		width: 260px;
		height: 50px;
	}

	&__icon {
		display: flex;
		position: absolute;
		right: 24px;
		transition: right 100ms ease-in-out;
		/* The submit icon is positioned on the submit button.
		From the user point of view the icon is part of the
		button, so the clicks on the icon have to be
		applied to the button instead. */
		pointer-events: none;
	}

	&__input:hover + &__icon:not(.icon-loading-small-dark),
	&__input:focus + &__icon:not(.icon-loading-small-dark),
	&__input:active + &__icon:not(.icon-loading-small-dark) {
		right: 20px;
	}
}
</style>
