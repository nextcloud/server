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
			'<ul id="shareLink" class="shareWithList">' +
			'	<li data-share-id="{{cid}}">' +
			'		<div class="avatar icon-public-white"></div><span class="username" title="{{linkShareLabel}}">{{linkShareLabel}}</span>' +
			'		<span class="sharingOptionsGroup">' +
			'			<span class="shareOption"> ' +
			'				<span class="icon-loading-small hidden"></span>' +
			'				<input id="linkCheckbox-{{cid}}" {{#if isLinkShare}}checked="checked"{{/if}} type="checkbox" name="linkCheckbox" class="linkCheckbox permissions checkbox">' +
			'				<label for="linkCheckbox-{{cid}}">{{linkShareEnableLabel}}</label>' +
			'			</span>' +
			'			{{#if showMenu}}' +
			'			<div class="share-menu" tabindex="0"><span class="icon icon-more"></span>' +
			'				{{#if showPending}}' +
			'					{{{pendingPopoverMenu}}}' +
			'				{{else}}' +
			'					{{{popoverMenu}}}' +
			'				{{/if}}' +
			'			</div>' +
			'			{{/if}}' +
			'		</span>' +
			'	</li>' +
			'</ul>' +
			'{{else}}' +
			// FIXME: this doesn't belong in this view
			'{{#if noSharingPlaceholder}}<input id="shareWith-{{cid}}" class="shareWithField" type="text" placeholder="{{noSharingPlaceholder}}" disabled="disabled"/>{{/if}}' +
			'{{/if}}'
		;
	var TEMPLATE_POPOVER_MENU =
		'<div class="popovermenu menu">' +
			'<ul>' +
				'<li>' +
					'<a href="#" class="menuitem clipboardButton" data-clipboard-text="{{shareLinkURL}}">' +
						'<span class="icon icon-clippy" ></span>' +
						'<span>{{copyLabel}}</span>' +
					'</a>' +
				'</li>' +
				'<li class="hidden linkTextMenu">' +
					'<span class="menuitem icon-link-text">' +
						'<input id="linkText-{{cid}}" class="linkText" type="text" readonly="readonly" value="{{shareLinkURL}}" />' +
					'</span>' +
				'</li>' +
				'{{#if publicUpload}}' +
					'<li><span class="shareOption menuitem">' +
						'<span class="icon-loading-small hidden"></span>' +
						'<input type="radio" name="publicUpload" value="{{publicUploadRValue}}" id="sharingDialogAllowPublicUpload-r-{{cid}}" class="radio publicUploadRadio" {{{publicUploadRChecked}}} />' +
						'<label for="sharingDialogAllowPublicUpload-r-{{cid}}">{{publicUploadRLabel}}</label>' +
					'</span></li>' +
					'<li><span class="shareOption menuitem">' +
						'<span class="icon-loading-small hidden"></span>' +
						'<input type="radio" name="publicUpload" value="{{publicUploadRWValue}}" id="sharingDialogAllowPublicUpload-rw-{{cid}}" class="radio publicUploadRadio" {{{publicUploadRWChecked}}} />' +
						'<label for="sharingDialogAllowPublicUpload-rw-{{cid}}">{{publicUploadRWLabel}}</label>' +
					'</span></li>' +
					'<li><span class="shareOption menuitem">' +
						'<span class="icon-loading-small hidden"></span>' +
						'<input type="radio" name="publicUpload" value="{{publicUploadWValue}}" id="sharingDialogAllowPublicUpload-w-{{cid}}" class="radio publicUploadRadio" {{{publicUploadWChecked}}} />' +
						'<label for="sharingDialogAllowPublicUpload-w-{{cid}}">{{publicUploadWLabel}}</label>' +
					'</span></li>' +
				'{{/if}}' +
				'{{#if publicEditing}}' +
				'	<li id="allowPublicEditingWrapper"><span class="shareOption menuitem">' +
				'		<span class="icon-loading-small hidden"></span>' +
				'		<input type="checkbox" name="allowPublicEditing" id="sharingDialogAllowPublicEditing-{{cid}}" class="checkbox publicEditingCheckbox" {{{publicEditingChecked}}} />' +
				'		<label for="sharingDialogAllowPublicEditing-{{cid}}">{{publicEditingLabel}}</label>' +
				'	</span></li>' +
				'{{/if}}' +
				'<li>' +
				'	<span class="shareOption menuitem">' +
				'		<input type="checkbox" name="showPassword" id="showPassword-{{cid}}" class="checkbox showPasswordCheckbox"' +
				'			{{#if isPasswordSet}}checked="checked"{{/if}} {{#if isPasswordEnforced}}disabled="disabled"{{/if}} value="1" />' +
				'		<label for="showPassword-{{cid}}">{{enablePasswordLabel}}</label>' +
				'	</span>' +
				'</li>' +
				'<li class="{{#unless isPasswordSet}}hidden{{/unless}} linkPassMenu">' +
				'	<span class="shareOption menuitem icon-share-pass">' +
				'		<input id="linkPassText-{{cid}}" class="linkPassText" type="password" placeholder="{{passwordPlaceholder}}" autocomplete="new-password" />' +
				'		<input type="submit" class="icon-confirm share-pass-submit" value="" />' +
				'		<span class="icon icon-loading-small hidden"></span>' +
				'	</span>' +
				'</li>' +
				'<li>' +
				'	<span class="shareOption menuitem">' +
				'		<input id="expireDate-{{cid}}" type="checkbox" name="expirationDate" class="expireDate checkbox"' +
				'			{{#if hasExpireDate}}checked="checked"{{/if}} {{#if isExpirationEnforced}}disabled="disabled"{{/if}}" />' +
				'		<label for="expireDate-{{cid}}">{{expireDateLabel}}</label>' +
				'	</span>' +
				'</li>' +
				'<li class="{{#unless hasExpireDate}}hidden{{/unless}}">' +
				'	<span class="menuitem icon-expiredate expirationDateContainer-{{cid}}">' +
				'    	<label for="expirationDatePicker-{{cid}}" class="hidden-visually" value="{{expirationDate}}">{{expirationLabel}}</label>' +
				'    	<input id="expirationDatePicker-{{cid}}" class="datepicker" type="text" placeholder="{{expirationDatePlaceholder}}" value="{{#if hasExpireDate}}{{expireDate}}{{else}}{{defaultExpireDate}}{{/if}}" />' +
				'	</span>' +
				'</li>' +
				'<li>' +
					'<a href="#" class="share-add"><span class="icon-loading-small hidden"></span>' +
					'	<span class="icon icon-edit"></span>' +
					'	<span>{{addNoteLabel}}</span>' +
					'	<input type="button" class="share-note-delete icon-delete">' +
					'</a>' +
				'</li>' +
				'<li class="share-note-form share-note-link hidden">' +
					'<span class="menuitem icon-note">' +
					'	<textarea class="share-note">{{shareNote}}</textarea>' +
					'	<input type="submit" class="icon-confirm share-note-submit" value="" id="add-note-{{shareId}}" />' +
					'</span>' +
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

	// popovermenu waiting for password or expiration date before saving the share
	var TEMPLATE_POPOVER_MENU_PENDING =
		'<div class="popovermenu pendingpopover menu">' +
			'<ul>' +
				'{{#if isPasswordEnforced}}' +
				'	<li><span class="shareOption menuitem">' +
				'		<input type="checkbox" name="showPassword" id="showPassword-{{cid}}" checked="checked" disabled class="checkbox showPasswordCheckbox" value="1" />' +
				'		<label for="showPassword-{{cid}}">{{enablePasswordLabel}}</label>' +
				'	</span></li>' +
				'	<li class="linkPassMenu"><span class="shareOption menuitem icon-share-pass">' +
				'    	<input id="linkPassText-{{cid}}" class="linkPassText" type="password" placeholder="{{passwordPlaceholder}}" autocomplete="new-password" />' +
				'    <span class="icon icon-loading-small hidden"></span>' +
				'	</span></li>' +
				'{{/if}}' +
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

		/** @type {Function} **/
		_pendingPopoverMenuTemplate: undefined,

		/** @type {boolean} **/
		showLink: true,

		/** @type {boolean} **/
		showPending: false,

		events: {
			// enable/disable
			'change .linkCheckbox': 'onLinkCheckBoxChange',
			// open menu
			'click .share-menu .icon-more': 'onToggleMenu',
			// password
			'click input.share-pass-submit': 'onPasswordEntered', 
			'keyup input.linkPassText': 'onPasswordKeyUp', // check for the enter key
			'change .showPasswordCheckbox': 'onShowPasswordClick',
			'change .publicEditingCheckbox': 'onAllowPublicEditingChange',
			// copy link url
			'click .linkText': 'onLinkTextClick',
			// social
			'click .pop-up': 'onPopUpClick',
			// permission change
			'change .publicUploadRadio': 'onPublicUploadChange',
			// expire date
			'click .expireDate' : 'onExpireDateChange',
			'change .datepicker': 'onChangeExpirationDate',
			'click .datepicker' : 'showDatePicker',
			// note
			'click .share-add': 'showNoteForm',
			'click .share-note-delete': 'deleteNote',
			'click .share-note-submit': 'updateNote'
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

			var clipboard = new Clipboard('.clipboardButton');
			clipboard.on('success', function(e) {
				var $menu = $(e.trigger);
				var $linkTextMenu = $menu.parent().next('li.linkTextMenu')

				$menu.tooltip('hide')
					.attr('data-original-title', t('core', 'Copied!'))
					.tooltip('fixTitle')
					.tooltip({placement: 'bottom', trigger: 'manual'})
					.tooltip('show');
				_.delay(function() {
					$menu.tooltip('hide');
					$menu.tooltip('destroy');
				}, 3000);
			});
			clipboard.on('error', function (e) {
				var $menu = $(e.trigger);
				var $linkTextMenu = $menu.parent().next('li.linkTextMenu')
				var $input = $linkTextMenu.find('.linkText');

				var actionMsg = '';
				if (/iPhone|iPad/i.test(navigator.userAgent)) {
					actionMsg = t('core', 'Not supported!');
				} else if (/Mac/i.test(navigator.userAgent)) {
					actionMsg = t('core', 'Press ⌘-C to copy.');
				} else {
					actionMsg = t('core', 'Press Ctrl-C to copy.');
				}

				$linkTextMenu.removeClass('hidden');
				$input.select();
				$input.tooltip('hide')
					.attr('data-original-title', actionMsg)
					.tooltip('fixTitle')
					.tooltip({placement: 'bottom', trigger: 'manual'})
					.tooltip('show');
				_.delay(function () {
					$input.tooltip('hide');
					$input.attr('data-original-title', t('core', 'Copy'))
						  .tooltip('fixTitle');
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
					// force the rendering of the menu
					this.showPending = true;
					this.render();
					$('#shareLink .share-menu .icon-more').click();
					$('#shareLink .share-menu .icon-more + .popovermenu input:eq(1)').focus();
				}
			} else {
				if (this.model.get('linkShare').isLinkShare) {
					$loading.removeClass('hidden');
					this.model.removeLinkShare();
				} else {
					this.showPending = false;
					this.render()
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
			this.$el.find('.linkPassMenu').toggleClass('hidden');
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
			var $loading = this.$el.find('.linkPassMenu .icon-loading-small');
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
					var $container = $input.parent();
					$container.tooltip('destroy');
					$input.addClass('error');
					$container.attr('title', msg);
					$container.tooltip({placement: 'bottom', trigger: 'manual'});
					$container.tooltip('show');
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
		
		showNoteForm: function(event) {
			event.preventDefault();
			event.stopPropagation();
			var self = this;
			var $element = $(event.target);
			var $li = $element.closest('li[data-share-id]');
			var $menu = $element.closest('li');
			var $form = $menu.next('li.share-note-form');

			// show elements
			$menu.find('.share-note-delete').toggle();
			$form.toggleClass('hidden');
			$form.find('textarea').focus();
		},

		deleteNote: function(event) {
			event.preventDefault();
			event.stopPropagation();
			var self = this;
			var $element = $(event.target);
			var $li = $element.closest('li[data-share-id]');
			var shareId = $li.data('share-id');
			var $menu = $element.closest('li');
			var $form = $menu.next('li.share-note-form');
	
			$form.find('.share-note').val('');
			
			$form.addClass('hidden');
			$menu.find('.share-note-delete').hide();

			self.sendNote('', shareId, $menu);
		},

		updateNote: function(event) {
			event.preventDefault();
			event.stopPropagation();
			var self = this;
			var $element = $(event.target);
			var $li = $element.closest('li[data-share-id]');
			var shareId = $li.data('share-id');
			var $form = $element.closest('li.share-note-form');
			var $menu = $form.prev('li');
			var message = $form.find('.share-note').val().trim();

			if (message.length < 1) {
				return;
			}

			self.sendNote(message, shareId, $menu);
		},

		sendNote: function(note, shareId, $menu) {
			var $form = $menu.next('li.share-note-form');
			var $submit = $form.find('input.share-note-submit');
			var $error = $form.find('input.share-note-error');

			$submit.prop('disabled', true);
			$menu.find('.icon-loading-small').removeClass('hidden');
			$menu.find('.icon-edit').hide();

			var complete = function() {
				$submit.prop('disabled', false);
				$menu.find('.icon-loading-small').addClass('hidden');
				$menu.find('.icon-edit').show();
			};
			var error = function() {
				$error.show();
				setTimeout(function() {
					$error.hide();
				}, 3000);
			};

			// send data
			$.ajax({
				method: 'PUT',
				url: OC.linkToOCS('apps/files_sharing/api/v1/shares',2) + shareId + '?' + OC.buildQueryString({format: 'json'}),
				data: { note: note },
				complete : complete,
				error: error
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
			var isPasswordEnforced = this.configModel.get('enforcePasswordForPublicLink')
			var isPasswordEnabledByDefault = this.configModel.get('enableLinkPasswordByDefault') === true
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

			var defaultExpireDays = this.configModel.get('defaultExpireDate');
			var isExpirationEnforced = this.configModel.get('isDefaultExpireDateEnforced');
			var hasExpireDate = !!this.model.get('linkShare').expiration || isExpirationEnforced;

			var expireDate;
			if (hasExpireDate) {
				expireDate = moment(this.model.get('linkShare').expiration, 'YYYY-MM-DD').format('DD-MM-YYYY');
			}

			// what if there is another date picker on that page?
			var minDate = new Date();
			var maxDate = null;
			// min date should always be the next day
			minDate.setDate(minDate.getDate()+1);

			if(hasExpireDate) {
				if(isExpirationEnforced) {
					// TODO: hack: backend returns string instead of integer
					var shareTime = this.model.get('linkShare').stime;
					if (_.isNumber(shareTime)) {
						shareTime = new Date(shareTime * 1000);
					}
					if (!shareTime) {
						shareTime = new Date(); // now
					}
					shareTime = OC.Util.stripTime(shareTime).getTime();
					maxDate = new Date(shareTime + defaultExpireDays * 24 * 3600 * 1000);
				}
			}
			$.datepicker.setDefaults({
				minDate: minDate,
				maxDate: maxDate
			});

			this.$el.find('.datepicker').datepicker({dateFormat : 'dd-mm-yy'});

			var popover = this.popoverMenuTemplate({
				cid: this.model.get('linkShare').id,
				copyLabel: t('core', 'Copy URL'),
				social: social,

				shareLinkURL: this.model.get('linkShare').link,
				urlLabel: t('core', 'Link'),
				enablePasswordLabel: t('core', 'Password protect'),
				passwordLabel: t('core', 'Password'),
				passwordPlaceholder: isPasswordSet ? PASSWORD_PLACEHOLDER : PASSWORD_PLACEHOLDER_MESSAGE,
				passwordPlaceholderInitial: passwordPlaceholderInitial,
				isPasswordSet: isPasswordSet || isPasswordEnabledByDefault || isPasswordEnforced,
				publicUpload: publicUpload && isLinkShare,
				publicEditing: publicEditable,
				publicEditingChecked: publicEditingChecked,
				publicEditingLabel: t('core', 'Allow editing'),
				mailPrivatePlaceholder: t('core', 'Email link to person'),
				mailButtonText: t('core', 'Send'),
				publicUploadRWLabel: t('core', 'Allow upload and editing'),
				publicUploadRLabel: t('core', 'Read only'),
				publicUploadWLabel: t('core', 'File drop (upload only)'),
				publicUploadRWValue: OC.PERMISSION_UPDATE | OC.PERMISSION_CREATE | OC.PERMISSION_READ | OC.PERMISSION_DELETE,
				publicUploadRValue: OC.PERMISSION_READ,
				publicUploadWValue: OC.PERMISSION_CREATE,
				publicUploadRWChecked: publicUploadRWChecked,
				publicUploadRChecked: publicUploadRChecked,
				publicUploadWChecked: publicUploadWChecked,
				expireDateLabel: t('core', 'Set expiration date'),
				expirationLabel: t('core', 'Expiration'),
				expirationDatePlaceholder: t('core', 'Expiration date'),
				hasExpireDate: hasExpireDate,
				isExpirationEnforced: isExpirationEnforced,
				isPasswordEnforced: isPasswordEnforced,
				expireDate: expireDate,
				defaultExpireDate: moment().add(1, 'day').format('DD-MM-YYYY'), // Can't expire today
				shareNote: this.model.get('linkShare').note,
				addNoteLabel: t('core', 'Note to recipient'),
			});

			var pendingPopover = this.pendingPopoverMenuTemplate({
				cid: this.model.get('linkShare').id,
				enablePasswordLabel: t('core', 'Password protect'),
				passwordLabel: t('core', 'Password'),
				passwordPlaceholder: isPasswordSet ? PASSWORD_PLACEHOLDER : PASSWORD_PLACEHOLDER_MESSAGE,
				passwordPlaceholderInitial: passwordPlaceholderInitial,
				isPasswordEnforced: isPasswordEnforced,
			});

			this.$el.html(linkShareTemplate({
				cid: this.model.get('linkShare').id,
				shareAllowed: true,
				isLinkShare: isLinkShare,
				linkShareLabel: t('core', 'Share link'),
				linkShareEnableLabel: t('core', 'Enable'),
				popoverMenu: popover,
				pendingPopoverMenu: pendingPopover,
				showMenu: isLinkShare || this.showPending,
				showPending: this.showPending && !isLinkShare
			}));

			this.delegateEvents();

			// new note autosize
			autosize(this.$el.find('.share-note-form .share-note'));

			return this;
		},

		onToggleMenu: function(event) {
			event.preventDefault();
			event.stopPropagation();
			var $element = $(event.target);
			var $li = $element.closest('li[data-share-id]');
			var $menu = $li.find('.sharingOptionsGroup .popovermenu');

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

		/**
		 * renders the pending popover template and returns the resulting HTML
		 *
		 * @param {Object} data
		 * @returns {string}
		 */
		pendingPopoverMenuTemplate: function(data) {
			if(!this._pendingPopoverMenuTemplate) {
				this._pendingPopoverMenuTemplate = Handlebars.compile(TEMPLATE_POPOVER_MENU_PENDING);
			}
			return this._pendingPopoverMenuTemplate(data);
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
		},

		onExpireDateChange: function(event) {
			var $element = $(event.target);
			var li = $element.closest('li[data-share-id]');
			var shareId = li.data('share-id');
			var expirationDatePicker = '#expirationDateContainer-' + shareId;
			var datePicker = $(expirationDatePicker);
			var state = $element.prop('checked');
			datePicker.toggleClass('hidden', !state);
			
			if (!state) {
				// disabled, let's hide the input and
				// set the expireDate to nothing
				$element.closest('li').next('li').addClass('hidden');
				this.setExpirationDate('');
			} else {
				// enabled, show the input and the datepicker
				$element.closest('li').next('li').removeClass('hidden');
				this.showDatePicker(event);

			}
		},

		showDatePicker: function(event) {
			var $element = $(event.target);
			var li = $element.closest('li[data-share-id]');
			var shareId = li.data('share-id');
			var expirationDatePicker = '#expirationDatePicker-' + shareId;
			var self = this;

			$(expirationDatePicker).datepicker({
				dateFormat : 'dd-mm-yy',
				onSelect: function (expireDate) {
					self.setExpirationDate(expireDate);
				}
			});
			$(expirationDatePicker).datepicker('show');
			$(expirationDatePicker).focus();

		},

		setExpirationDate: function(expireDate) {
			this.model.saveLinkShare({expireDate: expireDate});
		},

		onChangeExpirationDate: function(event) {
			var $element = $(event.target);
			var expireDate = $element.val();
			var li = $element.closest('li[data-share-id]');
			var shareId = li.data('share-id');
			var expirationDatePicker = '#expirationDatePicker-' + shareId;

			this.setExpirationDate(expireDate, shareId);
			$(expirationDatePicker).datepicker('hide');
		}

	});

	OC.Share.ShareDialogLinkShareView = ShareDialogLinkShareView;

})();
