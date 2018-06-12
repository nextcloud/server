<!--
  - @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
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
	<div id="app-navigation" :class="{'icon-loading': menu.loading}">
		<div class="app-navigation-new" v-if="menu.new">
			<button type="button" :id="menu.new.id" :class="menu.new.icon" @click="menu.new.action">{{menu.new.text}}</button>
		</div>
		<ul :id="menu.id">
			<navigation-item v-for="item in menu.items" :item="item" :key="item.key" />
		</ul>
		<div id="app-settings" v-if="!!$slots['settings-content']">
			<div id="app-settings-header">
				<button class="settings-button"
						data-apps-slide-toggle="#app-settings-content"
				>{{t('settings', 'Settings')}}</button>
			</div>
			<div id="app-settings-content">
				<slot name="settings-content"></slot>
			</div>
		</div>
	</div>
</template>

<script>
import navigationItem from './appNavigation/navigationItem';

export default {
	name: 'appNavigation',
	props: ['menu'],
	components: {
		navigationItem
	}
};
</script>
