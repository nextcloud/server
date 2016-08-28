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
	if (!OC.Share) {
		OC.Share = {};
	}

	var TEMPLATE =
			'<ul id="shareWithList" class="shareWithList">' +
			'{{#each sharees}}' +
				'<li data-share-id="{{shareId}}" data-share-type="{{shareType}}" data-share-with="{{shareWith}}">' +
					'{{#if avatarEnabled}}' +
					'<div class="avatar {{#if modSeed}}imageplaceholderseed{{/if}}" data-username="{{shareWith}}" {{#if modSeed}}data-seed="{{shareWith}} {{shareType}}"{{/if}}></div>' +
					'{{/if}}' +
					'<span class="has-tooltip username" title="{{shareWith}}">{{shareWithDisplayName}}</span>' +
					'{{#if mailNotificationEnabled}}  {{#unless isRemoteShare}}' +
					'<span class="shareOption">' +
						'<input id="mail-{{cid}}-{{shareWith}}" type="checkbox" name="mailNotification" class="mailNotification checkbox" {{#if wasMailSent}}checked="checked"{{/if}} />' +
						'<label for="mail-{{cid}}-{{shareWith}}">{{notifyByMailLabel}}</label>' +
					'</span>' +
					'{{/unless}} {{/if}}' +
					'<span class="sharingOptionsGroup">' +
						'{{#if editPermissionPossible}}' +
						'<span class="shareOption">' +
							'<input id="canEdit-{{cid}}-{{shareWith}}" type="checkbox" name="edit" class="permissions checkbox" {{#if hasEditPermission}}checked="checked"{{/if}} />' +
							'<label for="canEdit-{{cid}}-{{shareWith}}">{{canEditLabel}}</label>' +
						'</span>' +
						'{{/if}}' +
						'<a href="#"><span class="icon icon-more"></span></a>' +
						'<div class="popovermenu bubble hidden menu">' +
							'<ul>' +
								'{{#if isResharingAllowed}} {{#if sharePermissionPossible}}' +
								'<li>' +
									'<span class="shareOption">' +
										'<input id="canShare-{{cid}}-{{shareWith}}" type="checkbox" name="share" class="permissions checkbox" {{#if hasSharePermission}}checked="checked"{{/if}} data-permissions="{{sharePermission}}" />' +
										'<label for="canShare-{{cid}}-{{shareWith}}">{{canShareLabel}}</label>' +
									'</span>' +
								'</li>' +
								'{{/if}} {{/if}}' +
								'{{#if isFolder}}' +
									'{{#if createPermissionPossible}}' +
									'<li>' +
										'<span class="shareOption">' +
											'<input id="canCreate-{{cid}}-{{shareWith}}" type="checkbox" name="create" class="permissions checkbox" {{#if hasCreatePermission}}checked="checked"{{/if}} data-permissions="{{createPermission}}"/>' +
											'<label for="canCreate-{{cid}}-{{shareWith}}">{{createPermissionLabel}}</label>' +
										'</span>' +
									'</li>' +
									'{{/if}}' +
									'{{#if updatePermissionPossible}}' +
									'<li>' +
										'<span class="shareOption">' +
											'<input id="canUpdate-{{cid}}-{{shareWith}}" type="checkbox" name="update" class="permissions checkbox" {{#if hasUpdatePermission}}checked="checked"{{/if}} data-permissions="{{updatePermission}}"/>' +
											'<label for="canUpdate-{{cid}}-{{shareWith}}">{{updatePermissionLabel}}</label>' +
										'</span>' +
									'</li>' +
									'{{/if}}' +
									'{{#if deletePermissionPossible}}' +
									'<li>' +
										'<span class="shareOption">' +
											'<input id="canDelete-{{cid}}-{{shareWith}}" type="checkbox" name="delete" class="permissions checkbox" {{#if hasDeletePermission}}checked="checked"{{/if}} data-permissions="{{deletePermission}}"/>' +
											'<label for="canDelete-{{cid}}-{{shareWith}}">{{deletePermissionLabel}}</label>' +
										'</span>' +
									'</li>' +
									'{{/if}}' +
								'{{/if}}' +
							'</ul>' +
						'</div>' +
						'<a href="#" class="unshare"><span class="icon-loading-small hidden"></span><span class="icon icon-delete"></span><span class="hidden-visually">{{unshareLabel}}</span></a>' +
					'</span>' +
				'</li>' +
			'{{/each}}' +
			'{{#each linkReshares}}' +
				'<li data-share-id="{{shareId}}" data-share-type="{{shareType}}">' +
					'<span class="icon icon-public"></span>' +
					'<span class="has-tooltip username" title="{{shareInitiator}}">{{shareInitiatorDisplayName}}</span>' +
					'<a href="#" class="unshare"><span class="icon-loading-small hidden"></span><span class="icon icon-delete"></span><span class="hidden-visually">{{unshareLabel}}</span></a>' +
				'</li>' +
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

		_menuOpen: false,

		events: {
			'click .unshare': 'onUnshare',
			'click .icon-more': 'onToggleMenu',
			'click .permissions': 'onPermissionChange',
			'click .mailNotification': 'onSendMailNotification'
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
			var shareType = this.model.getShareType(shareIndex);

			var hasPermissionOverride = {};
			if (shareType === OC.Share.SHARE_TYPE_GROUP) {
				shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'group') + ')';
			} else if (shareType === OC.Share.SHARE_TYPE_REMOTE) {
				shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'remote') + ')';
			}

			return _.extend(hasPermissionOverride, {
				cid: this.cid,
				hasSharePermission: this.model.hasSharePermission(shareIndex),
				hasEditPermission: this.model.hasEditPermission(shareIndex),
				hasCreatePermission: this.model.hasCreatePermission(shareIndex),
				hasUpdatePermission: this.model.hasUpdatePermission(shareIndex),
				hasDeletePermission: this.model.hasDeletePermission(shareIndex),
				wasMailSent: this.model.notificationMailWasSent(shareIndex),
				shareWith: shareWith,
				shareWithDisplayName: shareWithDisplayName,
				shareType: shareType,
				shareId: this.model.get('shares')[shareIndex].id,
				modSeed: shareType !== OC.Share.SHARE_TYPE_USER,
				isRemoteShare: shareType === OC.Share.SHARE_TYPE_REMOTE
			});
		},

		getShareeList: function() {
			var universal = {
				avatarEnabled: this.configModel.areAvatarsEnabled(),
				mailNotificationEnabled: this.configModel.isMailNotificationEnabled(),
				notifyByMailLabel: t('core', 'notify by email'),
				unshareLabel: t('core', 'Unshare'),
				canShareLabel: t('core', 'can reshare'),
				canEditLabel: t('core', 'can edit'),
				createPermissionLabel: t('core', 'create'),
				updatePermissionLabel: t('core', 'change'),
				deletePermissionLabel: t('core', 'delete'),
				crudsLabel: t('core', 'access control'),
				triangleSImage: OC.imagePath('core', 'actions/triangle-s'),
				isResharingAllowed: this.configModel.get('isResharingAllowed'),
				sharePermissionPossible: this.model.sharePermissionPossible(),
				editPermissionPossible: this.model.editPermissionPossible(),
				createPermissionPossible: this.model.createPermissionPossible(),
				updatePermissionPossible: this.model.updatePermissionPossible(),
				deletePermissionPossible: this.model.deletePermissionPossible(),
				sharePermission: OC.PERMISSION_SHARE,
				createPermission: OC.PERMISSION_CREATE,
				updatePermission: OC.PERMISSION_UPDATE,
				deletePermission: OC.PERMISSION_DELETE,
				isFolder: this.model.isFolder()
			};

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
			this.$el.html(this.template({
				cid: this.cid,
				sharees: this.getShareeList(),
				linkReshares: this.getLinkReshares()
			}));

			if(this.configModel.areAvatarsEnabled()) {
				this.$('.avatar').each(function() {
					var $this = $(this);
					if ($this.hasClass('imageplaceholderseed')) {
						$this.css({width: 32, height: 32});
						$this.imageplaceholder($this.data('seed'));
					} else {
						$this.avatar($this.data('username'), 32);
					}
				});
			}

			this.$('.has-tooltip').tooltip({
				placement: 'bottom'
			});

			var _this = this;
			this.$('.popovermenu').on('afterHide', function() {
				_this._menuOpen = false;
			});
			if (this._menuOpen) {
				// Open menu again if it was opened before
				OC.showMenu(null, this.$('.popovermenu'));
			}

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
			return this._template(data);
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
			var $menu = $li.find('.popovermenu');

			OC.showMenu(null, $menu);
			this._menuOpen = true;
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
				} else {
					var numberChecked = $checkboxes.filter(':checked').length;
					checked = numberChecked > 0;
					$('input[name="edit"]', $li).prop('checked', checked);
				}
			} else {
				if ($element.attr('name') === 'edit' && $element.is(':checked')) {
					permissions |= OC.PERMISSION_UPDATE;
				}
			}

			$('.permissions', $li).not('input[name="edit"]').filter(':checked').each(function(index, checkbox) {
				permissions |= $(checkbox).data('permissions');
			});

			this.model.updateShare(shareId, {permissions: permissions});
		},

		onSendMailNotification: function(event) {
			var $target = $(event.target);
			var $li = $(event.target).closest('li[data-share-id]');
			var shareType = $li.data('share-type');
			var shareWith = $li.attr('data-share-with');

			this.model.sendNotificationForShare(shareType, shareWith, $target.is(':checked'));
		}
	});

	OC.Share.ShareDialogShareeListView = ShareDialogShareeListView;

})();
