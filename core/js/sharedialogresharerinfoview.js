/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* globals Handlebars */

(function() {
	if (!OC.Share) {
		OC.Share = {};
	}

	/**
	 * @class OCA.Share.ShareDialogView
	 * @member {OC.Share.ShareItemModel} model
	 * @member {jQuery} $el
	 * @memberof OCA.Sharing
	 * @classdesc
	 *
	 * Represents the GUI of the share dialogue
	 *
	 */
	var ShareDialogResharerInfoView = OC.Backbone.View.extend({
		/** @type {string} **/
		id: 'shareDialogResharerInfo',

		/** @type {string} **/
		tagName: 'div',

		/** @type {string} **/
		className: 'reshare',

		/** @type {OC.Share.ShareConfigModel} **/
		configModel: undefined,

		/** @type {Function} **/
		_template: undefined,

		initialize: function(options) {
			var view = this;

			this.model.on('change:reshare', function() {
				view.render();
			});

			if(!_.isUndefined(options.configModel)) {
				this.configModel = options.configModel;
			} else {
				throw 'missing OC.Share.ShareConfigModel';
			}
		},

		render: function() {
			if (!this.model.hasReshare()
				|| this.model.getReshareOwner() === OC.currentUser)
			{
				this.$el.empty();
				return this;
			}

			var reshareTemplate = this.template();
			var ownerDisplayName = this.model.getReshareOwnerDisplayname();
			var shareNote = this.model.getReshareNote();
			
			var sharedByText = '';

			if (this.model.getReshareType() === OC.Share.SHARE_TYPE_GROUP) {
				sharedByText = t(
					'core',
					'Shared with you and the group {group} by {owner}',
					{
						group: this.model.getReshareWithDisplayName(),
						owner: ownerDisplayName
					},
					undefined,
					{escape: false}
				);
			} else if (this.model.getReshareType() === OC.Share.SHARE_TYPE_CIRCLE) {
				sharedByText = t(
					'core',
					'Shared with you and {circle} by {owner}',
					{
						circle: this.model.getReshareWithDisplayName(),
						owner: ownerDisplayName
					},
					undefined,
					{escape: false}
				);
			} else if (this.model.getReshareType() === OC.Share.SHARE_TYPE_ROOM) {
				if (this.model.get('reshare').share_with_displayname) {
					sharedByText = t(
						'core',
						'Shared with you and the conversation {conversation} by {owner}',
						{
							conversation: this.model.getReshareWithDisplayName(),
							owner: ownerDisplayName
						},
						undefined,
						{escape: false}
					);
				} else {
					sharedByText = t(
						'core',
						'Shared with you in a conversation by {owner}',
						{
							owner: ownerDisplayName
						},
						undefined,
						{escape: false}
					);
				}
			} else {
				sharedByText = t(
					'core',
					'Shared with you by {owner}',
					{ owner: ownerDisplayName },
					undefined,
					{escape: false}
				);
			}



			this.$el.html(reshareTemplate({
				reshareOwner: this.model.getReshareOwner(),
				sharedByText: sharedByText,
				shareNote: shareNote,
				hasShareNote: shareNote !== ''
			}));

			this.$el.find('.avatar').each(function() {
				var $this = $(this);
				$this.avatar($this.data('username'), 32);
			});

			this.$el.find('.reshare').contactsMenu(
				this.model.getReshareOwner(),
				OC.Share.SHARE_TYPE_USER,
				this.$el);

			return this;
		},

		/**
		 * @returns {Function} from Handlebars
		 * @private
		 */
		template: function () {
			return OC.Share.Templates['sharedialogresharerinfoview'];
		}

	});

	OC.Share.ShareDialogResharerInfoView = ShareDialogResharerInfoView;

})();
