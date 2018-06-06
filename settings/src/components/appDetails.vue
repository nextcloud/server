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
	<div id="app-details-view" style="padding: 20px;">
		<a class="close icon-close" href="#" v-on:click="hideAppDetails"><span class="hidden-visually">Close</span></a>
		<h2>
			<div v-if="!app.preview" class="icon-settings-dark"></div>
			<svg v-if="app.previewAsIcon && app.preview" width="32" height="32" viewBox="0 0 32 32">
				<defs><filter :id="filterId"><feColorMatrix in="SourceGraphic" type="matrix" values="-1 0 0 0 1 0 -1 0 0 1 0 0 -1 0 1 0 0 0 1 0"></feColorMatrix></filter></defs>
				<image x="0" y="0" width="32" height="32" preserveAspectRatio="xMinYMin meet" :filter="filterUrl" :xlink:href="app.preview" class="app-icon"></image>
			</svg>
			{{ app.name }}</h2>
		<img v-if="app.screenshot" :src="app.screenshot" width="100%" />
		<div class="app-level" v-if="app.level === 200 || hasRating">
			<span class="official icon-checkmark" v-if="app.level === 200"
			  v-tooltip.auto="t('settings', 'Official apps are developed by and within the community. They offer central functionality and are ready for production use.')">
				{{ t('settings', 'Official') }}</span>
			<app-score v-if="hasRating" :score="app.appstoreData.ratingOverall"></app-score>
		</div>

		<div class="app-author" v-if="author">
			{{ t('settings', 'by') }}
			<span v-for="(a, index) in author">
				<a v-if="a['@attributes'] && a['@attributes']['homepage']" :href="a['@attributes']['homepage']">{{ a['@value'] }}</a><span v-else-if="a['@value']">{{ a['@value'] }}</span><span v-else>{{ a }}</span><span v-if="index+1 < author.length">, </span>
			</span>
		</div>
		<div class="app-licence" v-if="licence">{{ licence }}</div>
		<div class="actions">
			<div class="actions-buttons">
				<input v-if="app.update" class="update" type="button" :value="t('settings', 'Update to {version}', {version: app.update})" :disabled="installing || loading(app.id)"/>
				<input v-if="app.canUnInstall" class="uninstall" type="button" :value="t('settings', 'Remove')" :disabled="installing || loading(app.id)"/>
				<input v-if="app.active" class="enable" type="button" :value="t('settings','Disable')" v-on:click="disable(app.id)" :disabled="installing || loading(app.id)" />
				<input v-if="!app.active" class="enable" type="button" :value="enableButtonText" v-on:click="enable(app.id)" v-tooltip.auto="enableButtonTooltip" :disabled="!app.canInstall || installing || loading(app.id)" />
			</div>
			<div class="app-groups">
				<div class="groups-enable" v-if="app.active && canLimitToGroups(app)">
					<input type="checkbox" :value="app.id" v-model="groupCheckedAppsData" v-on:change="setGroupLimit" class="groups-enable__checkbox checkbox" :id="prefix('groups_enable', app.id)">
					<label :for="prefix('groups_enable', app.id)">Auf Gruppen beschränken</label>
					<input type="hidden" class="group_select" title="Alle" value="">
					<multiselect v-if="isLimitedToGroups(app)" :options="groups" :value="appGroups" @select="addGroupLimitation" @remove="removeGroupLimitation" :options-limit="5"
								 :placeholder="t('settings', 'Limit app usage to groups')"
								 label="name" track-by="id" class="multiselect-vue"
								 :multiple="true" :close-on-select="false">
						<span slot="noResult">{{t('settings', 'No results')}}</span>
					</multiselect>
				</div>
			</div>
		</div>

		<p class="documentation">
			<a class="appslink" :href="appstoreUrl" v-if="!app.internal" target="_blank" rel="noreferrer noopener">{{ t('settings', 'View in store')}} ↗</a>

			<a class="appslink" v-if="app.website" :href="app.website" target="_blank" rel="noreferrer noopener">{{ t('settings', 'Visit website') }} ↗</a>
			<a class="appslink" v-if="app.bugs" :href="app.bugs" target="_blank" rel="noreferrer noopener">{{ t('settings', 'Report a bug') }} ↗</a>

			<a class="appslink" v-if="app.documentation && app.documentation.user" :href="app.documentation.user" target="_blank" rel="noreferrer noopener">{{ t('settings', 'User documentation') }} ↗</a>
			<a class="appslink" v-if="app.documentation && app.documentation.admin" :href="app.documentation.admin" target="_blank" rel="noreferrer noopener">{{ t('settings', 'Admin documentation') }} ↗</a>
			<a class="appslink" v-if="app.documentation && app.documentation.developer" :href="app.documentation.developer" target="_blank" rel="noreferrer noopener">{{ t('settings', 'Developer documentation') }} ↗</a>
		</p>

		<ul class="app-dependencies">
			<li v-if="app.missingMinOwnCloudVersion">{{ t('settings', 'This app has no minimum Nextcloud version assigned. This will be an error in the future.') }}</li>
			<li v-if="app.missingMaxOwnCloudVersion">{{ t('settings', 'This app has no maximum Nextcloud version assigned. This will be an error in the future.') }}</li>
			<li v-if="!app.canInstall">
				{{ t('settings', 'This app cannot be installed because the following dependencies are not fulfilled:') }}
				<ul class="missing-dependencies">
					<li v-for="dep in app.missingDependencies">{{ dep }}</li>
				</ul>
			</li>
		</ul>

		<div class="app-description" v-html="renderMarkdown"></div>
	</div>
</template>

<script>
import Multiselect from 'vue-multiselect';
import AppScore from './appList/appScore';
import AppManagement from './appManagement';
import prefix from './prefixMixin';
import SvgFilterMixin from './svgFilterMixin';

export default {
	mixins: [AppManagement, prefix, SvgFilterMixin],
	name: 'appDetails',
	props: ['category', 'app'],
	components: {
		Multiselect,
		AppScore
	},
	data() {
		return {
			groupCheckedAppsData: false,
		}
	},
	mounted() {
		if (this.app.groups.length > 0) {
			this.groupCheckedAppsData = true;
		}
	},
	methods: {
		hideAppDetails() {
			this.$router.push({
				name: 'apps-category',
				params: {category: this.category}
			});
		},
	},
	computed: {
		appstoreUrl() {
			return `https://apps.nextcloud.com/apps/${this.app.id}`;
		},
		licence() {
			if (this.app.licence)
				return ('' + this.app.licence).toUpperCase() + t('settings', '-licensed');
			return null;
		},
		hasRating() {
			return this.app.appstoreData && this.app.appstoreData.ratingNumOverall > 5;
		},
		author() {
			if (typeof this.app.author === 'string') {
				return [
					{
						'@value': this.app.author
					}
				]
			}
			if (this.app.author['@value']) {
				return [this.app.author];
			}
			return this.app.author;
		},
		appGroups() {
			return this.app.groups.map(group => {return {id: group, name: group}});
		},
		groups() {
			return this.$store.getters.getGroups
				.filter(group => group.id !== 'disabled')
				.sort((a, b) => a.name.localeCompare(b.name));
		},
		renderMarkdown() {
			// TODO: bundle marked as well
			var renderer = new window.marked.Renderer();
			renderer.link = function(href, title, text) {
				try {
					var prot = decodeURIComponent(unescape(href))
						.replace(/[^\w:]/g, '')
						.toLowerCase();
				} catch (e) {
					return '';
				}

				if (prot.indexOf('http:') !== 0 && prot.indexOf('https:') !== 0) {
					return '';
				}

				var out = '<a href="' + href + '" rel="noreferrer noopener"';
				if (title) {
					out += ' title="' + title + '"';
				}
				out += '>' + text + '</a>';
				return out;
			};
			renderer.image = function(href, title, text) {
				if (text) {
					return text;
				}
				return title;
			};
			renderer.blockquote = function(quote) {
				return quote;
			};
			return DOMPurify.sanitize(
				window.marked(this.app.description.trim(), {
					renderer: renderer,
					gfm: false,
					highlight: false,
					tables: false,
					breaks: false,
					pedantic: false,
					sanitize: true,
					smartLists: true,
					smartypants: false
				}),
				{
					SAFE_FOR_JQUERY: true,
					ALLOWED_TAGS: [
						'strong',
						'p',
						'a',
						'ul',
						'ol',
						'li',
						'em',
						'del',
						'blockquote'
					]
				}
			);
		}
	}
}
</script>
