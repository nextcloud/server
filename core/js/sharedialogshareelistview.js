/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {
	if (!OC.Share) {
		OC.Share = {};
	}

	var TEMPLATE =
			'<ul id="shareWithList">' +
			'{{#each sharees}}' +
			'    <li data-share-type="{{shareType}}" data-share-with="{{shareWith}}" title="{{shareWith}}">' +
			'        <a href="#" class="unshare"><img class="svg" alt="{{unshareLabel}}" title="{{unshareLabel}}" src="{{unshareImage}}" /></a>' +
			'        {{#if avatarEnabled}}' +
			'        <div class="avatar"></div>' +
			'        {{/if}}' +
			'        <span class="username">{{shareWithDisplayName}}</span>' +
			'        {{#if mailPublicNotificationEnabled}} {{#unless isRemoteShare}}' +
			'        <label><input type="checkbox" name="mailNotification" class="mailNotification" {{isMailSent}} />{{notifyByMailLabel}}</label>' +
			'        {{/unless}} {{/if}}' +
			'        {{#if isResharingAllowed}} {{#if hasSharePermission}}' +
			'        {{/if}} {{/if}}' +
			'    </li>' +
			'{{/each}}' +
			'</ul>'
		;

	/**
	 * @class OCA.Share.ShareDialogShareeListView
	 * @member {OC.Share.ShareItemModel} model
	 * @member {jQuery} $el
	 * @memberof OCA.Sharing
	 * @classdesc
	 *
	 * Represents the sharee list part in the GUI of the share dialogue
	 *
	 */
	var ShareDialogShareeListView = OC.Backbone.View.extend({
		/** @type {string} **/
		id: 'shareDialogLinkShare',

		/** @type {OC.Share.ShareConfigModel} **/
		configModel: undefined,

		/** @type {Function} **/
		_template: undefined,

		/** @type {boolean} **/
		showLink: true,

		initialize: function(options) {
			if(!_.isUndefined(options.configModel)) {
				this.configModel = options.configModel;
			} else {
				throw 'missing OC.Share.ShareConfigModel';
			}
		},

		getShareeList: function() {
			var universal = {
				avatarEnabled: this.configModel.areAvatarsEnabled(),
				mailPublicNotificationEnabled: this.configModel.isMailPublicNotificationEnabled(),
				notifyByMailLabel: t('core', 'notify by email'),
				unshareLabel: t('core', 'Unshare'),
				unshareImage: OC.imagePath('core', 'actions/delete')
			};

			// TODO: sharess must have following attributes
			// shareType
			// shareWith
			// shareWithDisplayName
			// isRemoteShare
			// isMailSent

			var list = _.extend({}, universal);

			return list;
		},

		render: function() {
			var shareeListTemplate = this.template();
			this.$el.html(shareeListTemplate({
				sharees: this.getShareeList()
			}));

			return this;
		},

		/**
		 * @returns {Function} from Handlebars
		 * @private
		 */
		template: function () {
			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}
			return this._template;
		}

	});

	OC.Share.ShareDialogShareeListView = ShareDialogShareeListView;

})();
