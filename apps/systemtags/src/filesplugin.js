/*
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {
	OCA.SystemTags = _.extend({}, OCA.SystemTags)
	if (!OCA.SystemTags) {
		/**
		 * @namespace
		 */
		OCA.SystemTags = {}
	}

	/**
	 * @namespace
	 */
	OCA.SystemTags.FilesPlugin = {
		ignoreLists: [
			'trashbin',
			'files.public'
		],

		attach: function(fileList) {
			if (this.ignoreLists.indexOf(fileList.id) >= 0) {
				return
			}

			var systemTagsInfoView = new OCA.SystemTags.SystemTagsInfoView()
			fileList.registerDetailView(systemTagsInfoView)

			_.each(fileList.getRegisteredDetailViews(), function(detailView) {
				if (detailView instanceof OCA.Files.MainFileInfoDetailView) {
					var systemTagsInfoViewToggleView
						= new OCA.SystemTags.SystemTagsInfoViewToggleView({
							systemTagsInfoView: systemTagsInfoView
						})
					systemTagsInfoViewToggleView.render()

					// The toggle view element is detached before the
					// MainFileInfoDetailView is rendered to prevent its event
					// handlers from being removed.
					systemTagsInfoViewToggleView.listenTo(detailView, 'pre-render', function() {
						systemTagsInfoViewToggleView.$el.detach()
					})
					systemTagsInfoViewToggleView.listenTo(detailView, 'post-render', function() {
						detailView.$el.find('.file-details').append(systemTagsInfoViewToggleView.$el)
					})

				}
			})
		}
	}

})()

OC.Plugins.register('OCA.Files.FileList', OCA.SystemTags.FilesPlugin)
