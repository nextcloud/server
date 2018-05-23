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
		<h2>{{ app.name }}</h2>
		<img :src="app.preview" width="100%" />
		<app-score v-if="app.ratingNumThresholdReached" :score="app.score"></app-score>
		<div class="app-author">
			{{ author }}
			{{ licence }}
		</div>
		<div class="actions">
			<div class="warning hidden"></div>
			<input v-if="app.update" class="update" type="button" :value="t('settings', 'Update to %s', app.update)" />
			<input v-if="app.canUnInstall" class="uninstall" type="button" :value="t('settings', 'Remove')" />
			<input v-if="app.active" class="enable" type="button" :value="t('settings','Disable')" v-on:click="disable(app.id)" />
			<input v-if="!app.active && !app.needsDownload" class="enable" type="button" :value="t('settings','Enable')" v-on:click="enable(app.id)" :disabled="!app.canInstall" />
			<input v-if="!app.active && app.needsDownload" class="enable needs-download" type="button" :value="t('settings', 'Enable')" :disabled="!app.canInstall"/>
		</div>
		<p class="documentation">
			<a class="appslink" v-if="app.website" :href="app.website" target="_blank" rel="noreferrer noopener">{{ t('settings', 'Visit website') }} ↗</a>
			<a class="appslink" v-if="app.bugs" :href="app.bugs" target="_blank" rel="noreferrer noopener">{{ t('settings', 'Report a bug') }} ↗</a>

			<a class="appslink" v-if="app.documentation.user" :href="app.documentation.user" target="_blank" rel="noreferrer noopener">{{ t('settings', 'User documentation') }} ↗</a>
			<a class="appslink" v-if="app.documentation.admin" :href="app.documentation.admin" target="_blank" rel="noreferrer noopener">{{ t('settings', 'Admin documentation') }} ↗</a>
			<a class="appslink" v-if="app.documentation.developer" :href="app.documentation.developer" target="_blank" rel="noreferrer noopener">{{ t('settings', 'Developer documentation') }} ↗</a>
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
export default {
	name: 'appDetails',
	props: ['app'],
	components: {
		Multiselect,
		AppScore
	},
	computed: {
		licence() {
			return this.app.license + t('settings', '-licensed');
		},
		author() {
			return t('settings', 'by') + ' ' + this.app.author;
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
