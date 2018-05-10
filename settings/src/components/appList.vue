<!--
  - @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
  -
  - @author Julius Härtl <jus@bitgrid.net>
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
	<div id="app-content">
		<div id="apps-list" class="installed">
			<div class="apps-header" v-if="category === 'app-bundles'">
				<div class="app-image"></div>
				<h2>Firmen-Paket <input class="enable" type="submit" data-bundleid="EnterpriseBundle" data-active="true" value="Alle aktivieren"></h2>
				<div class="app-version"></div>
				<div class="app-level"></div>
				<div class="app-groups"></div>
				<div class="actions">&nbsp;</div>
			</div>

			<div class="section" v-for="app in apps">
				<div class="app-image app-image-icon">
					<svg width="32" height="32" viewBox="0 0 32 32">
						<defs><filter id="invertIconApps-606"><feColorMatrix in="SourceGraphic" type="matrix" values="-1 0 0 0 1 0 -1 0 0 1 0 0 -1 0 1 0 0 0 1 0"></feColorMatrix></filter></defs>
						<image x="0" y="0" width="32" height="32" preserveAspectRatio="xMinYMin meet" filter="url(#invertIconApps-606)" xlink:href="/core/img/places/default-app-icon.svg?v=13.0.2.1" class="app-icon"></image>
					</svg>
				</div>
				<div class="app-name">
					{{ app.name }}
				</div>
				<div class="app-version">{{ app.version }}</div>
				<div class="app-level">
					<a href="https://apps.nextcloud.com/apps/apporder">Im Store anzeigen ↗</a>
				</div>

				<div class="app-groups">
					<div class="groups-enable">
						<input type="checkbox" class="groups-enable__checkbox checkbox" id="groups_enable-apporder">
						<label for="groups_enable-apporder">Auf Gruppen beschränken</label>
						<input type="hidden" class="group_select" title="Alle" value="">
					</div>
				</div>

				<div class="actions">
					<div class="warning hidden"></div>
					<input class="update hidden" type="submit" value="Aktualisierung auf false" data-appid="apporder">
					<input class="enable" type="submit" data-appid="apporder" data-active="true" value="Deaktivieren">
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import userRow from './userList/userRow';
import Multiselect from 'vue-multiselect';
import InfiniteLoading from 'vue-infinite-loading';
import Vue from 'vue';

export default {
	name: 'appList',
	props: ['category'],
	components: {
		Multiselect,
	},
	data() {
		return {
			loading: false,
			scrolled: false,
		};
	},
	watch: {
		// watch url change and group select
		category: function (val, old) {
			this.$store.commit('resetApps');
			this.$store.dispatch('getApps', { category: this.category });
		}
	},
	mounted() {
		this.$store.dispatch('getApps', { category: this.category });
	},
	computed: {
		apps() {
			return this.$store.getters.getApps;
		},
	},
	methods: {
		createUser() {
			this.loading = true;
			this.$store.dispatch('addUser', {
				userid: this.newUser.id,
				password: this.newUser.password,
				email: this.newUser.mailAddress,
				groups: this.newUser.groups.map(group => group.id),
				subadmin: this.newUser.subAdminsGroups.map(group => group.id),
				quota: this.newUser.quota.id,
				language: this.newUser.language.code,
			}).then(() => this.resetForm());
		}
	}
}
</script>
