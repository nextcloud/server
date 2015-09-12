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
			'<input type="checkbox" name="linkCheckbox" id="linkCheckbox" value="1" {{#if isLinkShare}}checked="checked"{{/if}} /><label for="linkCheckbox">{{linkShareLabel}}</label>' +
			'<br />' +
			'<label for="linkText" class="hidden-visually">{{urlLabel}}</label>' +
			'<input id="linkText" {{#unless isLinkShare}}class="hidden"{{/unless}} type="text" readonly="readonly" value="{{shareLinkURL}}" />' +
			'   {{#if showPasswordCheckBox}}' +
			'<input type="checkbox" name="showPassword" id="showPassword" {{#if isPasswordSet}}checked="checked"{{/if}} value="1" /><label for="showPassword">{{enablePasswordLabel}}</label>' +
			'   {{/if}}' +
			'<div id="linkPass" {{#unless isPasswordSet}}class="hidden"{{/unless}}>' +
			'    <label for="linkPassText" class="hidden-visually">{{passwordLabel}}</label>' +
			'    <input id="linkPassText" type="password" placeholder="{{passwordPlaceholder}}" />' +
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
			'    <input id="email" value="" placeholder="{{mailPrivatePlaceholder}}" type="text" />' +
			'    <input id="emailButton" type="submit" value="{{mailButtonText}}" />' +
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

			this.model.on('change:linkShare', function() {
				view.render();
			});

			if(!_.isUndefined(options.configModel)) {
				this.configModel = options.configModel;
			} else {
				throw 'missing OC.Share.ShareConfigModel';
			}

			_.bindAll(this, 'onLinkCheckBoxChange', 'onPasswordEntered', 'onShowPasswordClick');
		},

		onLinkCheckBoxChange: function() {
			var $checkBox = this.$el.find('#linkCheckbox');
			var $loading = $checkBox.siblings('.icon-loading-small');
			if(!$loading.hasClass('hidden')) {
				return false;
			}

			if($checkBox.is(':checked')) {
				if(this.configModel.get('enforcePasswordForPublicLink') === false) {
					$loading.removeClass('hidden');
					this.model.addLinkShare();
				} else {
					this.$el.find('#linkPass').slideToggle(OC.menuSpeed);
					// TODO drop with IE8 drop
					if($('html').hasClass('ie8')) {
						this.$el.find('#linkPassText').attr('placeholder', null);
						this.$el.find('#linkPassText').val('');
					}
					this.$el.find('#linkPassText').focus();
				}
			} else {
				this.model.removeLinkShare();
			}
		},

		onLinkTextClick: function() {
			this.focus();
			this.select();
		},

		onShowPasswordClick: function() {
			this.$el.find('#linkPass').slideToggle(OC.menuSpeed);
			if(!this.$el.find('#showPassword').is(':checked')) {
				this.model.addLinkShare({password: ''});
			} else {
				this.$el.find('#linkPassText').focus();
			}
		},

		onPasswordEntered: function() {
			var password = this.$el.find('#linkPassText').val();
			if(password === '') {
				return;
			}

			this.$el.find('#linkPass .icon-loading-small')
				.removeClass('hidden')
				.addClass('inlineblock');

			this.model.addLinkShare({password: password});
		},

		render: function() {
			var linkShareTemplate = this.template();

			if(    !this.model.sharePermissionPossible()
				|| !this.showLink
				|| !this.configModel.isShareWithLinkAllowed())
			{
				this.$el.html(linkShareTemplate({
					shareAllowed: false,
					noSharingPlaceholder: t('core', 'Resharing is not allowed')
				}));
				return this;
			}

			var publicUpload =
				this.model.isFolder()
				&& this.model.createPermissionPossible()
				&& this.configModel.isPublicUploadEnabled();

			var publicUploadChecked = '';
			if(this.model.isPublicUploadAllowed()) {
				publicUploadChecked = 'checked="checked"';
			}

			var isLinkShare = this.model.get('linkShare').isLinkShare;
			var isPasswordSet = !!this.model.get('linkShare').password;
			var showPasswordCheckBox = isLinkShare
				&& (   !this.configModel.get('enforcePasswordForPublicLink')
					|| !this.model.get('linkShare').password);

			this.$el.html(linkShareTemplate({
				shareAllowed: true,
				isLinkShare: isLinkShare,
				shareLinkURL: this.model.get('linkShare').link,
				linkShareLabel: t('core', 'Share link'),
				urlLabel: t('core', 'Link'),
				enablePasswordLabel: t('core', 'Password protect'),
				passwordLabel: t('core', 'Password'),
				passwordPlaceholder: isPasswordSet ? '**********' : t('core', 'Choose a password for the public link'),
				isPasswordSet: isPasswordSet,
				showPasswordCheckBox: showPasswordCheckBox,
				publicUpload: publicUpload && isLinkShare,
				publicUploadChecked: publicUploadChecked,
				publicUploadLabel: t('core', 'Allow editing'),
				mailPublicNotificationEnabled: isLinkShare && this.configModel.isMailPublicNotificationEnabled(),
				mailPrivatePlaceholder: t('core', 'Email link to person'),
				mailButtonText: t('core', 'Send')
			}));

			this.$el.find('#linkCheckbox').change(this.onLinkCheckBoxChange);
			this.$el.find('#linkText').click(this.onLinkTextClick);
			this.$el.find('#showPassword').click(this.onShowPasswordClick);
			this.$el.find('#linkPassText').focusout(this.onPasswordEntered);
			var view = this;
			this.$el.find('#linkPassText').keyup(function(event) {
				if(event.keyCode == 13) {
					view.onPasswordEntered();
				}
			});

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
