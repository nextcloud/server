/* global Handlebars, OC */

/**
 * @copyright 2016 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2016 Christoph Wurst <christoph@winzerhof-wurst.at>
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
	'use strict';

	var TEMPLATE = '<span class="icon icon-share {{#if isShared}}shared{{/if}}"></span>';

	var BreadCrumbView = OC.Backbone.View.extend({
		tagName: 'span',
		events: {
			click: '_onClick'
		},
		_dirInfo: undefined,
		_template: undefined,
		template: function(data) {
			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}
			return this._template(data);
		},
		render: function(data) {
			this._dirInfo = data.dirInfo || null;

			if (this._dirInfo !== null && (this._dirInfo.path !== '/' || this._dirInfo.name !== '')) {
				var isShared = data.dirInfo && data.dirInfo.shareTypes && data.dirInfo.shareTypes.length > 0;
				this.$el.html(this.template({
					isShared: isShared
				}));
				this.$el.show();
				this.delegateEvents();
			} else {
				this.$el.empty();
				this.$el.hide();
			}

			return this;
		},
		_onClick: function(e) {
			e.preventDefault();

			var fileInfoModel = new OCA.Files.FileInfoModel(this._dirInfo);
			var self = this;
			fileInfoModel.on('change', function() {
				console.log('CHANGE');
				self.render({
					dirInfo: self._dirInfo
				});
			});
			OCA.Files.App.fileList.showDetailsView(fileInfoModel, 'shareTabView');
		}
	});

	OCA.Sharing.ShareBreadCrumbView = BreadCrumbView;
})();

