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

		/** @type {string} **/
		password: '',

		/** @type {string} **/
		newShareId: 'new-share',

		events: {
			// open menu
			'click .share-menu .icon-more': 'onToggleMenu',
			// hide download
			'change .hideDownloadCheckbox': 'onHideDownloadChange',
			// password
			'click input.share-pass-submit': 'onPasswordEntered', 
			'keyup input.linkPassText': 'onPasswordKeyUp', // check for the enter key
			'change .showPasswordCheckbox': 'onShowPasswordClick',
			'change .passwordByTalkCheckbox': 'onPasswordByTalkChange',
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
			// enforced pass set
			'submit .enforcedPassForm': 'enforcedPasswordSet',
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

			this.model.on('change:linkShares', function(model, linkShares) {
				// The "Password protect by Talk" item is shown only when there
				// is a password. Unfortunately there is no fine grained
				// rendering of items in the link shares, so the whole view
				// needs to be rendered again when the password of a share
				// changes.
				// Note that this event handler is concerned only about password
				// changes; other changes in the link shares does not trigger
				// a rendering, so the view must be rendered again as needed in
				// those cases (for example, when a link share is removed).
				
				var previousLinkShares = model.previous('linkShares');
				if (previousLinkShares.length !== linkShares.length) {
					return;
				}

				var i;
				for (i = 0; i < linkShares.length; i++) {
					if (linkShares[i].id !== previousLinkShares[i].id) {
						// A resorting should never happen, but just in case.
						return;
					}

					if (linkShares[i].password !== previousLinkShares[i].password) {
						view.render();

						return;
					}
				}
			});

			if(!_.isUndefined(options.configModel)) {
				this.configModel = options.configModel;
			} else {
				throw 'missing OC.Share.ShareConfigModel';
			}

			var clipboard = new Clipboard('.clipboard-button');
			clipboard.on('success', function(e) {
				var $trigger = $(e.trigger);

				$trigger.tooltip('hide')
					.attr('data-original-title', t('core', 'Copied!'))
					.tooltip('fixTitle')
					.tooltip({placement: 'bottom', trigger: 'manual'})
					.tooltip('show');
				_.delay(function() {
					$trigger.tooltip('hide')
						.attr('data-original-title', t('core', 'Copy link'))
						.tooltip('fixTitle')
				}, 3000);
			});
			clipboard.on('error', function (e) {
				var $trigger = $(e.trigger);
				var $menu = $trigger.next('.share-menu').find('.popovermenu');
				var $linkTextMenu = $menu.find('li.linkTextMenu');
				var $input = $linkTextMenu.find('.linkText');

				var $li = $trigger.closest('li[data-share-id]');
				var shareId = $li.data('share-id');

				// show menu
				OC.showMenu(null, $menu);

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

		newShare: function(event) {
			var self = this;
			var $target = $(event.target);
			var $li = $target.closest('li[data-share-id]');
			var shareId = $li.data('share-id');
			var $loading = $li.find('.share-menu > .icon-loading-small');

			if(!$loading.hasClass('hidden') && this.password === '') {
				// in process
				return false;
			}

			// hide all icons and show loading
			$li.find('.icon').addClass('hidden');
			$loading.removeClass('hidden');

			// hide menu
			OC.hideMenus();

			var shareData = {}

			var isPasswordEnforced = this.configModel.get('enforcePasswordForPublicLink');
			var isExpirationEnforced = this.configModel.get('isDefaultExpireDateEnforced');

			// set default expire date
			if (isExpirationEnforced) {
				var defaultExpireDays = this.configModel.get('defaultExpireDate');
				var expireDate = moment().add(defaultExpireDays, 'day').format('DD-MM-YYYY')
				shareData.expireDate = expireDate;
			}

			// if password is set, add to data
			if (isPasswordEnforced && this.password !== '') {
				shareData.password = this.password
			}

			var newShareId = false;

			// We need a password before the share creation
			if (isPasswordEnforced && !this.showPending && this.password === '') {
				this.showPending = shareId;
				var self = this.render();
				self.$el.find('.pending #enforcedPassText').focus();
			} else {
				// else, we have a password or it is not enforced
				$.when(this.model.saveLinkShare(shareData, {
					success: function() {
						$loading.addClass('hidden');
						$li.find('.icon').removeClass('hidden');
						self.render();
						// open the menu by default
						// we can only do that after the render
						if (newShareId) {
							var shares = self.$el.find('li[data-share-id]');
							var $newShare = self.$el.find('li[data-share-id="'+newShareId+'"]');
						}
					},
					error: function() {
						// empty function to override the default Dialog warning
					}
				})).fail(function(response) {
					// password failure? Show error
					self.password = ''
					if (isPasswordEnforced && response && response.responseJSON && response.responseJSON.ocs.meta && response.responseJSON.ocs.meta.message) {
						var $input = self.$el.find('.pending #enforcedPassText')
						$input.tooltip('destroy');
						$input.attr('title', response.responseJSON.ocs.meta.message);
						$input.tooltip({placement: 'bottom', trigger: 'manual'});
						$input.tooltip('show');
					} else {
						OC.Notification.showTemporary(t('core', 'Unable to create a link share'));
						$loading.addClass('hidden');
						$li.find('.icon').removeClass('hidden');
					}
				}).then(function(response) {
					// resolve before success
					newShareId = response.ocs.data.id
				});
			}
		},

		enforcedPasswordSet: function(event) {
			event.preventDefault();
			var $form = $(event.target);
			var $input = $form.find('input.enforcedPassText');
			this.password = $input.val();
			this.showPending = false;
			this.newShare(event);
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
			$input.parent().find('input').removeClass('error');
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
					// Add visual feedback to both the input and the submit button
					$input.parent().find('input').addClass('error');

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

		onPasswordByTalkChange: function(event) {
			var $element = $(event.target);
			var $li = $element.closest('li[data-share-id]');
			var shareId = $li.data('share-id');
			var $checkbox = $li.find('.passwordByTalkCheckbox');
			$checkbox.siblings('.icon-loading-small').removeClass('hidden').addClass('inlineblock');

			var sendPasswordByTalk = false;
			if($checkbox.is(':checked')) {
				sendPasswordByTalk = true;
			}

			this.model.saveLinkShare({
				sendPasswordByTalk: sendPasswordByTalk,
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
			$menu.find('.share-note-delete').toggleClass('hidden');
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
			$menu.find('.share-note-delete').addClass('hidden');

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
			this.$el.find('.has-tooltip').tooltip();

			// reset previously set passwords
			this.password = '';

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

			var isExpirationEnforced = this.configModel.get('isDefaultExpireDateEnforced');

			// what if there is another date picker on that page?
			var minDate = new Date();
			// min date should always be the next day
			minDate.setDate(minDate.getDate()+1);

			$.datepicker.setDefaults({
				minDate: minDate
			});

			this.$el.find('.datepicker').datepicker({dateFormat : 'dd-mm-yy'});

			var minPasswordLength = 4
			// password policy?
			if(OC.getCapabilities().password_policy && OC.getCapabilities().password_policy.minLength) {
				minPasswordLength = OC.getCapabilities().password_policy.minLength;
			}

			var popoverBase = {
				urlLabel: t('core', 'Link'),
				hideDownloadLabel: t('core', 'Hide download'),
				enablePasswordLabel: isPasswordEnforced ? t('core', 'Password protection enforced') : t('core', 'Password protect'),
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
				expireDateLabel: isExpirationEnforced ? t('core', 'Expiration date enforced') : t('core', 'Set expiration date'),
				expirationLabel: t('core', 'Expiration'),
				expirationDatePlaceholder: t('core', 'Expiration date'),
				isExpirationEnforced: isExpirationEnforced,
				isPasswordEnforced: isPasswordEnforced,
				defaultExpireDate: moment().add(1, 'day').format('DD-MM-YYYY'), // Can't expire today
				addNoteLabel: t('core', 'Note to recipient'),
				unshareLabel: t('core', 'Unshare'),
				unshareLinkLabel: t('core', 'Delete share link'),
				newShareLabel: t('core', 'Add another link'),
			};

			var pendingPopover = {
				isPasswordEnforced: isPasswordEnforced,
				enforcedPasswordLabel: t('core', 'Password protection for links is mandatory'),
				passwordPlaceholder: passwordPlaceholderInitial,
				minPasswordLength: minPasswordLength,
			};
			var pendingPopoverMenu = this.pendingPopoverMenuTemplate(_.extend({}, pendingPopover))

			var linkShares = this.getShareeList();
			if(_.isArray(linkShares)) {
				for (var i = 0; i < linkShares.length; i++) {
					var social = [];
					OC.Share.Social.Collection.each(function (model) {
						var url = model.get('url');
						url = url.replace('{{reference}}', linkShares[i].shareLinkURL);
						social.push({
							url: url,
							label: t('core', 'Share to {name}', {name: model.get('name')}),
							name: model.get('name'),
							iconClass: model.get('iconClass'),
							newWindow: model.get('newWindow')
						});
					});
					var popover = this.getPopoverObject(linkShares[i])
					linkShares[i].popoverMenu = this.popoverMenuTemplate(_.extend({}, popoverBase, popover, {social: social}));
					linkShares[i].pendingPopoverMenu = pendingPopoverMenu
				}
			}

			this.$el.html(linkShareTemplate({
				linkShares: linkShares,
				shareAllowed: true,
				nolinkShares: linkShares.length === 0,
				newShareLabel: t('core', 'Share link'),
				newShareTitle: t('core', 'New share link'),
				pendingPopoverMenu: pendingPopoverMenu,
				showPending: this.showPending === this.newShareId,
				newShareId: this.newShareId,
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

			// focus the password if not set and enforced
			var isPasswordEnabledByDefault = this.configModel.get('enableLinkPasswordByDefault') === true;
			var haspassword = $menu.find('.linkPassText').val() !== '';

			if (!haspassword && isPasswordEnabledByDefault) {
				$menu.find('.linkPassText').focus();
			}
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
				this.setExpirationDate('', shareId);
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

		onChangeExpirationDate: function(event) {
			var $element = $(event.target);
			var expireDate = $element.val();
			var li = $element.closest('li[data-share-id]');
			var shareId = li.data('share-id');
			var expirationDatePicker = '#expirationDatePicker-' + shareId;

			this.setExpirationDate(expireDate, shareId);
			$(expirationDatePicker).datepicker('hide');
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
				linkShareLabel: share.label ? share.label : t('core', 'Share link'),
				popoverMenu: {},
				shareLinkURL: share.url,
				newShareTitle: t('core', 'New share link'),
				copyLabel: t('core', 'Copy link'),
				showPending: this.showPending === share.id,
				linkShareCreationDate: t('core', 'Created on {time}', { time: moment(share.stime * 1000).format('LLLL') })
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
			var isExpirationEnforced = this.configModel.get('isDefaultExpireDateEnforced');
			var defaultExpireDays = this.configModel.get('defaultExpireDate');
			var hasExpireDate = !!share.expiration || isExpirationEnforced;

			var expireDate;
			if (hasExpireDate) {
				expireDate = moment(share.expiration, 'YYYY-MM-DD').format('DD-MM-YYYY');
			}

			var isTalkEnabled = OC.appswebroots['spreed'] !== undefined;
			var sendPasswordByTalk = share.sendPasswordByTalk;

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
				showPasswordByTalkCheckBox: isTalkEnabled && isPasswordSet,
				passwordByTalkLabel: t('core', 'Password protect by Talk'),
				isPasswordByTalkSet: sendPasswordByTalk,
				publicUploadRWChecked: publicUploadRWChecked,
				publicUploadRChecked: publicUploadRChecked,
				publicUploadWChecked: publicUploadWChecked,
				hasExpireDate: hasExpireDate,
				expireDate: expireDate,
				shareNote: share.note,
				hasNote: share.note !== '',
				maxDate: maxDate,
				hideDownload: hideDownload,
				isExpirationEnforced: isExpirationEnforced,
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
