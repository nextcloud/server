/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* globals Clipboard, Handlebars */

(function() {
	if (!OC.Share) {
		OC.Share = {};
	}

	var PASSWORD_PLACEHOLDER = '**********';
	var PASSWORD_PLACEHOLDER_MESSAGE = t('core', 'Choose a password for the public link');
	var PASSWORD_PLACEHOLDER_MESSAGE_OPTIONAL = t('core', 'Choose a password for the public link or press the "Enter" key');

	var TEMPLATE =
			'{{#if shareAllowed}}' +
			'<span class="icon-loading-small hidden"></span>' +
			'<input type="checkbox" name="linkCheckbox" id="linkCheckbox-{{cid}}" class="checkbox linkCheckbox" value="1" {{#if isLinkShare}}checked="checked"{{/if}} />' +
			'<label for="linkCheckbox-{{cid}}">{{linkShareLabel}}</label>' +
			'<br />' +
			'<div class="oneline">' +
			'<label for="linkText-{{cid}}" class="hidden-visually">{{urlLabel}}</label>' +
			'<input id="linkText-{{cid}}" class="linkText {{#unless isLinkShare}}hidden{{/unless}}" type="text" readonly="readonly" value="{{shareLinkURL}}" />' +
			'{{#if singleAction}}' +
				'<a class="{{#unless isLinkShare}}hidden-visually{{/unless}} clipboardButton icon icon-clippy" data-clipboard-target="#linkText-{{cid}}"></a>' +
			'{{else}}' +
				'<a class="{{#unless isLinkShare}}hidden-visually{{/unless}}" href="#"><span class="linkMore icon icon-more"></span></a>' +
				'{{{popoverMenu}}}' +
			'{{/if}}' +
			'</div>' +
			'{{#if publicUpload}}' +
				'<div>' +
					'<span class="icon-loading-small hidden"></span>' +
					'<input type="radio" name="publicUpload" value="{{publicUploadRValue}}" id="sharingDialogAllowPublicUpload-r-{{cid}}" class="radio publicUploadRadio" {{{publicUploadRChecked}}} />' +
					'<label for="sharingDialogAllowPublicUpload-r-{{cid}}">{{publicUploadRLabel}}</label>' +
				'</div>' +
				'<div>' +
					'<span class="icon-loading-small hidden"></span>' +
					'<input type="radio" name="publicUpload" value="{{publicUploadRWValue}}" id="sharingDialogAllowPublicUpload-rw-{{cid}}" class="radio publicUploadRadio" {{{publicUploadRWChecked}}} />' +
					'<label for="sharingDialogAllowPublicUpload-rw-{{cid}}">{{publicUploadRWLabel}}</label>' +
				'</div>' +
				'<div>' +
					'<span class="icon-loading-small hidden"></span>' +
					'<input type="radio" name="publicUpload" value="{{publicUploadWValue}}" id="sharingDialogAllowPublicUpload-w-{{cid}}" class="radio publicUploadRadio" {{{publicUploadWChecked}}} />' +
					'<label for="sharingDialogAllowPublicUpload-w-{{cid}}">{{publicUploadWLabel}}</label>' +
				'</div>' +
			'{{/if}}' +
			'     {{#if publicEditing}}' +
			'<div id="allowPublicEditingWrapper">' +
			'    <span class="icon-loading-small hidden"></span>' +
			'    <input type="checkbox" value="1" name="allowPublicEditing" id="sharingDialogAllowPublicEditing-{{cid}}" class="checkbox publicEditingCheckbox" {{{publicEditingChecked}}} />' +
			'<label for="sharingDialogAllowPublicEditing-{{cid}}">{{publicEditingLabel}}</label>' +
			'</div>' +
			'    {{/if}}' +
			'    {{#if showPasswordCheckBox}}' +
			'<input type="checkbox" name="showPassword" id="showPassword-{{cid}}" class="checkbox showPasswordCheckbox" {{#if isPasswordSet}}checked="checked"{{/if}} value="1" />' +
			'<label for="showPassword-{{cid}}">{{enablePasswordLabel}}</label>' +
			'    {{/if}}' +
			'<div id="linkPass" class="oneline linkPass {{#unless isPasswordSet}}hidden{{/unless}}">' +
			'    <label for="linkPassText-{{cid}}" class="hidden-visually">{{passwordLabel}}</label>' +
			'    {{#if showPasswordCheckBox}}' +
			'    <input id="linkPassText-{{cid}}" class="linkPassText" type="password" placeholder="{{passwordPlaceholder}}" autocomplete="new-password" />' +
			'    {{else}}' +
			'    <input id="linkPassText-{{cid}}" class="linkPassText" type="password" placeholder="{{passwordPlaceholderInitial}}" autocomplete="new-password" />' +
			'    {{/if}}' +
			'    <span class="icon icon-loading-small hidden"></span>' +
			'</div>' +
			'{{else}}' +
			// FIXME: this doesn't belong in this view
			'{{#if noSharingPlaceholder}}<input id="shareWith-{{cid}}" class="shareWithField" type="text" placeholder="{{noSharingPlaceholder}}" disabled="disabled"/>{{/if}}' +
			'{{/if}}'
		;
	var TEMPLATE_POPOVER_MENU =
		'<div class="popovermenu bubble hidden menu socialSharingMenu">' +
			'<ul>' +
				'<li>' +
					'<a href="#" class="shareOption menuitem clipboardButton" data-clipboard-target="#linkText-{{cid}}">' +
						'<span class="icon icon-clippy" ></span>' +
						'<span>{{copyLabel}}</span>' +
					'</a>' +
				'</li>' +
				'{{#each social}}' +
					'<li>' +
						'<a href="#" class="shareOption menuitem pop-up" data-url="{{url}}" data-window="{{newWindow}}">' +
							'<span class="icon {{iconClass}}"' +
								'></span><span>{{label}}' +
							'</span>' +
						'</a>' +
					'</li>' +
				'{{/each}}' +
			'</ul>' +
		'</div>';

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

		/** @type {Function} **/
		_popoverMenuTemplate: undefined,

		/** @type {boolean} **/
		showLink: true,

		events: {
			'focusout input.linkPassText': 'onPasswordEntered',
			'keyup input.linkPassText': 'onPasswordKeyUp',
			'click .linkCheckbox': 'onLinkCheckBoxChange',
			'click .linkText': 'onLinkTextClick',
			'change .publicEditingCheckbox': 'onAllowPublicEditingChange',
			'click .showPasswordCheckbox': 'onShowPasswordClick',
			'click .icon-more': 'onToggleMenu',
			'click .pop-up': 'onPopUpClick',
			'change .publicUploadRadio': 'onPublicUploadChange'
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
				'onAllowPublicEditingChange',
				'onPublicUploadChange'
			);

			var clipboard = new Clipboard('.clipboardButton');
			clipboard.on('success', function(e) {
				var $input = $(e.trigger);
				$input.tooltip('hide')
					.attr('data-original-title', t('core', 'Copied!'))
					.tooltip('fixTitle')
					.tooltip({placement: 'bottom', trigger: 'manual'})
					.tooltip('show');
				_.delay(function() {
					$input.tooltip('hide');
					if (OC.Share.Social.Collection.size() == 0) {
						$input.attr('data-original-title', t('core', 'Copy'))
							.tooltip('fixTitle');
					} else {
						$input.tooltip("destroy");
					}
				}, 3000);
			});
			clipboard.on('error', function (e) {
				var $input = $(e.trigger);
				var actionMsg = '';
				if (/iPhone|iPad/i.test(navigator.userAgent)) {
					actionMsg = t('core', 'Not supported!');
				} else if (/Mac/i.test(navigator.userAgent)) {
					actionMsg = t('core', 'Press ⌘-C to copy.');
				} else {
					actionMsg = t('core', 'Press Ctrl-C to copy.');
				}

				$input.tooltip('hide')
					.attr('data-original-title', actionMsg)
					.tooltip('fixTitle')
					.tooltip({placement: 'bottom', trigger: 'manual'})
					.tooltip('show');
				_.delay(function () {
					$input.tooltip('hide');
					if (OC.Share.Social.Collection.size() == 0) {
						$input.attr('data-original-title', t('core', 'Copy'))
							.tooltip('fixTitle');
					} else {
						$input.tooltip("destroy");
					}
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
				if(this.configModel.get('enforcePasswordForPublicLink') === false && this.configModel.get('enableLinkPasswordByDefault') === false) {
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
				if (!OC.Util.isIE()) {
					this.$el.find('.linkPassText').focus();
				}
			}
		},

		onPasswordKeyUp: function(event) {
			if(event.keyCode === 13) {
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

			if (this.$el.find('.linkPassText').attr('placeholder') === PASSWORD_PLACEHOLDER_MESSAGE_OPTIONAL) {

				// in IE9 the password might be the placeholder due to bugs in the placeholders polyfill
				if(password === PASSWORD_PLACEHOLDER_MESSAGE_OPTIONAL) {
					password = '';
				}
			} else {

				// in IE9 the password might be the placeholder due to bugs in the placeholders polyfill
				if(password === '' || password === PASSWORD_PLACEHOLDER || password === PASSWORD_PLACEHOLDER_MESSAGE) {
					return;
				}
			}

			$loading
				.removeClass('hidden')
				.addClass('inlineblock');

			this.model.saveLinkShare({
				password: password
			}, {
				complete: function(model) {
					$loading.removeClass('inlineblock').addClass('hidden');
				},
				error: function(model, msg) {
					// destroy old tooltips
					$input.tooltip('destroy');
					$input.addClass('error');
					$input.attr('title', msg);
					$input.tooltip({placement: 'bottom', trigger: 'manual'});
					$input.tooltip('show');
				}
			});
		},

		onAllowPublicEditingChange: function() {
			var $checkbox = this.$('.publicEditingCheckbox');
			$checkbox.siblings('.icon-loading-small').removeClass('hidden').addClass('inlineblock');

			var permissions = OC.PERMISSION_READ;
			if($checkbox.is(':checked')) {
				permissions = OC.PERMISSION_UPDATE | OC.PERMISSION_READ;
			}

			this.model.saveLinkShare({
				permissions: permissions
			});
		},


	onPublicUploadChange: function(e) {
			var permissions = e.currentTarget.value;
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

			var publicUploadRWChecked = '';
			var publicUploadRChecked = '';
			var publicUploadWChecked = '';

			switch (this.model.linkSharePermissions()) {
				case OC.PERMISSION_READ:
					publicUploadRChecked = 'checked';
					break;
				case OC.PERMISSION_CREATE:
					publicUploadWChecked = 'checked';
					break;
				case OC.PERMISSION_UPDATE | OC.PERMISSION_CREATE | OC.PERMISSION_READ | OC.PERMISSION_DELETE:
					publicUploadRWChecked = 'checked';
					break;
			}

			var publicEditingChecked = '';
			if(this.model.isPublicEditingAllowed()) {
				publicEditingChecked = 'checked="checked"';
			}

			var isLinkShare = this.model.get('linkShare').isLinkShare;
			var isPasswordSet = !!this.model.get('linkShare').password;
			var showPasswordCheckBox = isLinkShare
				&& (   !this.configModel.get('enforcePasswordForPublicLink')
					|| !this.model.get('linkShare').password);
			var passwordPlaceholderInitial = this.configModel.get('enforcePasswordForPublicLink')
				? PASSWORD_PLACEHOLDER_MESSAGE : PASSWORD_PLACEHOLDER_MESSAGE_OPTIONAL;

			var publicEditable =
				!this.model.isFolder()
				&& isLinkShare
				&& this.model.updatePermissionPossible();

			var link = this.model.get('linkShare').link;
			var social = [];
			OC.Share.Social.Collection.each(function(model) {
				var url = model.get('url');
				url = url.replace('{{reference}}', link);

				social.push({
					url: url,
					label: t('core', 'Share to {name}', {name: model.get('name')}),
					name: model.get('name'),
					iconClass: model.get('iconClass'),
					newWindow: model.get('newWindow')
				});
			});

			var popover = this.popoverMenuTemplate({
				cid: this.cid,
				copyLabel: t('core', 'Copy'),
				social: social
			});

			this.$el.html(linkShareTemplate({
				cid: this.cid,
				shareAllowed: true,
				isLinkShare: isLinkShare,
				shareLinkURL: this.model.get('linkShare').link,
				linkShareLabel: t('core', 'Share link'),
				urlLabel: t('core', 'Link'),
				enablePasswordLabel: t('core', 'Password protect'),
				passwordLabel: t('core', 'Password'),
				passwordPlaceholder: isPasswordSet ? PASSWORD_PLACEHOLDER : PASSWORD_PLACEHOLDER_MESSAGE,
				passwordPlaceholderInitial: passwordPlaceholderInitial,
				isPasswordSet: isPasswordSet,
				showPasswordCheckBox: showPasswordCheckBox,
				publicUpload: publicUpload && isLinkShare,
				publicEditing: publicEditable,
				publicEditingChecked: publicEditingChecked,
				publicEditingLabel: t('core', 'Allow editing'),
				mailPrivatePlaceholder: t('core', 'Email link to person'),
				mailButtonText: t('core', 'Send'),
				singleAction: OC.Share.Social.Collection.size() == 0,
				popoverMenu: popover,
				publicUploadRWLabel: t('core', 'Allow upload and editing'),
				publicUploadRLabel: t('core', 'Read only'),
				publicUploadWLabel: t('core', 'File drop (upload only)'),
				publicUploadRWValue: OC.PERMISSION_UPDATE | OC.PERMISSION_CREATE | OC.PERMISSION_READ | OC.PERMISSION_DELETE,
				publicUploadRValue: OC.PERMISSION_READ,
				publicUploadWValue: OC.PERMISSION_CREATE,
				publicUploadRWChecked: publicUploadRWChecked,
				publicUploadRChecked: publicUploadRChecked,
				publicUploadWChecked: publicUploadWChecked
			}));

			if (OC.Share.Social.Collection.size() == 0) {
				this.$el.find('.clipboardButton').tooltip({
					placement: 'bottom',
					title: t('core', 'Copy'),
					trigger: 'hover'
				});
			}

			this.delegateEvents();

			return this;
		},

		onToggleMenu: function(event) {
			event.preventDefault();
			event.stopPropagation();
			var $element = $(event.target);
			var $li = $element.closest('.oneline');
			var $menu = $li.find('.popovermenu');

			OC.showMenu(null, $menu);
			this._menuOpen = $li.data('share-id');
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
		},

		/**
		 * renders the popover template and returns the resulting HTML
		 *
		 * @param {Object} data
		 * @returns {string}
		 */
		popoverMenuTemplate: function(data) {
			if(!this._popoverMenuTemplate) {
				this._popoverMenuTemplate = Handlebars.compile(TEMPLATE_POPOVER_MENU);
			}
			return this._popoverMenuTemplate(data);
		},

		onPopUpClick: function(event) {
			event.preventDefault();
			event.stopPropagation();

			var url = $(event.currentTarget).data('url');
			var newWindow = $(event.currentTarget).data('window');
			$(event.currentTarget).tooltip('hide');
			if (url) {
				if (newWindow === true) {
					var width = 600;
					var height = 400;
					var left = (screen.width / 2) - (width / 2);
					var top = (screen.height / 2) - (height / 2);

					window.open(url, 'name', 'width=' + width + ', height=' + height + ', top=' + top + ', left=' + left);
				} else {
					window.location.href = url;
				}
			}
		}

	});

	OC.Share.ShareDialogLinkShareView = ShareDialogLinkShareView;

})();
