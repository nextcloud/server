/* global OC, Handlebars */

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

	var PASSWORD_PLACEHOLDER = '**********';
	var PASSWORD_PLACEHOLDER_MESSAGE = t('core', 'Choose a password for the mail share');

	if (!OC.Share) {
		OC.Share = {};
	}

	var TEMPLATE =
			'<ul id="shareWithList" class="shareWithList">' +
			'{{#each sharees}}' +
				'<li data-share-id="{{shareId}}" data-share-type="{{shareType}}" data-share-with="{{shareWith}}">' +
					'<div class="avatar {{#if modSeed}}imageplaceholderseed{{/if}}" data-username="{{shareWith}}" data-avatar="{{shareWithAvatar}}" data-displayname="{{shareWithDisplayName}}" {{#if modSeed}}data-seed="{{shareWith}} {{shareType}}"{{/if}}></div>' +
					'<span class="username" title="{{shareWithTitle}}">{{shareWithDisplayName}}</span>' +
					'<span class="sharingOptionsGroup">' +
						'{{#if editPermissionPossible}}' +
						'<span class="shareOption">' +
							'<input id="canEdit-{{cid}}-{{shareId}}" type="checkbox" name="edit" class="permissions checkbox" />' +
							'<label for="canEdit-{{cid}}-{{shareId}}">{{canEditLabel}}</label>' +
						'</span>' +
						'{{/if}}' +
						'<div tabindex="0" class="share-menu"><span class="icon icon-more"></span>' +
							'{{{popoverMenu}}}' +
						'</div>' +
					'</span>' +
				'</li>' +
			'{{/each}}' +
			'{{#each linkReshares}}' +
				'<li data-share-id="{{shareId}}" data-share-type="{{shareType}}">' +
					'<div class="avatar" data-username="{{shareInitiator}}"></div>' +
					'<span class="has-tooltip username" title="{{shareInitiator}}">' + t('core', '{{shareInitiatorDisplayName}} shared via link') + '</span>' +

					'<span class="sharingOptionsGroup">' +
						'<a href="#" class="unshare"><span class="icon-loading-small hidden"></span><span class="icon icon-delete"></span><span class="hidden-visually">{{unshareLabel}}</span></a>' +
					'</span>' +
				'</li>' +
			'{{/each}}' +
			'</ul>'
		;

	var TEMPLATE_POPOVER_MENU =
		'<div class="popovermenu bubble hidden menu">' +
			'<ul>' +
				'{{#if isResharingAllowed}} {{#if sharePermissionPossible}} {{#unless isMailShare}}' +
				'<li>' +
					'<span class="shareOption menuitem">' +
						'<input id="canShare-{{cid}}-{{shareId}}" type="checkbox" name="share" class="permissions checkbox" {{#if hasSharePermission}}checked="checked"{{/if}} data-permissions="{{sharePermission}}" />' +
						'<label for="canShare-{{cid}}-{{shareId}}">{{canShareLabel}}</label>' +
					'</span>' +
				'</li>' +
				'{{/unless}} {{/if}} {{/if}}' +
				'{{#if isFolder}}' +
					'{{#if createPermissionPossible}}{{#unless isMailShare}}' +
					'<li>' +
						'<span class="shareOption menuitem">' +
							'<input id="canCreate-{{cid}}-{{shareId}}" type="checkbox" name="create" class="permissions checkbox" {{#if hasCreatePermission}}checked="checked"{{/if}} data-permissions="{{createPermission}}"/>' +
							'<label for="canCreate-{{cid}}-{{shareId}}">{{createPermissionLabel}}</label>' +
						'</span>' +
					'</li>' +
					'{{/unless}}{{/if}}' +
					'{{#if updatePermissionPossible}}{{#unless isMailShare}}' +
					'<li>' +
						'<span class="shareOption menuitem">' +
							'<input id="canUpdate-{{cid}}-{{shareId}}" type="checkbox" name="update" class="permissions checkbox" {{#if hasUpdatePermission}}checked="checked"{{/if}} data-permissions="{{updatePermission}}"/>' +
							'<label for="canUpdate-{{cid}}-{{shareId}}">{{updatePermissionLabel}}</label>' +
						'</span>' +
					'</li>' +
					'{{/unless}}{{/if}}' +
					'{{#if deletePermissionPossible}}{{#unless isMailShare}}' +
					'<li>' +
						'<span class="shareOption menuitem">' +
							'<input id="canDelete-{{cid}}-{{shareId}}" type="checkbox" name="delete" class="permissions checkbox" {{#if hasDeletePermission}}checked="checked"{{/if}} data-permissions="{{deletePermission}}"/>' +
							'<label for="canDelete-{{cid}}-{{shareId}}">{{deletePermissionLabel}}</label>' +
						'</span>' +
					'</li>' +
					'{{/unless}}{{/if}}' +
				'{{/if}}' +
				'{{#if isMailShare}}' +
					'{{#if hasCreatePermission}}' +
						'<li>' +
							'<span class="shareOption menuitem">' +
								'<input id="secureDrop-{{cid}}-{{shareId}}" type="checkbox" name="secureDrop" class="checkbox secureDrop" {{#if secureDropMode}}checked="checked"{{/if}} data-permissions="{{readPermission}}"/>' +
								'<label for="secureDrop-{{cid}}-{{shareId}}">{{secureDropLabel}}</label>' +
							'</span>' +
						'</li>' +
					'{{/if}}' +
					'<li>' +
						'<span class="shareOption menuitem">' +
							'<input id="password-{{cid}}-{{shareId}}" type="checkbox" name="password" class="password checkbox" {{#if isPasswordSet}}checked="checked"{{/if}}{{#if isPasswordSet}}{{#if isPasswordForMailSharesRequired}}disabled=""{{/if}}{{/if}}" />' +
							'<label for="password-{{cid}}-{{shareId}}">{{passwordLabel}}</label>' +
						'</span>' +
					'</li>' +
					'<li class="passwordMenu-{{cid}}-{{shareId}} {{#unless isPasswordSet}}hidden{{/unless}}">' +
						'<span class="passwordContainer-{{cid}}-{{shareId}} icon-passwordmail menuitem">' +
						'    <label for="passwordField-{{cid}}-{{shareId}}" class="hidden-visually" value="{{password}}">{{passwordLabel}}</label>' +
						'    <input id="passwordField-{{cid}}-{{shareId}}" class="passwordField" type="password" placeholder="{{passwordPlaceholder}}" value="{{passwordValue}}" autocomplete="new-password" />' +
						'    <span class="icon-loading-small hidden"></span>' +
						'</span>' +
					'</li>' +
					'{{#if isTalkEnabled}}' +
					'<li>' +
						'<span class="shareOption menuitem">' +
							'<input id="passwordByTalk-{{cid}}-{{shareId}}" type="checkbox" name="passwordByTalk" class="passwordByTalk checkbox" {{#if isPasswordByTalkSet}}checked="checked"{{/if}} />' +
							'<label for="passwordByTalk-{{cid}}-{{shareId}}">{{passwordByTalkLabel}}</label>' +
						'</span>' +
					'</li>' +
					'<li class="passwordByTalkMenu-{{cid}}-{{shareId}} {{#unless isPasswordByTalkSet}}hidden{{/unless}}">' +
						'<span class="passwordByTalkContainer-{{cid}}-{{shareId}} icon-passwordtalk menuitem">' +
						'    <label for="passwordByTalkField-{{cid}}-{{shareId}}" class="hidden-visually" value="{{password}}">{{passwordByTalkLabel}}</label>' +
						'    <input id="passwordByTalkField-{{cid}}-{{shareId}}" class="passwordField" type="password" placeholder="{{passwordByTalkPlaceholder}}" value="{{passwordValue}}" autocomplete="new-password" />' +
						'    <span class="icon-loading-small hidden"></span>' +
						'</span>' +
					'</li>' +
					'{{/if}}' +
				'{{/if}}' +
				'<li>' +
					'<span class="shareOption menuitem">' +
						'<input id="expireDate-{{cid}}-{{shareId}}" type="checkbox" name="expirationDate" class="expireDate checkbox" {{#if hasExpireDate}}checked="checked"{{/if}}" />' +
						'<label for="expireDate-{{cid}}-{{shareId}}">{{expireDateLabel}}</label>' +
					'</span>' +
				'</li>' +
				'<li class="expirationDateMenu-{{cid}}-{{shareId}} {{#unless hasExpireDate}}hidden{{/unless}}">' +
					'<span class="expirationDateContainer-{{cid}}-{{shareId}} icon-expiredate menuitem">' +
					'    <label for="expirationDatePicker-{{cid}}-{{shareId}}" class="hidden-visually" value="{{expirationDate}}">{{expirationLabel}}</label>' +
					'    <input id="expirationDatePicker-{{cid}}-{{shareId}}" class="datepicker" type="text" placeholder="{{expirationDatePlaceholder}}" value="{{#if hasExpireDate}}{{expireDate}}{{else}}{{defaultExpireDate}}{{/if}}" />' +
					'</span>' +
				'</li>' +
				'{{#if isNoteAvailable}}' +
				'<li>' +
					'<a href="#" class="share-add"><span class="icon-loading-small hidden"></span>' +
					'	<span class="icon icon-edit"></span>' +
					'	<span>{{addNoteLabel}}</span>' +
					'	<input type="button" class="share-note-delete icon-delete">' +
					'</a>' +
				'</li>' +
				'<li class="share-note-form hidden">' +
					'<span class="menuitem icon-note">' +
					'	<textarea class="share-note">{{shareNote}}</textarea>' +
					'	<input type="submit" class="icon-confirm share-note-submit" value="" id="add-note-{{shareId}}" />' +
					'</span>' +
				'</li>' +
				'{{/if}}' +
				'<li>' +
					'<a href="#" class="unshare"><span class="icon-loading-small hidden"></span><span class="icon icon-delete"></span><span>{{unshareLabel}}</span></a>' +
				'</li>' +
			'</ul>' +
		'</div>';

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

		/** @type {Function} **/
		_popoverMenuTemplate: undefined,

		_menuOpen: false,

		/** @type {boolean|number} **/
		_renderPermissionChange: false,

		events: {
			'click .unshare': 'onUnshare',
			'click .share-add': 'showNoteForm',
			'click .share-note-delete': 'deleteNote',
			'click .share-note-submit': 'updateNote',
			'click .share-menu .icon-more': 'onToggleMenu',
			'click .permissions': 'onPermissionChange',
			'click .expireDate' : 'onExpireDateChange',
			'click .password' : 'onMailSharePasswordProtectChange',
			'click .passwordByTalk' : 'onMailSharePasswordProtectByTalkChange',
			'click .secureDrop' : 'onSecureDropChange',
			'keyup input.passwordField': 'onMailSharePasswordKeyUp',
			'focusout input.passwordField': 'onMailSharePasswordEntered',
			'change .datepicker': 'onChangeExpirationDate',
			'click .datepicker' : 'showDatePicker'
		},

		initialize: function(options) {
			if(!_.isUndefined(options.configModel)) {
				this.configModel = options.configModel;
			} else {
				throw 'missing OC.Share.ShareConfigModel';
			}

			var view = this;
			this.model.on('change:shares', function() {
				view.render();
			});
		},

		/**
		 *
		 * @param {OC.Share.Types.ShareInfo} shareInfo
		 * @returns {object}
		 */
		getShareeObject: function(shareIndex) {
			var shareWith = this.model.getShareWith(shareIndex);
			var shareWithDisplayName = this.model.getShareWithDisplayName(shareIndex);
			var shareWithAvatar = this.model.getShareWithAvatar(shareIndex);
			var shareWithTitle = '';
			var shareType = this.model.getShareType(shareIndex);
			var sharedBy = this.model.getSharedBy(shareIndex);
			var sharedByDisplayName = this.model.getSharedByDisplayName(shareIndex);

			var hasPermissionOverride = {};
			if (shareType === OC.Share.SHARE_TYPE_GROUP) {
				shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'group') + ')';
			} else if (shareType === OC.Share.SHARE_TYPE_REMOTE) {
				shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'remote') + ')';
			} else if (shareType === OC.Share.SHARE_TYPE_REMOTE_GROUP) {
				shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'remote group') + ')';
			} else if (shareType === OC.Share.SHARE_TYPE_EMAIL) {
				shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'email') + ')';
			} else if (shareType === OC.Share.SHARE_TYPE_CIRCLE) {
			} else if (shareType === OC.Share.SHARE_TYPE_ROOM) {
				shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'conversation') + ')';
			}

			if (shareType === OC.Share.SHARE_TYPE_GROUP) {
				shareWithTitle = shareWith + " (" + t('core', 'group') + ')';
			} else if (shareType === OC.Share.SHARE_TYPE_REMOTE) {
				shareWithTitle = shareWith + " (" + t('core', 'remote') + ')';
			} else if (shareType === OC.Share.SHARE_TYPE_REMOTE_GROUP) {
				shareWithTitle = shareWith + " (" + t('core', 'remote group') + ')';
			}
			else if (shareType === OC.Share.SHARE_TYPE_EMAIL) {
				shareWithTitle = shareWith + " (" + t('core', 'email') + ')';
			} else if (shareType === OC.Share.SHARE_TYPE_CIRCLE) {
				shareWithTitle = shareWith;
				// Force "shareWith" in the template to a safe value, as the
				// original "shareWith" returned by the model may contain
				// problematic characters like "'".
				shareWith = 'circle-' + shareIndex;
			}

			if (sharedBy !== oc_current_user) {
				var empty = shareWithTitle === '';
				if (!empty) {
					shareWithTitle += ' (';
				}
				shareWithTitle += t('core', 'shared by {sharer}', {sharer: sharedByDisplayName});
				if (!empty) {
					shareWithTitle += ')';
				}
			}

			var share = this.model.get('shares')[shareIndex];
			var password = share.password;
			var hasPassword = password !== null && password !== '';
			var sendPasswordByTalk = share.send_password_by_talk;

			return _.extend(hasPermissionOverride, {
				cid: this.cid,
				hasSharePermission: this.model.hasSharePermission(shareIndex),
				editPermissionState: this.model.editPermissionState(shareIndex),
				hasCreatePermission: this.model.hasCreatePermission(shareIndex),
				hasUpdatePermission: this.model.hasUpdatePermission(shareIndex),
				hasDeletePermission: this.model.hasDeletePermission(shareIndex),
				shareWith: shareWith,
				shareWithDisplayName: shareWithDisplayName,
				shareWithAvatar: shareWithAvatar,
				shareWithTitle: shareWithTitle,
				shareType: shareType,
				shareId: this.model.get('shares')[shareIndex].id,
				modSeed: shareWithAvatar || (shareType !== OC.Share.SHARE_TYPE_USER && shareType !== OC.Share.SHARE_TYPE_CIRCLE && shareType !== OC.Share.SHARE_TYPE_ROOM),
				isRemoteShare: shareType === OC.Share.SHARE_TYPE_REMOTE,
				isRemoteGroupShare: shareType === OC.Share.SHARE_TYPE_REMOTE_GROUP,
				isNoteAvailable: shareType !== OC.Share.SHARE_TYPE_REMOTE && shareType !== OC.Share.SHARE_TYPE_REMOTE_GROUP,
				isMailShare: shareType === OC.Share.SHARE_TYPE_EMAIL,
				isCircleShare: shareType === OC.Share.SHARE_TYPE_CIRCLE,
				isFileSharedByMail: shareType === OC.Share.SHARE_TYPE_EMAIL && !this.model.isFolder(),
				isPasswordSet: hasPassword && !sendPasswordByTalk,
				isPasswordByTalkSet: hasPassword && sendPasswordByTalk,
				isTalkEnabled: oc_appswebroots['spreed'] !== undefined,
				secureDropMode: !this.model.hasReadPermission(shareIndex),
				hasExpireDate: this.model.getExpireDate(shareIndex) !== null,
				shareNote: this.model.getNote(shareIndex),
				expireDate: moment(this.model.getExpireDate(shareIndex), 'YYYY-MM-DD').format('DD-MM-YYYY'),
				// The password placeholder does not take into account if
				// sending the password by Talk is enabled or not; when
				// switching from sending the password by Talk to sending the
				// password by email the password is reused and the share
				// updated, so the placeholder already shows the password in the
				// brief time between disabling sending the password by email
				// and receiving the updated share.
				passwordPlaceholder: hasPassword ? PASSWORD_PLACEHOLDER : PASSWORD_PLACEHOLDER_MESSAGE,
				passwordByTalkPlaceholder: (hasPassword && sendPasswordByTalk)? PASSWORD_PLACEHOLDER : PASSWORD_PLACEHOLDER_MESSAGE,
			});
		},

		getShareProperties: function() {
			return {
				unshareLabel: t('core', 'Unshare'),
				addNoteLabel: t('core', 'Note to recipient'),
				canShareLabel: t('core', 'Can reshare'),
				canEditLabel: t('core', 'Can edit'),
				createPermissionLabel: t('core', 'Can create'),
				updatePermissionLabel: t('core', 'Can change'),
				deletePermissionLabel: t('core', 'Can delete'),
				secureDropLabel: t('core', 'File drop (upload only)'),
				expireDateLabel: t('core', 'Set expiration date'),
				passwordLabel: t('core', 'Password protect'),
				passwordByTalkLabel: t('core', 'Password protect by Talk'),
				crudsLabel: t('core', 'Access control'),
				expirationDatePlaceholder: t('core', 'Expiration date'),
				defaultExpireDate: moment().add(1, 'day').format('DD-MM-YYYY'), // Can't expire today
				triangleSImage: OC.imagePath('core', 'actions/triangle-s'),
				isResharingAllowed: this.configModel.get('isResharingAllowed'),
				isPasswordForMailSharesRequired: this.configModel.get('isPasswordForMailSharesRequired'),
				sharePermissionPossible: this.model.sharePermissionPossible(),
				editPermissionPossible: this.model.editPermissionPossible(),
				createPermissionPossible: this.model.createPermissionPossible(),
				updatePermissionPossible: this.model.updatePermissionPossible(),
				deletePermissionPossible: this.model.deletePermissionPossible(),
				sharePermission: OC.PERMISSION_SHARE,
				createPermission: OC.PERMISSION_CREATE,
				updatePermission: OC.PERMISSION_UPDATE,
				deletePermission: OC.PERMISSION_DELETE,
				readPermission: OC.PERMISSION_READ,
				isFolder: this.model.isFolder()
			};
		},

		/**
		 * get an array of sharees' share properties
		 *
		 * @returns {Array}
		 */
		getShareeList: function() {
			var universal = this.getShareProperties();

			if(!this.model.hasUserShares()) {
				return [];
			}

			var shares = this.model.get('shares');
			var list = [];
			for(var index = 0; index < shares.length; index++) {
				var share = this.getShareeObject(index);

				if (share.shareType === OC.Share.SHARE_TYPE_LINK) {
					continue;
				}
				// first empty {} is necessary, otherwise we get in trouble
				// with references
				list.push(_.extend({}, universal, share));
			}

			return list;
		},

		getLinkReshares: function() {
			var universal = {
				unshareLabel: t('core', 'Unshare'),
			};

			if(!this.model.hasUserShares()) {
				return [];
			}

			var shares = this.model.get('shares');
			var list = [];
			for(var index = 0; index < shares.length; index++) {
				var share = this.getShareeObject(index);

				if (share.shareType !== OC.Share.SHARE_TYPE_LINK) {
					continue;
				}
				// first empty {} is necessary, otherwise we get in trouble
				// with references
				list.push(_.extend({}, universal, share, {
					shareInitiator: shares[index].uid_owner,
					shareInitiatorDisplayName: shares[index].displayname_owner
				}));
			}

			return list;
		},

		render: function() {
			if(!this._renderPermissionChange) {
				this.$el.html(this.template({
					cid: this.cid,
					sharees: this.getShareeList(),
					linkReshares: this.getLinkReshares()
				}));

				this.$('.avatar').each(function () {
					var $this = $(this);

					if ($this.hasClass('imageplaceholderseed')) {
						$this.css({width: 32, height: 32});
						if ($this.data('avatar')) {
							$this.css('border-radius', '0%');
							$this.css('background', 'url(' + $this.data('avatar') + ') no-repeat');
							$this.css('background-size', '31px');
						} else {
							$this.imageplaceholder($this.data('seed'));
						}
					} else {
						//                         user,   size,  ie8fix, hidedefault,  callback, displayname
						$this.avatar($this.data('username'), 32, undefined, undefined, undefined, $this.data('displayname'));
					}
				});

				this.$('.has-tooltip').tooltip({
					placement: 'bottom'
				});

				this.$('ul.shareWithList > li').each(function() {
					var $this = $(this);

					var shareWith = $this.data('share-with');
					var shareType = $this.data('share-type');

					$this.find('div.avatar, span.username').contactsMenu(shareWith, shareType, $this);
				});
			} else {
				var permissionChangeShareId = parseInt(this._renderPermissionChange, 10);
				var shareWithIndex = this.model.findShareWithIndex(permissionChangeShareId);
				var sharee = this.getShareeObject(shareWithIndex);
				$.extend(sharee, this.getShareProperties());
				var $li = this.$('li[data-share-id=' + permissionChangeShareId + ']');
				$li.find('.sharingOptionsGroup .popovermenu').replaceWith(this.popoverMenuTemplate(sharee));
			}

			var _this = this;
			this.getShareeList().forEach(function(sharee) {
				var $edit = _this.$('#canEdit-' + _this.cid + '-' + sharee.shareId);
				if($edit.length === 1) {
					$edit.prop('checked', sharee.editPermissionState === 'checked');
					if (sharee.isFolder) {
						$edit.prop('indeterminate', sharee.editPermissionState === 'indeterminate');
					}
				}
			});
			this.$('.popovermenu').on('afterHide', function() {
				_this._menuOpen = false;
			});
			this.$('.popovermenu').on('beforeHide', function() {
				var shareId = parseInt(_this._menuOpen, 10);
				if(!_.isNaN(shareId)) {
					var datePickerClass = '.expirationDateContainer-' + _this.cid + '-' + shareId;
					var datePickerInput = '#expirationDatePicker-' + _this.cid + '-' + shareId;
					var expireDateCheckbox = '#expireDate-' + _this.cid + '-' + shareId;
					if ($(expireDateCheckbox).prop('checked')) {
						$(datePickerInput).removeClass('hidden-visually');
						$(datePickerClass).removeClass('hasDatepicker');
						$(datePickerClass + ' .ui-datepicker').hide();
					}
				}
			});
			if (this._menuOpen !== false) {
				// Open menu again if it was opened before
				var shareId = parseInt(this._menuOpen, 10);
				if(!_.isNaN(shareId)) {
					var liSelector = 'li[data-share-id=' + shareId + ']';
					OC.showMenu(null, this.$(liSelector + ' .sharingOptionsGroup .popovermenu'));
				}
			}

			this._renderPermissionChange = false;

			// new note autosize
			autosize(this.$el.find('.share-note-form .share-note'));

			this.delegateEvents();

			return this;
		},

		/**
		 * @returns {Function} from Handlebars
		 * @private
		 */
		template: function (data) {
			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}
			var sharees = data.sharees;
			if(_.isArray(sharees)) {
				for (var i = 0; i < sharees.length; i++) {
					data.sharees[i].popoverMenu = this.popoverMenuTemplate(sharees[i]);
				}
			}
			return this._template(data);
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

		showNoteForm: function(event) {
			event.preventDefault();
			event.stopPropagation();
			var $element = $(event.target);
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

			console.log($form.find('.share-note'));
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

			self.model.removeShare(shareId)
				.done(function() {
					$li.remove();
				})
				.fail(function() {
					$loading.addClass('hidden');
					OC.Notification.showTemporary(t('core', 'Could not unshare'));
				});
			return false;
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

		onExpireDateChange: function(event) {
			var $element = $(event.target);
			var li = $element.closest('li[data-share-id]');
			var shareId = li.data('share-id');
			var datePickerClass = '.expirationDateContainer-' + this.cid + '-' + shareId;
			var datePicker = $(datePickerClass);
			var state = $element.prop('checked');
			datePicker.toggleClass('hidden', !state);
			if (!state) {
				// disabled, let's hide the input and
				// set the expireDate to nothing
				$element.closest('li').next('li').addClass('hidden');
				this.setExpirationDate(shareId, '');
			} else {
				// enabled, show the input and the datepicker
				$element.closest('li').next('li').removeClass('hidden');
				this.showDatePicker(event);

			}
		},

		showDatePicker: function(event) {
			var element = $(event.target);
			var li = element.closest('li[data-share-id]');
			var shareId = li.data('share-id');
			var expirationDatePicker = '#expirationDatePicker-' + this.cid + '-' + shareId;
			var view = this;
			$(expirationDatePicker).datepicker({
				dateFormat : 'dd-mm-yy',
				onSelect: function (expireDate) {
					view.setExpirationDate(shareId, expireDate);
				}
			});
			$(expirationDatePicker).focus();

		},

		setExpirationDate: function(shareId, expireDate) {
			this.model.updateShare(shareId, {expireDate: expireDate}, {});
		},

		onMailSharePasswordProtectChange: function(event) {
			var element = $(event.target);
			var li = element.closest('li[data-share-id]');
			var shareId = li.data('share-id');
			var passwordContainerClass = '.passwordMenu-' + this.cid + '-' + shareId;
			var passwordContainer = $(passwordContainerClass);
			var loading = this.$el.find(passwordContainerClass + ' .icon-loading-small');
			var inputClass = '#passwordField-' + this.cid + '-' + shareId;
			var passwordField = $(inputClass);
			var state = element.prop('checked');
			var passwordByTalkElement = $('#passwordByTalk-' + this.cid + '-' + shareId);
			var passwordByTalkState = passwordByTalkElement.prop('checked');
			if (!state && !passwordByTalkState) {
				this.model.updateShare(shareId, {password: '', sendPasswordByTalk: false});
				passwordField.attr('value', '');
				passwordField.removeClass('error');
				passwordField.tooltip('hide');
				loading.addClass('hidden');
				passwordField.attr('placeholder', PASSWORD_PLACEHOLDER_MESSAGE);
				// We first need to reset the password field before we hide it
				passwordContainer.toggleClass('hidden', !state);
			} else if (state) {
				if (passwordByTalkState) {
					// Switching from sending the password by Talk to sending
					// the password by mail can be done keeping the previous
					// password sent by Talk.
					this.model.updateShare(shareId, {sendPasswordByTalk: false});

					var passwordByTalkContainerClass = '.passwordByTalkMenu-' + this.cid + '-' + shareId;
					var passwordByTalkContainer = $(passwordByTalkContainerClass);
					passwordByTalkContainer.addClass('hidden');
					passwordByTalkElement.prop('checked', false);
				}

				passwordContainer.toggleClass('hidden', !state);
				passwordField = '#passwordField-' + this.cid + '-' + shareId;
				this.$(passwordField).focus();
			}
		},

		onMailSharePasswordProtectByTalkChange: function(event) {
			var element = $(event.target);
			var li = element.closest('li[data-share-id]');
			var shareId = li.data('share-id');
			var passwordByTalkContainerClass = '.passwordByTalkMenu-' + this.cid + '-' + shareId;
			var passwordByTalkContainer = $(passwordByTalkContainerClass);
			var loading = this.$el.find(passwordByTalkContainerClass + ' .icon-loading-small');
			var inputClass = '#passwordByTalkField-' + this.cid + '-' + shareId;
			var passwordByTalkField = $(inputClass);
			var state = element.prop('checked');
			var passwordElement = $('#password-' + this.cid + '-' + shareId);
			var passwordState = passwordElement.prop('checked');
			if (!state) {
				this.model.updateShare(shareId, {password: '', sendPasswordByTalk: false});
				passwordByTalkField.attr('value', '');
				passwordByTalkField.removeClass('error');
				passwordByTalkField.tooltip('hide');
				loading.addClass('hidden');
				passwordByTalkField.attr('placeholder', PASSWORD_PLACEHOLDER_MESSAGE);
				// We first need to reset the password field before we hide it
				passwordByTalkContainer.toggleClass('hidden', !state);
			} else if (state) {
				if (passwordState) {
					// Enabling sending the password by Talk requires a new
					// password to be given (the one sent by mail is not reused,
					// as it would defeat the purpose of checking the identity
					// of the sharee by Talk if it was already sent by mail), so
					// the share is not updated until the user explicitly gives
					// the new password.

					var passwordContainerClass = '.passwordMenu-' + this.cid + '-' + shareId;
					var passwordContainer = $(passwordContainerClass);
					passwordContainer.addClass('hidden');
					passwordElement.prop('checked', false);
				}

				passwordByTalkContainer.toggleClass('hidden', !state);
				passwordByTalkField = '#passwordByTalkField-' + this.cid + '-' + shareId;
				this.$(passwordByTalkField).focus();
			}
		},

		onMailSharePasswordKeyUp: function(event) {
			if(event.keyCode === 13) {
				this.onMailSharePasswordEntered(event);
			}
		},

		onMailSharePasswordEntered: function(event) {
			var passwordField = $(event.target);
			var li = passwordField.closest('li[data-share-id]');
			var shareId = li.data('share-id');
			var passwordContainerClass = '.passwordMenu-' + this.cid + '-' + shareId;
			var passwordByTalkContainerClass = '.passwordByTalkMenu-' + this.cid + '-' + shareId;
			var sendPasswordByTalk = passwordField.attr('id').startsWith('passwordByTalk');
			var loading;
			if (sendPasswordByTalk) {
				loading = this.$el.find(passwordByTalkContainerClass + ' .icon-loading-small');
			} else {
				loading = this.$el.find(passwordContainerClass + ' .icon-loading-small');
			}
			if (!loading.hasClass('hidden')) {
				// still in process
				return;
			}

			passwordField.removeClass('error');
			var password = passwordField.val();
			// in IE9 the password might be the placeholder due to bugs in the placeholders polyfill
			if(password === '' || password === PASSWORD_PLACEHOLDER || password === PASSWORD_PLACEHOLDER_MESSAGE) {
				return;
			}

			loading
				.removeClass('hidden')
				.addClass('inlineblock');


			this.model.updateShare(shareId, {
				password: password,
				sendPasswordByTalk: sendPasswordByTalk
			}, {
				error: function(model, msg) {
					// destroy old tooltips
					passwordField.tooltip('destroy');
					loading.removeClass('inlineblock').addClass('hidden');
					passwordField.addClass('error');
					passwordField.attr('title', msg);
					passwordField.tooltip({placement: 'bottom', trigger: 'manual'});
					passwordField.tooltip('show');
				},
				success: function(model, msg) {
					passwordField.blur();
					passwordField.attr('value', '');
					passwordField.attr('placeholder', PASSWORD_PLACEHOLDER);
					loading.removeClass('inlineblock').addClass('hidden');
				}
			});
		},

		onPermissionChange: function(event) {
			event.preventDefault();
			event.stopPropagation();
			var $element = $(event.target);
			var $li = $element.closest('li[data-share-id]');
			var shareId = $li.data('share-id');

			var permissions = OC.PERMISSION_READ;

			if (this.model.isFolder()) {
				// adjust checkbox states
				var $checkboxes = $('.permissions', $li).not('input[name="edit"]').not('input[name="share"]');
				var checked;
				if ($element.attr('name') === 'edit') {
					checked = $element.is(':checked');
					// Check/uncheck Create, Update, and Delete checkboxes if Edit is checked/unck
					$($checkboxes).prop('checked', checked);
					if (checked) {
						permissions |= OC.PERMISSION_CREATE | OC.PERMISSION_UPDATE | OC.PERMISSION_DELETE;
					}
				} else {
					var numberChecked = $checkboxes.filter(':checked').length;
					checked = numberChecked === $checkboxes.length;
					var $editCb = $('input[name="edit"]', $li);
					$editCb.prop('checked', checked);
					$editCb.prop('indeterminate', !checked && numberChecked > 0);
				}
			} else {
				if ($element.attr('name') === 'edit' && $element.is(':checked')) {
					permissions |= OC.PERMISSION_UPDATE;
				}
			}

			$('.permissions', $li).not('input[name="edit"]').filter(':checked').each(function(index, checkbox) {
				permissions |= $(checkbox).data('permissions');
			});


			/** disable checkboxes during save operation to avoid race conditions **/
			$li.find('input[type=checkbox]').prop('disabled', true);
			var enableCb = function() {
				$li.find('input[type=checkbox]').prop('disabled', false);
			};
			var errorCb = function(elem, msg) {
				OC.dialogs.alert(msg, t('core', 'Error while sharing'));
				enableCb();
			};

			this.model.updateShare(shareId, {permissions: permissions}, {error: errorCb, success: enableCb});

			this._renderPermissionChange = shareId;
		},

		onSecureDropChange: function(event) {
			event.preventDefault();
			event.stopPropagation();
			var $element = $(event.target);
			var $li = $element.closest('li[data-share-id]');
			var shareId = $li.data('share-id');

			var permissions = OC.PERMISSION_CREATE | OC.PERMISSION_UPDATE | OC.PERMISSION_DELETE | OC.PERMISSION_READ;
			if ($element.is(':checked')) {
				permissions = OC.PERMISSION_CREATE | OC.PERMISSION_UPDATE | OC.PERMISSION_DELETE;
			}

			/** disable checkboxes during save operation to avoid race conditions **/
			$li.find('input[type=checkbox]').prop('disabled', true);
			var enableCb = function() {
				$li.find('input[type=checkbox]').prop('disabled', false);
			};
			var errorCb = function(elem, msg) {
				OC.dialogs.alert(msg, t('core', 'Error while sharing'));
				enableCb();
			};

			this.model.updateShare(shareId, {permissions: permissions}, {error: errorCb, success: enableCb});

			this._renderPermissionChange = shareId;
		}

	});

	OC.Share.ShareDialogShareeListView = ShareDialogShareeListView;

})();
