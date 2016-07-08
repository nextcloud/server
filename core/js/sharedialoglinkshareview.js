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

	var PASSWORD_PLACEHOLDER = '**********';
	var PASSWORD_PLACEHOLDER_MESSAGE = t('core', 'Choose a password for the public link');

	var TEMPLATE =
			'{{#if shareAllowed}}' +
			'<span class="icon-loading-small hidden"></span>' +
			'<input type="checkbox" name="linkCheckbox" id="linkCheckbox-{{cid}}" class="checkbox linkCheckbox" value="1" {{#if isLinkShare}}checked="checked"{{/if}} />' +
			'<label for="linkCheckbox-{{cid}}">{{linkShareLabel}}</label>' +
			'<br />' +
			'<div class="oneline">' +
			'<label for="linkText-{{cid}}" class="hidden-visually">{{urlLabel}}</label>' +
			'<input id="linkText-{{cid}}" class="linkText {{#unless isLinkShare}}hidden{{/unless}}" type="text" readonly="readonly" value="{{shareLinkURL}}" />' +
			'<a class="{{#unless isLinkShare}}hidden-visually{{/unless}} clipboardButton icon icon-clippy" data-clipboard-target="#linkText-{{cid}}"></a>' +
			'</div>' +
			'    {{#if publicUpload}}' +
			'<div id="allowPublicUploadWrapper">' +
			'    <span class="icon-loading-small hidden"></span>' +
			'    <input type="checkbox" value="1" name="allowPublicUpload" id="sharingDialogAllowPublicUpload-{{cid}}" class="checkbox publicUploadCheckbox" {{{publicUploadChecked}}} />' +
			'<label for="sharingDialogAllowPublicUpload-{{cid}}">{{publicUploadLabel}}</label>' +
			'</div>' +
			'        {{#if hideFileList}}' +
			'<div id="hideFileListWrapper">' +
			'    <span class="icon-loading-small hidden"></span>' +
			'    <input type="checkbox" value="1" name="hideFileList" id="sharingDialogHideFileList-{{cid}}" class="checkbox hideFileListCheckbox" {{{hideFileListChecked}}} />' +
			'<label for="sharingDialogHideFileList-{{cid}}">{{hideFileListLabel}}</label>' +
			'</div>' +
			'        {{/if}}' +
			'    {{/if}}' +
			'    {{#if showPasswordCheckBox}}' +
			'<input type="checkbox" name="showPassword" id="showPassword-{{cid}}" class="checkbox showPasswordCheckbox" {{#if isPasswordSet}}checked="checked"{{/if}} value="1" />' +
			'<label for="showPassword-{{cid}}">{{enablePasswordLabel}}</label>' +
			'    {{/if}}' +
			'<div id="linkPass" class="linkPass {{#unless isPasswordSet}}hidden{{/unless}}">' +
			'    <label for="linkPassText-{{cid}}" class="hidden-visually">{{passwordLabel}}</label>' +
			'    <input id="linkPassText-{{cid}}" class="linkPassText" type="password" placeholder="{{passwordPlaceholder}}" />' +
			'    <span class="icon-loading-small hidden"></span>' +
			'</div>' +
			'{{else}}' +
			// FIXME: this doesn't belong in this view
			'{{#if noSharingPlaceholder}}<input id="shareWith-{{cid}}" class="shareWithField" type="text" placeholder="{{noSharingPlaceholder}}" disabled="disabled"/>{{/if}}' +
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

		events: {
			'focusout input.linkPassText': 'onPasswordEntered',
			'keyup input.linkPassText': 'onPasswordKeyUp',
			'click .linkCheckbox': 'onLinkCheckBoxChange',
			'click .linkText': 'onLinkTextClick',
			'change .publicUploadCheckbox': 'onAllowPublicUploadChange',
			'change .hideFileListCheckbox': 'onHideFileListChange',
			'click .showPasswordCheckbox': 'onShowPasswordClick'
		},

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

			this.model.on('change:hideFileListStatus', function() {
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

			_.bindAll(
				this,
				'onLinkCheckBoxChange',
				'onPasswordEntered',
				'onPasswordKeyUp',
				'onLinkTextClick',
				'onShowPasswordClick',
				'onHideFileListChange',
				'onAllowPublicUploadChange'
			);

			var clipboard = new Clipboard('.clipboardButton');
			clipboard.on('success', function(e) {
				$input = $(e.trigger);
				$input.tooltip({placement: 'bottom', trigger: 'manual', title: t('core', 'Copied!')});
				$input.tooltip('show');
				_.delay(function() {
					$input.tooltip('hide');
				}, 3000);
			});
			clipboard.on('error', function (e) {
				$input = $(e.trigger);
				var actionMsg = '';
				if (/iPhone|iPad/i.test(navigator.userAgent)) {
					actionMsg = t('core', 'Not supported!');
				} else if (/Mac/i.test(navigator.userAgent)) {
					actionMsg = t('core', 'Press ⌘-C to copy.');
				} else {
					actionMsg = t('core', 'Press Ctrl-C to copy.');
				}

				$input.tooltip({
					placement: 'bottom',
					trigger: 'manual',
					title: actionMsg
				});
				$input.tooltip('show');
				_.delay(function () {
					$input.tooltip('hide');
				}, 3000);
			});

		},

		onLinkCheckBoxChange: function() {
			var $checkBox = this.$el.find('.linkCheckbox');
			var $loading = $checkBox.siblings('.icon-loading-small');
			if(!$loading.hasClass('hidden')) {
				return false;
			}

			if($checkBox.is(':checked')) {
				if(this.configModel.get('enforcePasswordForPublicLink') === false) {
					$loading.removeClass('hidden');
					// this will create it
					this.model.saveLinkShare();
				} else {
					this.$el.find('.linkPass').slideToggle(OC.menuSpeed);
					this.$el.find('.linkPassText').focus();
				}
			} else {
				if (this.model.get('linkShare').isLinkShare) {
					$loading.removeClass('hidden');
					this.model.removeLinkShare();
				} else {
					this.$el.find('.linkPass').slideToggle(OC.menuSpeed);
				}
			}
		},

		onLinkTextClick: function() {
			var $el = this.$el.find('.linkText');
			$el.focus();
			$el.select();
		},

		onShowPasswordClick: function() {
			this.$el.find('.linkPass').slideToggle(OC.menuSpeed);
			if(!this.$el.find('.showPasswordCheckbox').is(':checked')) {
				this.model.saveLinkShare({
					password: ''
				});
			} else {
				this.$el.find('.linkPassText').focus();
			}
		},

		onPasswordKeyUp: function(event) {
			if(event.keyCode == 13) {
				this.onPasswordEntered();
			}
		},

		onPasswordEntered: function() {
			var $loading = this.$el.find('.linkPass .icon-loading-small');
			if (!$loading.hasClass('hidden')) {
				// still in process
				return;
			}
			var $input = this.$el.find('.linkPassText');
			$input.removeClass('error');
			var password = $input.val();
			// in IE9 the password might be the placeholder due to bugs in the placeholders polyfill
			if(password === '' || password === PASSWORD_PLACEHOLDER || password === PASSWORD_PLACEHOLDER_MESSAGE) {
				return;
			}

			$loading
				.removeClass('hidden')
				.addClass('inlineblock');

			this.model.saveLinkShare({
				password: password
			}, {
				error: function(model, msg) {
					// destroy old tooltips
					$input.tooltip('destroy');
					$loading.removeClass('inlineblock').addClass('hidden');
					$input.addClass('error');
					$input.attr('title', msg);
					$input.tooltip({placement: 'bottom', trigger: 'manual'});
					$input.tooltip('show');
				}
			});
		},

		onAllowPublicUploadChange: function() {
			var $checkbox = this.$('.publicUploadCheckbox');
			$checkbox.siblings('.icon-loading-small').removeClass('hidden').addClass('inlineblock');

			var permissions = OC.PERMISSION_READ;
			if($checkbox.is(':checked')) {
				permissions = OC.PERMISSION_UPDATE | OC.PERMISSION_CREATE | OC.PERMISSION_READ | OC.PERMISSION_DELETE;
			}

			this.model.saveLinkShare({
				permissions: permissions
			});
		},

		onHideFileListChange: function () {
			var $checkbox = this.$('.hideFileListCheckbox');
			$checkbox.siblings('.icon-loading-small').removeClass('hidden').addClass('inlineblock');

			var permissions = OC.PERMISSION_UPDATE | OC.PERMISSION_CREATE | OC.PERMISSION_READ | OC.PERMISSION_DELETE;
			if ($checkbox.is(':checked')) {
				permissions = OC.PERMISSION_CREATE;
			}

			this.model.saveLinkShare({
				permissions: permissions
			});
		},

		render: function() {
			var linkShareTemplate = this.template();
			var resharingAllowed = this.model.sharePermissionPossible();

			if(!resharingAllowed
				|| !this.showLink
				|| !this.configModel.isShareWithLinkAllowed())
			{
				var templateData = {shareAllowed: false};
				if (!resharingAllowed) {
					// add message
					templateData.noSharingPlaceholder = t('core', 'Resharing is not allowed');
				}
				this.$el.html(linkShareTemplate(templateData));
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

			var hideFileList = publicUploadChecked;

			var hideFileListChecked = '';
			if(this.model.isHideFileListSet()) {
				hideFileListChecked = 'checked="checked"';
			}

			var isLinkShare = this.model.get('linkShare').isLinkShare;
			var isPasswordSet = !!this.model.get('linkShare').password;
			var showPasswordCheckBox = isLinkShare
				&& (   !this.configModel.get('enforcePasswordForPublicLink')
					|| !this.model.get('linkShare').password);

			this.$el.html(linkShareTemplate({
				cid: this.cid,
				shareAllowed: true,
				hideFileList: hideFileList,
				isLinkShare: isLinkShare,
				shareLinkURL: this.model.get('linkShare').link,
				linkShareLabel: t('core', 'Share link'),
				urlLabel: t('core', 'Link'),
				enablePasswordLabel: t('core', 'Password protect'),
				passwordLabel: t('core', 'Password'),
				passwordPlaceholder: isPasswordSet ? PASSWORD_PLACEHOLDER : PASSWORD_PLACEHOLDER_MESSAGE,
				isPasswordSet: isPasswordSet,
				showPasswordCheckBox: showPasswordCheckBox,
				publicUpload: publicUpload && isLinkShare,
				publicUploadChecked: publicUploadChecked,
				hideFileListChecked: hideFileListChecked,
				publicUploadLabel: t('core', 'Allow editing'),
				hideFileListLabel: t('core', 'Hide file listing'),
				mailPublicNotificationEnabled: isLinkShare && this.configModel.isMailPublicNotificationEnabled(),
				mailPrivatePlaceholder: t('core', 'Email link to person'),
				mailButtonText: t('core', 'Send')
			}));

			this.delegateEvents();

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
