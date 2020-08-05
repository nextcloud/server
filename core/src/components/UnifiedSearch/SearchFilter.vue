 <!--
  - @copyright Copyright (c) 2020 John Molakvoæ <skjnldsv@protonmail.com>
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
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<li>
		<a :title="t('core', 'Search for {name} only', { name })"
			class="unified-search__filter"
			href="#"
			@click.prevent="onClick">
			{{ filter }}
		</a>
	</li>
</template>

<script>
export default {
	name: 'SearchFilter',

	props: {
		type: {
			type: String,
			required: true,
		},
		name: {
			type: String,
			required: true,
		},
	},
	computed: {
		filter() {
			return `in:${this.type}`
		},
	},

	methods: {
		onClick() {
			this.$emit('click', this.filter)
		},
	},

}
</script>

<style lang="scss" scoped>
.unified-search__filter {
	height: 1em;
	margin-right: 5px;
	padding: 3px 8px;
	border-radius: 1em;
	background-color: var(--color-background-darker);

	&:active,
	&:focus,
	&:hover {
		background-color: var(--color-background-hover);
	}
}

</style>
