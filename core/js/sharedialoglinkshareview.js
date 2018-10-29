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

		/** @type {boolean} **/
		showLink: true,

		/** @type {boolean} **/
		showPending: false,

		events: {
			// open menu
			'click .share-menu .icon-more': 'onToggleMenu',
			// hide download
			'change .hideDownloadCheckbox': 'onHideDownloadChange',
			// password
			'focusout input.linkPassText': 'onPasswordEntered',
			'keyup input.linkPassText': 'onPasswordKeyUp',
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
			'click .share-note-submit': 'updateNote',
			// remove
			'click .unshare': 'onUnshare',
			// new share
			'click .new-share': 'newShare',
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

			if(!_.isUndefined(options.configModel)) {
				this.configModel = options.configModel;
			} else {
				throw 'missing OC.Share.ShareConfigModel';
			}

			var clipboard = new Clipboard('.clipboardButton');
			clipboard.on('success', function(e) {
				var $menu = $(e.trigger);

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
				var $linkTextMenu = $menu.parent().next('li.linkTextMenu');
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

		newShare: function() {
			var self = this;
			var $loading = this.$el.find('.icon-loading-small').eq(0);
			if(!$loading.hasClass('hidden')) {
				// in process
				return false;
			}
			// hide all icons and show loading
			this.$el.find('.icon').addClass('hidden');
			$loading.removeClass('hidden');

			this.model.saveLinkShare({}, {
				success: function() {
					$loading.addClass('hidden');
					self.$el.find('.icon').removeClass('hidden');
					self.render();
				},
				error: function(obj, msg) {
					OC.Notification.showTemporary(t('core', 'Unable to create a link share'));
					$loading.addClass('hidden');
					self.$el.find('.icon').removeClass('hidden');
				}
			})
		},

		onLinkTextClick: function(event) {
			var $element = $(event.target);
			var $li = $element.closest('li[data-share-id]');
			var $el = $li.find('.linkText');
			$el.focus();
			$el.select();
		},

		onHideDownloadChange: function(event) {
			var $element = $(event.target);
			var $li = $element.closest('li[data-share-id]');
			var shareId = $li.data('share-id');
			var $checkbox = $li.find('.hideDownloadCheckbox');
			$checkbox.siblings('.icon-loading-small').removeClass('hidden').addClass('inlineblock');

			var hideDownload = false;
			if($checkbox.is(':checked')) {
				hideDownload = true;
			}

			this.model.saveLinkShare({
				hideDownload: hideDownload,
				cid: shareId
			}, {
				success: function() {
					$checkbox.siblings('.icon-loading-small').addClass('hidden').removeClass('inlineblock');
				},
				error: function(obj, msg) {
					OC.Notification.showTemporary(t('core', 'Unable to toggle this option'));
					$checkbox.siblings('.icon-loading-small').addClass('hidden').removeClass('inlineblock');
				}
			});
		},

		onShowPasswordClick: function(event) {
			var $element = $(event.target);
			var $li = $element.closest('li[data-share-id]');
			var shareId = $li.data('share-id');
			$li.find('.linkPass').slideToggle(OC.menuSpeed);
			$li.find('.linkPassMenu').toggleClass('hidden');
			if(!$li.find('.showPasswordCheckbox').is(':checked')) {
				this.model.saveLinkShare({
					password: '',
					cid: shareId
				});
			} else {
				if (!OC.Util.isIE()) {
					$li.find('.linkPassText').focus();
				}
			}
		},

		onPasswordKeyUp: function(event) {
			if(event.keyCode === 13) {
				this.onPasswordEntered(event);
			}
		},

		onPasswordEntered: function(event) {
			var $element = $(event.target);
			var $li = $element.closest('li[data-share-id]');
			var shareId = $li.data('share-id');
			var $loading = $li.find('.linkPassMenu .icon-loading-small');
			if (!$loading.hasClass('hidden')) {
				// still in process
				return;
			}
			var $input = $li.find('.linkPassText');
			$input.removeClass('error');
			var password = $input.val();

			if ($li.find('.linkPassText').attr('placeholder') === PASSWORD_PLACEHOLDER_MESSAGE_OPTIONAL) {

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
				password: password,
				cid: shareId
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

		onAllowPublicEditingChange: function(event) {
			var $element = $(event.target);
			var $li = $element.closest('li[data-share-id]');
			var shareId = $li.data('share-id');
			var $checkbox = $li.find('.publicEditingCheckbox');
			$checkbox.siblings('.icon-loading-small').removeClass('hidden').addClass('inlineblock');

			var permissions = OC.PERMISSION_READ;
			if($checkbox.is(':checked')) {
				permissions = OC.PERMISSION_UPDATE | OC.PERMISSION_READ;
			}

			this.model.saveLinkShare({
				permissions: permissions,
				cid: shareId
			}, {
				success: function() {
					$checkbox.siblings('.icon-loading-small').addClass('hidden').removeClass('inlineblock');
				},
				error: function(obj, msg) {
					OC.Notification.showTemporary(t('core', 'Unable to toggle this option'));
					$checkbox.siblings('.icon-loading-small').addClass('hidden').removeClass('inlineblock');
				}
			});
		},


		onPublicUploadChange: function(event) {
			var $element = $(event.target);
			var $li = $element.closest('li[data-share-id]');
			var shareId = $li.data('share-id');
			var permissions = event.currentTarget.value;
			this.model.saveLinkShare({
				permissions: permissions,
				cid: shareId
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


			var publicEditingChecked = '';
			if(this.model.isPublicEditingAllowed()) {
				publicEditingChecked = 'checked="checked"';
			}

			var isPasswordEnforced = this.configModel.get('enforcePasswordForPublicLink');
			var isPasswordEnabledByDefault = this.configModel.get('enableLinkPasswordByDefault') === true;
			var passwordPlaceholderInitial = this.configModel.get('enforcePasswordForPublicLink')
				? PASSWORD_PLACEHOLDER_MESSAGE : PASSWORD_PLACEHOLDER_MESSAGE_OPTIONAL;

			var publicEditable =
				!this.model.isFolder()
				&& this.model.updatePermissionPossible();

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
			var isExpirationEnforced = this.configModel.get('isDefaultExpireDateEnforced');
			
			// what if there is another date picker on that page?
			var minDate = new Date();
			// min date should always be the next day
			minDate.setDate(minDate.getDate()+1);

			$.datepicker.setDefaults({
				minDate: minDate
			});

			this.$el.find('.datepicker').datepicker({dateFormat : 'dd-mm-yy'});

			var popoverBase = {
				copyLabel: t('core', 'Copy link'),
				social: social,
				urlLabel: t('core', 'Link'),
				hideDownloadLabel: t('core', 'Hide download'),
				enablePasswordLabel: t('core', 'Password protect'),
				passwordLabel: t('core', 'Password'),
				passwordPlaceholderInitial: passwordPlaceholderInitial,
				publicUpload: publicUpload,
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
				expireDateLabel: t('core', 'Set expiration date'),
				expirationLabel: t('core', 'Expiration'),
				expirationDatePlaceholder: t('core', 'Expiration date'),
				isExpirationEnforced: isExpirationEnforced,
				isPasswordEnforced: isPasswordEnforced,
				defaultExpireDate: moment().add(1, 'day').format('DD-MM-YYYY'), // Can't expire today
				addNoteLabel: t('core', 'Note to recipient'),
				unshareLabel: t('core', 'Unshare'),
				newShareLabel: t('core', 'New share link'),
			};

			var pendingPopoverBase = {
				enablePasswordLabel: t('core', 'Password protect'),
				passwordLabel: t('core', 'Password'),
				passwordPlaceholderInitial: passwordPlaceholderInitial,
				isPasswordEnforced: isPasswordEnforced,
			};

			var linkShares = this.getShareeList();
			if(_.isArray(linkShares)) {
				for (var i = 0; i < linkShares.length; i++) {
					var popover = this.getPopoverObject(linkShares[i])
					var pendingPopover = this.getPendingPopoverObject(linkShares[i])
					linkShares[i].popoverMenu = this.popoverMenuTemplate(_.extend({}, popoverBase, popover));
					linkShares[i].pendingPopoverMenu = this.pendingPopoverMenuTemplate(_.extend({}, pendingPopoverBase, pendingPopover));
				}
			}

			this.$el.html(linkShareTemplate({
				linkShares: linkShares,
				shareAllowed: true,
				nolinkShares: linkShares.length === 0,
				newShareLabel: t('core', 'Share link'),
				newShareTitle: t('core', 'New share link'),
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
			var shareId = $li.data('share-id');

			OC.showMenu(null, $menu);
			this._menuOpen = shareId;
		},

		/**
		 * @returns {Function} from Handlebars
		 * @private
		 */
		template: function () {
			return OC.Share.Templates['sharedialoglinkshareview'];
		},

		/**
		 * renders the popover template and returns the resulting HTML
		 *
		 * @param {Object} data
		 * @returns {string}
		 */
		popoverMenuTemplate: function(data) {
			return OC.Share.Templates['sharedialoglinkshareview_popover_menu'](data);
		},

		/**
		 * renders the pending popover template and returns the resulting HTML
		 *
		 * @param {Object} data
		 * @returns {string}
		 */
		pendingPopoverMenuTemplate: function(data) {
			return OC.Share.Templates['sharedialoglinkshareview_popover_menu_pending'](data);
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
			var maxDate = $element.data('max-date');
			var expirationDatePicker = '#expirationDatePicker-' + shareId;
			var self = this;

			$(expirationDatePicker).datepicker({
				dateFormat : 'dd-mm-yy',
				onSelect: function (expireDate) {
					self.setExpirationDate(expireDate, shareId);
				},
				maxDate: maxDate
			});
			$(expirationDatePicker).datepicker('show');
			$(expirationDatePicker).focus();

		},

		setExpirationDate: function(expireDate, shareId) {
			this.model.saveLinkShare({expireDate: expireDate, cid: shareId});
		},

		/**
		 * get an array of sharees' share properties
		 *
		 * @returns {Array}
		 */
		getShareeList: function() {
			var shares = this.model.get('linkShares');

			if(!this.model.hasLinkShares()) {
				return [];
			}

			var list = [];
			for(var index = 0; index < shares.length; index++) {
				var share = this.getShareeObject(index);
				// first empty {} is necessary, otherwise we get in trouble
				// with references
				list.push(_.extend({}, share));
			}

			return list;
		},

		/**
		 *
		 * @param {OC.Share.Types.ShareInfo} shareInfo
		 * @returns {object}
		 */
		getShareeObject: function(shareIndex) {
			var share = this.model.get('linkShares')[shareIndex];

			return _.extend({}, share, {
				cid: share.id,
				shareAllowed: true,
				linkShareLabel: share.label !== '' ? share.label : t('core', 'Share link'),
				popoverMenu: {},
				pendingPopoverMenu: {},
				showPending: this.showPending
			})
		},

		getPopoverObject: function(share) {
			var publicUploadRWChecked = '';
			var publicUploadRChecked = '';
			var publicUploadWChecked = '';

			switch (this.model.linkSharePermissions(share.id)) {
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
	
			var isPasswordSet = !!share.password;
			var isPasswordEnabledByDefault = this.configModel.get('enableLinkPasswordByDefault') === true;
			var isPasswordEnforced = this.configModel.get('enforcePasswordForPublicLink');
			var showPasswordCheckBox = !this.configModel.get('enforcePasswordForPublicLink') || !share.password;
			var isExpirationEnforced = this.configModel.get('isDefaultExpireDateEnforced');
			var defaultExpireDays = this.configModel.get('defaultExpireDate');
			var hasExpireDate = !!share.expiration || isExpirationEnforced;
			var hasExpireDate = false;

			var expireDate;
			if (hasExpireDate) {
				expireDate = moment(share.expiration, 'YYYY-MM-DD').format('DD-MM-YYYY');
			}

			var showHideDownloadCheckbox = !this.model.isFolder();
			var hideDownload = share.hideDownload;

			var maxDate = null;

			if(hasExpireDate) {
				if(isExpirationEnforced) {
					// TODO: hack: backend returns string instead of integer
					var shareTime = share.stime;
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

			return {
				cid: share.id,
				shareLinkURL: share.url,
				passwordPlaceholder: isPasswordSet ? PASSWORD_PLACEHOLDER : PASSWORD_PLACEHOLDER_MESSAGE,
				isPasswordSet: isPasswordSet || isPasswordEnabledByDefault || isPasswordEnforced,
				showPasswordCheckBox: showPasswordCheckBox,
				publicUploadRWChecked: publicUploadRWChecked,
				publicUploadRChecked: publicUploadRChecked,
				publicUploadWChecked: publicUploadWChecked,
				hasExpireDate: hasExpireDate,
				expireDate: expireDate,
				shareNote: share.note,
				hasNote: share.note !== '',
				maxDate: maxDate,
				showHideDownloadCheckbox: showHideDownloadCheckbox,
				hideDownload: hideDownload,
				newShareTitle: t('core', 'New share link'),
			}
		},

		getPendingPopoverObject: function(share) {
			var isPasswordSet = !!share.password;
			var showPasswordCheckBox = !this.configModel.get('enforcePasswordForPublicLink') || !share.password;
			var isPasswordEnforced = this.configModel.get('enforcePasswordForPublicLink');

			return {
				cid: share.id,
				enablePasswordLabel: t('core', 'Password protect'),
				passwordLabel: t('core', 'Password'),
				passwordPlaceholder: isPasswordSet ? PASSWORD_PLACEHOLDER : PASSWORD_PLACEHOLDER_MESSAGE,
				showPasswordCheckBox: showPasswordCheckBox,
				isPasswordEnforced: isPasswordEnforced,
			}

		},

		onUnshare: function(event) {
			event.preventDefault();
			event.stopPropagation();
			var self = this;
			var $element = $(event.target);
			if (!$element.is('a')) {
				$element = $element.closest('a');
			}

			var $loading = $element.find('.icon-loading-small').eq(0);
			if(!$loading.hasClass('hidden')) {
				// in process
				return false;
			}
			$loading.removeClass('hidden');

			var $li = $element.closest('li[data-share-id]');

			var shareId = $li.data('share-id');

			self.model.removeShare(shareId, {
				success: function() {
					$li.remove();
					self.render()
				},
				error: function() {
					$loading.addClass('hidden');
					OC.Notification.showTemporary(t('core', 'Could not unshare'));
				}
			});
			return false;
		},


	});

	OC.Share.ShareDialogLinkShareView = ShareDialogLinkShareView;

})();
