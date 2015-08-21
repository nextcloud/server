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
			'{{#if shareAllowed}}' +
			'<span class="icon-loading-small hidden"></span>' +
			'<input type="checkbox" name="linkCheckbox" id="linkCheckbox" value="1" /><label for="linkCheckbox">{{linkShareLabel}}</label>' +
			'<br />' +
			'<label for="linkText" class="hidden-visually">{{urlLabel}}</label>' +
			'<input id="linkText" type="text" readonly="readonly" />' +
			'<input type="checkbox" name="showPassword" id="showPassword" value="1" class="hidden" /><label for="showPassword" class="hidden-visually">{{enablePasswordLabel}}</label>' +
			'<div id="linkPass">' +
			'    <label for="linkPassText" class="hidden-visually">{{passwordLabel}}</label>' +
			'    <input id="linkPassText" type="password" placeholder="passwordPlaceholder" />' +
			'    <span class="icon-loading-small hidden"></span>' +
			'</div>' +
			'    {{#if publicUpload}}' +
			'<div id="allowPublicUploadWrapper" class="hidden">' +
			'    <span class="icon-loading-small hidden"></span>' +
			'    <input type="checkbox" value="1" name="allowPublicUpload" id="sharingDialogAllowPublicUpload" {{{publicUploadChecked}}} />' +
			'    <label for="sharingDialogAllowPublicUpload">{{publicUploadLabel}}</label>' +
			'</div>' +
			'    {{/if}}' +
			'    {{#if mailPublicNotificationEnabled}}' +
			'<form id="emailPrivateLink">' +
			'    <input id="email" class="hidden" value="" placeholder="{{mailPrivatePlaceholder}}" type="text" />' +
			'    <input id="emailButton" class="hidden" type="submit" value="{{mailButtonText}}" />' +
			'</form>' +
			'    {{/if}}' +
			'{{else}}' +
			'<input id="shareWith" type="text" placeholder="{{noSharingPlaceholder}}" disabled="disabled"/>' +
			'{{/if}}'
		;

	/**
	 * @class OCA.Share.ShareDialogLinkShareView
	 * @member {OC.Share.ShareItemModel} model
	 * @member {jQuery} $el
	 * @memberof OCA.Sharing
	 * @classdesc
	 *
	 * Represents the GUI of the share dialogue
	 *
	 */
	var ShareDialogLinkShareView = OC.Backbone.View.extend({
		/** @type {string} **/
		id: 'shareDialogLinkShare',

		/** @type {OC.Share.ShareConfigModel} **/
		configModel: undefined,

		/** @type {Function} **/
		_template: undefined,

		/** @type {boolean} **/
		showLink: true,

		initialize: function(options) {
			var view = this;

			this.model.on('change:permissions', function() {
				view.render();
			});

			this.model.on('change:itemType', function() {
				view.render();
			});

			this.model.on('change:allowPublicUploadStatus', function() {
				view.render();
			});

			if(!_.isUndefined(options.configModel)) {
				this.configModel = options.configModel;
			} else {
				console.warn('missing OC.Share.ShareConfigModel');
			}
		},

		render: function() {
			var linkShareTemplate = this.template();

			if(    !this.model.hasSharePermission()
				|| !this.showLink
				|| !this.configModel.isShareWithLinkAllowed())
			{
				this.$el.empty();
				this.$el.append(linkShareTemplate({
					shareAllowed: false,
					noSharingPlaceholder: t('core', 'Resharing is not allowed')
				}));
				return this;
			}

			var publicUpload =
				this.model.isFolder()
				&& this.model.hasCreatePermission()
				&& this.configModel.isPublicUploadEnabled();

			var publicUploadChecked = '';
			if(this.model.isPublicUploadAllowed()) {
				publicUploadChecked = 'checked="checked"';
			}

			this.$el.empty();
			this.$el.append(linkShareTemplate({
				shareAllowed: true,
				linkShareLabel: t('core', 'Share link'),
				urlLabel: t('core', 'Link'),
				enablePasswordLabel: t('core', 'Password protect'),
				passwordLabel: t('core', 'Password'),
				passwordPlaceholder: t('core', 'Choose a password for the public link'),
				publicUpload: publicUpload,
				publicUploadChecked: publicUploadChecked,
				publicUploadLabel: t('core', 'Allow editing'),
				mailPublicNotificationEnabled: this.configModel.isMailPublicNotificationEnabled(),
				mailPrivatePlaceholder: t('core', 'Email link to person'),
				mailButtonText: t('core', 'Send')
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

	OC.Share.ShareDialogLinkShareView = ShareDialogLinkShareView;

})();
