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
					'<div class="avatar {{#if modSeed}}imageplaceholderseed{{/if}}" data-username="{{shareWith}}" data-displayname="{{shareWithDisplayName}}" {{#if modSeed}}data-seed="{{shareWith}} {{shareType}}"{{/if}}></div>' +
					'{{/if}}' +
					'<span class="has-tooltip username" title="{{shareWithTitle}}">{{shareWithDisplayName}}</span>' +
					'<span class="sharingOptionsGroup">' +
						'{{#if editPermissionPossible}}' +
						'{{#unless isFileSharedByMail}}' +
						'<span class="shareOption">' +
							'<input id="canEdit-{{cid}}-{{shareWith}}" type="checkbox" name="edit" class="permissions checkbox" {{#if hasEditPermission}}checked="checked"{{/if}} />' +
							'<label for="canEdit-{{cid}}-{{shareWith}}">{{canEditLabel}}</label>' +
						'</span>' +
						'{{/unless}}' +
						'{{/if}}' +
						'<a href="#"><span class="icon icon-more"></span></a>' +
						'<div class="popovermenu bubble hidden menu">' +
							'<ul>' +
								'{{#if isResharingAllowed}} {{#if sharePermissionPossible}} {{#unless isMailShare}}' +
								'<li>' +
									'<span class="shareOption">' +
										'<input id="canShare-{{cid}}-{{shareWith}}" type="checkbox" name="share" class="permissions checkbox" {{#if hasSharePermission}}checked="checked"{{/if}} data-permissions="{{sharePermission}}" />' +
										'<label for="canShare-{{cid}}-{{shareWith}}">{{canShareLabel}}</label>' +
									'</span>' +
								'</li>' +
								'{{/unless}} {{/if}} {{/if}}' +
								'{{#if isFolder}}' +
									'{{#if createPermissionPossible}}{{#unless isMailShare}}' +
									'<li>' +
										'<span class="shareOption">' +
											'<input id="canCreate-{{cid}}-{{shareWith}}" type="checkbox" name="create" class="permissions checkbox" {{#if hasCreatePermission}}checked="checked"{{/if}} data-permissions="{{createPermission}}"/>' +
											'<label for="canCreate-{{cid}}-{{shareWith}}">{{createPermissionLabel}}</label>' +
										'</span>' +
									'</li>' +
									'{{/unless}}{{/if}}' +
									'{{#if updatePermissionPossible}}{{#unless isMailShare}}' +
									'<li>' +
										'<span class="shareOption">' +
											'<input id="canUpdate-{{cid}}-{{shareWith}}" type="checkbox" name="update" class="permissions checkbox" {{#if hasUpdatePermission}}checked="checked"{{/if}} data-permissions="{{updatePermission}}"/>' +
											'<label for="canUpdate-{{cid}}-{{shareWith}}">{{updatePermissionLabel}}</label>' +
										'</span>' +
									'</li>' +
									'{{/unless}}{{/if}}' +
									'{{#if deletePermissionPossible}}{{#unless isMailShare}}' +
									'<li>' +
										'<span class="shareOption">' +
											'<input id="canDelete-{{cid}}-{{shareWith}}" type="checkbox" name="delete" class="permissions checkbox" {{#if hasDeletePermission}}checked="checked"{{/if}} data-permissions="{{deletePermission}}"/>' +
											'<label for="canDelete-{{cid}}-{{shareWith}}">{{deletePermissionLabel}}</label>' +
										'</span>' +
									'</li>' +
									'{{/unless}}{{/if}}' +
								'{{/if}}' +
								'<li>' +
									'<a href="#" class="unshare"><span class="icon-loading-small hidden"></span><span class="icon icon-delete"></span><span>{{unshareLabel}}</span></a>' +
								'</li>' +
							'</ul>' +
						'</div>' +
					'</span>' +
				'</li>' +
			'{{/each}}' +
			'{{#each linkReshares}}' +
				'<li data-share-id="{{shareId}}" data-share-type="{{shareType}}">' +
					'{{#if avatarEnabled}}' +
					'<div class="avatar" data-username="{{shareInitiator}}"></div>' +
					'{{/if}}' +
					'<span class="has-tooltip username" title="{{shareInitiator}}">' + t('core', '{{shareInitiatorDisplayName}} shared via link') + '</span>' +

					'<span class="sharingOptionsGroup">' +
						'<a href="#" class="unshare"><span class="icon-loading-small hidden"></span><span class="icon icon-delete"></span><span class="hidden-visually">{{unshareLabel}}</span></a>' +
					'</span>' +
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
			var shareWithTitle = '';
			var shareType = this.model.getShareType(shareIndex);

			var hasPermissionOverride = {};
			if (shareType === OC.Share.SHARE_TYPE_GROUP) {
				shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'group') + ')';
			} else if (shareType === OC.Share.SHARE_TYPE_REMOTE) {
				shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'remote') + ')';
			} else if (shareType === OC.Share.SHARE_TYPE_EMAIL) {
				shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'email') + ')';
			}

			if (shareType === OC.Share.SHARE_TYPE_GROUP) {
				shareWithTitle = shareWith + " (" + t('core', 'group') + ')';
			} else if (shareType === OC.Share.SHARE_TYPE_REMOTE) {
				shareWithTitle = shareWith + " (" + t('core', 'remote') + ')';
			} else if (shareType === OC.Share.SHARE_TYPE_EMAIL) {
				shareWithTitle = shareWith + " (" + t('core', 'email') + ')';
			}

			return _.extend(hasPermissionOverride, {
				cid: this.cid,
				hasSharePermission: this.model.hasSharePermission(shareIndex),
				hasEditPermission: this.model.hasEditPermission(shareIndex),
				hasCreatePermission: this.model.hasCreatePermission(shareIndex),
				hasUpdatePermission: this.model.hasUpdatePermission(shareIndex),
				hasDeletePermission: this.model.hasDeletePermission(shareIndex),
				shareWith: shareWith,
				shareWithDisplayName: shareWithDisplayName,
				shareWithTitle: shareWithTitle,
				shareType: shareType,
				shareId: this.model.get('shares')[shareIndex].id,
				modSeed: shareType !== OC.Share.SHARE_TYPE_USER,
				isRemoteShare: shareType === OC.Share.SHARE_TYPE_REMOTE,
				isMailShare: shareType === OC.Share.SHARE_TYPE_EMAIL,
				isFileSharedByMail: shareType === OC.Share.SHARE_TYPE_EMAIL && !this.model.isFolder()
			});
		},

		getShareeList: function() {
			var universal = {
				avatarEnabled: this.configModel.areAvatarsEnabled(),
				unshareLabel: t('core', 'Unshare'),
				canShareLabel: t('core', 'can reshare'),
				canEditLabel: t('core', 'can edit'),
				createPermissionLabel: t('core', 'can create'),
				updatePermissionLabel: t('core', 'can change'),
				deletePermissionLabel: t('core', 'can delete'),
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
				avatarEnabled: this.configModel.areAvatarsEnabled(),
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
						//                         user,   size,  ie8fix, hidedefault,  callback, displayname
						$this.avatar($this.data('username'), 32, undefined, undefined, undefined, $this.data('displayname'));
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
			if (this._menuOpen != false) {
				// Open menu again if it was opened before
				var shareId = parseInt(this._menuOpen, 10);
				if(!_.isNaN(shareId)) {
					var liSelector = 'li[data-share-id=' + shareId + ']';
					OC.showMenu(null, this.$(liSelector + ' .popovermenu'));
				}
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
			this._menuOpen = $li.data('share-id');
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
	});

	OC.Share.ShareDialogShareeListView = ShareDialogShareeListView;

})();
