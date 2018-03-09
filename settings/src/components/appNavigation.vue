<template>
	<div id="app-navigation">
		<div class="app-navigation-new" v-if="menu.new">
			<button type="button" :id="menu.new.id" :class="menu.new.icon" @click="menu.new.action">{{menu.new.text}}</button>
		</div>
		<ul :id="menu.id">
			<navigation-item v-for="(item, key) in menu.items" :item="item" :key="key" />
		</ul>
		<div id="app-settings">
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
}
</script>
