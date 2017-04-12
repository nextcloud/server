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

	var BreadCrumbView = OC.Backbone.View.extend({
		tagName: 'span',
		events: {
			click: '_onClick'
		},
		_dirInfo: undefined,

		/** @type OCA.Sharing.ShareTabView */
		_shareTab: undefined,

		initialize: function(options) {
			this._shareTab = options.shareTab;
		},

		render: function(data) {
			this._dirInfo = data.dirInfo || null;

			if (this._dirInfo !== null && (this._dirInfo.path !== '/' || this._dirInfo.name !== '')) {
				var isShared = data.dirInfo && data.dirInfo.shareTypes && data.dirInfo.shareTypes.length > 0;
				this.$el.removeClass('shared icon-public icon-shared');
				if (isShared) {
					this.$el.addClass('shared');
					if (data.dirInfo.shareTypes.indexOf(OC.Share.SHARE_TYPE_LINK) !== -1) {
						this.$el.addClass('icon-public');
					} else {
						this.$el.addClass('icon-shared');
					}
				} else {
					this.$el.addClass('icon-shared');
				}
				this.$el.show();
				this.delegateEvents();
			} else {
				this.$el.removeClass('shared icon-public icon-shared');
				this.$el.hide();
			}

			return this;
		},
		_onClick: function(e) {
			e.preventDefault();

			var fileInfoModel = new OCA.Files.FileInfoModel(this._dirInfo);
			var self = this;
			fileInfoModel.on('change', function() {
				self.render({
					dirInfo: self._dirInfo
				});
			});
			this._shareTab.on('sharesChanged', function(shareModel) {
				var shareTypes = [];
				var shares = shareModel.getSharesWithCurrentItem();

				for(var i = 0; i < shares.length; i++) {
					if (shareTypes.indexOf(shares[i].share_type) === -1) {
						shareTypes.push(shares[i].share_type);
					}
				}

				if (shareModel.hasLinkShare()) {
					shareTypes.push(OC.Share.SHARE_TYPE_LINK);
				}

				// Since the dirInfo isn't updated we need to do this dark hackery
				self._dirInfo.shareTypes = shareTypes;

				self.render({
					dirInfo: self._dirInfo
				});
			});
			OCA.Files.App.fileList.showDetailsView(fileInfoModel, 'shareTabView');
		}
	});

	OCA.Sharing.ShareBreadCrumbView = BreadCrumbView;
})();
