/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

(function() {

	OCA.WorkflowEngine = OCA.WorkflowEngine || {};
	OCA.WorkflowEngine.Plugins = OCA.WorkflowEngine.Plugins || {};

	OCA.WorkflowEngine.Plugins.FileSystemTagsPlugin = {
		getCheck: function() {
			this.collection = OC.SystemTags.collection;

			return {
				'class': 'OCA\\WorkflowEngine\\Check\\FileSystemTags',
				'name': t('workflowengine', 'File system tag'),
				'operators': [
					{'operator': 'is', 'name': t('workflowengine', 'is tagged with')},
					{'operator': '!is', 'name': t('workflowengine', 'is not tagged with')}
				]
			};
		},
		render: function(element, check) {
			if (check['class'] !== 'OCA\\WorkflowEngine\\Check\\FileSystemTags') {
				return;
			}

			$(element).css('width', '400px');

			$(element).select2({
				allowClear: false,
				multiple: false,
				placeholder: t('workflowengine', 'Select tag…'),
				query: _.debounce(function(query) {
					query.callback({
						results: OC.SystemTags.collection.filterByName(query.term)
					});
				}, 100, true),
				id: function(element) {
					return element.get('id');
				},
				initSelection: function(element, callback) {
					callback($(element).val());
				},
				formatResult: function (tag) {
					return OC.SystemTags.getDescriptiveTag(tag);
				},
				formatSelection: function (tagId) {
					var tag = OC.SystemTags.collection.get(tagId);
					return OC.SystemTags.getDescriptiveTag(tag);
				},
				escapeMarkup: function(m) {
					return m;
				}
			});
		}
	};
})();

OC.Plugins.register('OCA.WorkflowEngine.CheckPlugins', OCA.WorkflowEngine.Plugins.FileSystemTagsPlugin);
