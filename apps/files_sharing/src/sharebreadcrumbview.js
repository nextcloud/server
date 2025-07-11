/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { ShareType } from '@nextcloud/sharing'

(function() {
	'use strict'

	const BreadCrumbView = OC.Backbone.View.extend({
		tagName: 'span',
		events: {
			click: '_onClick',
		},
		_dirInfo: undefined,

		render(data) {
			this._dirInfo = data.dirInfo || null

			if (this._dirInfo !== null && (this._dirInfo.path !== '/' || this._dirInfo.name !== '')) {
				const isShared = data.dirInfo && data.dirInfo.shareTypes && data.dirInfo.shareTypes.length > 0
				this.$el.removeClass('shared icon-public icon-shared')
				if (isShared) {
					this.$el.addClass('shared')
					if (data.dirInfo.shareTypes.indexOf(ShareType.Link) !== -1) {
						this.$el.addClass('icon-public')
					} else {
						this.$el.addClass('icon-shared')
					}
				} else {
					this.$el.addClass('icon-shared')
				}
				this.$el.show()
				this.delegateEvents()
			} else {
				this.$el.removeClass('shared icon-public icon-shared')
				this.$el.hide()
			}

			return this
		},
		_onClick(e) {
			e.preventDefault()
			e.stopPropagation()

			const fileInfoModel = new OCA.Files.FileInfoModel(this._dirInfo)
			const self = this
			fileInfoModel.on('change', function() {
				self.render({
					dirInfo: self._dirInfo,
				})
			})

			const path = fileInfoModel.attributes.path + '/' + fileInfoModel.attributes.name
			OCA.Files.Sidebar.open(path)
			OCA.Files.Sidebar.setActiveTab('sharing')
		},
	})

	OCA.Sharing.ShareBreadCrumbView = BreadCrumbView
})()
