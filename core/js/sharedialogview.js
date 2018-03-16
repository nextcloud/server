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
	if(!OC.Share) {
		OC.Share = {};
	}

	var TEMPLATE_BASE =
		'<div class="resharerInfoView subView"></div>' +
		'{{#if isSharingAllowed}}' +
		'<label for="shareWith-{{cid}}" class="hidden-visually">{{shareLabel}}</label>' +
		'<div class="oneline">' +
		'    <input id="shareWith-{{cid}}" class="shareWithField" type="text" placeholder="{{sharePlaceholder}}" />' +
		'    <span class="shareWithLoading icon-loading-small hidden"></span>'+
		'{{{shareInfo}}}' +
		'</div>' +
		'{{/if}}' +
		'<div class="shareeListView subView"></div>' +
		'<div class="linkShareView subView"></div>' +
		'<div class="expirationView subView"></div>' +
		'<div class="loading hidden" style="height: 50px"></div>';

	var TEMPLATE_SHARE_INFO =
		'<span class="icon icon-info shareWithRemoteInfo hasTooltip" ' +
		'title="{{tooltip}}"></span>';

	/**
	 * @class OCA.Share.ShareDialogView
	 * @member {OC.Share.ShareItemModel} model
	 * @member {jQuery} $el
	 * @memberof OCA.Sharing
	 * @classdesc
	 *
	 * Represents the GUI of the share dialogue
	 *
	 */
	var ShareDialogView = OC.Backbone.View.extend({
		/** @type {Object} **/
		_templates: {},

		/** @type {boolean} **/
		_showLink: true,

		/** @type {string} **/
		tagName: 'div',

		/** @type {OC.Share.ShareConfigModel} **/
		configModel: undefined,

		/** @type {object} **/
		resharerInfoView: undefined,

		/** @type {object} **/
		linkShareView: undefined,

		/** @type {object} **/
		expirationView: undefined,

		/** @type {object} **/
		shareeListView: undefined,

		events: {
			'focus .shareWithField': 'onShareWithFieldFocus',
			'input .shareWithField': 'onShareWithFieldChanged'
		},

		initialize: function(options) {
			var view = this;

			this.model.on('fetchError', function() {
				OC.Notification.showTemporary(t('core', 'Share details could not be loaded for this item.'));
			});

			if(!_.isUndefined(options.configModel)) {
				this.configModel = options.configModel;
			} else {
				throw 'missing OC.Share.ShareConfigModel';
			}

			this.configModel.on('change:isRemoteShareAllowed', function() {
				view.render();
			});
			this.model.on('change:permissions', function() {
				view.render();
			});

			this.model.on('request', this._onRequest, this);
			this.model.on('sync', this._onEndRequest, this);

			var subViewOptions = {
				model: this.model,
				configModel: this.configModel
			};

			var subViews = {
				resharerInfoView: 'ShareDialogResharerInfoView',
				linkShareView: 'ShareDialogLinkShareView',
				expirationView: 'ShareDialogExpirationView',
				shareeListView: 'ShareDialogShareeListView'
			};

			for(var name in subViews) {
				var className = subViews[name];
				this[name] = _.isUndefined(options[name])
					? new OC.Share[className](subViewOptions)
					: options[name];
			}

			_.bindAll(this,
				'autocompleteHandler',
				'_onSelectRecipient',
				'onShareWithFieldChanged',
				'onShareWithFieldFocus'
			);

			OC.Plugins.attach('OC.Share.ShareDialogView', this);
		},

		onShareWithFieldChanged: function() {
			var $el = this.$el.find('.shareWithField');
			if ($el.val().length < 2) {
				$el.removeClass('error').tooltip('hide');
			}
		},

		/* trigger search after the field was re-selected */
		onShareWithFieldFocus: function() {
			this.$el.find('.shareWithField').autocomplete("search");
		},

		autocompleteHandler: function (search, response) {
			var $shareWithField = $('.shareWithField'),
				view = this,
				$loading = this.$el.find('.shareWithLoading'),
				$shareInfo = this.$el.find('.shareWithRemoteInfo');

			var count = oc_config['sharing.minSearchStringLength'];
			if (search.term.trim().length < count) {
				var title = n('core',
					'At least {count} character is needed for autocompletion',
					'At least {count} characters are needed for autocompletion',
					count,
					{ count: count }
				);
				$shareWithField.addClass('error')
					.attr('data-original-title', title)
					.tooltip('hide')
					.tooltip({
						placement: 'bottom',
						trigger: 'manual'
					})
					.tooltip('fixTitle')
					.tooltip('show');
				response();
				return;
			}

			$loading.removeClass('hidden');
			$loading.addClass('inlineblock');
			$shareInfo.addClass('hidden');

			$shareWithField.removeClass('error')
				.tooltip('hide');

			var perPage = 200;
			$.get(
				OC.linkToOCS('apps/files_sharing/api/v1') + 'sharees',
				{
					format: 'json',
					search: search.term.trim(),
					perPage: perPage,
					itemType: view.model.get('itemType')
				},
				function (result) {
					$loading.addClass('hidden');
					$loading.removeClass('inlineblock');
					$shareInfo.removeClass('hidden');
					if (result.ocs.meta.statuscode === 100) {
						var users   = result.ocs.data.exact.users.concat(result.ocs.data.users);
						var groups  = result.ocs.data.exact.groups.concat(result.ocs.data.groups);
						var remotes = result.ocs.data.exact.remotes.concat(result.ocs.data.remotes);
						var lookup = result.ocs.data.lookup;
						var emails = [],
							circles = [];
						if (typeof(result.ocs.data.emails) !== 'undefined') {
							emails = result.ocs.data.exact.emails.concat(result.ocs.data.emails);
						}
						if (typeof(result.ocs.data.circles) !== 'undefined') {
							circles = result.ocs.data.exact.circles.concat(result.ocs.data.circles);
						}

						var usersLength;
						var groupsLength;
						var remotesLength;
						var emailsLength;
						var circlesLength;

						var i, j;

						//Filter out the current user
						usersLength = users.length;
						for (i = 0; i < usersLength; i++) {
							if (users[i].value.shareWith === OC.currentUser) {
								users.splice(i, 1);
								break;
							}
						}

						// Filter out the owner of the share
						if (view.model.hasReshare()) {
							usersLength = users.length;
							for (i = 0 ; i < usersLength; i++) {
								if (users[i].value.shareWith === view.model.getReshareOwner()) {
									users.splice(i, 1);
									break;
								}
							}
						}

						var shares = view.model.get('shares');
						var sharesLength = shares.length;

						// Now filter out all sharees that are already shared with
						for (i = 0; i < sharesLength; i++) {
							var share = shares[i];

							if (share.share_type === OC.Share.SHARE_TYPE_USER) {
								usersLength = users.length;
								for (j = 0; j < usersLength; j++) {
									if (users[j].value.shareWith === share.share_with) {
										users.splice(j, 1);
										break;
									}
								}
							} else if (share.share_type === OC.Share.SHARE_TYPE_GROUP) {
								groupsLength = groups.length;
								for (j = 0; j < groupsLength; j++) {
									if (groups[j].value.shareWith === share.share_with) {
										groups.splice(j, 1);
										break;
									}
								}
							} else if (share.share_type === OC.Share.SHARE_TYPE_REMOTE) {
								remotesLength = remotes.length;
								for (j = 0; j < remotesLength; j++) {
									if (remotes[j].value.shareWith === share.share_with) {
										remotes.splice(j, 1);
										break;
									}
								}
							} else if (share.share_type === OC.Share.SHARE_TYPE_EMAIL) {
								emailsLength = emails.length;
								for (j = 0; j < emailsLength; j++) {
									if (emails[j].value.shareWith === share.share_with) {
										emails.splice(j, 1);
										break;
									}
								}
							} else if (share.share_type === OC.Share.SHARE_TYPE_CIRCLE) {
								circlesLength = circles.length;
								for (j = 0; j < circlesLength; j++) {
									if (circles[j].value.shareWith === share.share_with) {
										circles.splice(j, 1);
										break;
									}
								}
							}
						}

						var suggestions = users.concat(groups).concat(remotes).concat(emails).concat(circles).concat(lookup);

						if (suggestions.length > 0) {
							$shareWithField
								.autocomplete("option", "autoFocus", true);

							response(suggestions);

							// show a notice that the list is truncated
							// this is the case if one of the search results is at least as long as the max result config option
							if(oc_config['sharing.maxAutocompleteResults'] > 0 &&
								Math.min(perPage, oc_config['sharing.maxAutocompleteResults'])
								<= Math.max(users.length, groups.length, remotes.length, emails.length, lookup.length)) {

								var message = t('core', 'This list is maybe truncated - please refine your search term to see more results.');
								$('.ui-autocomplete').append('<li class="autocomplete-note">' + message + '</li>');
							}

						} else {
							var title = t('core', 'No users or groups found for {search}', {search: $shareWithField.val()});
							if (!view.configModel.get('allowGroupSharing')) {
								title = t('core', 'No users found for {search}', {search: $('.shareWithField').val()});
							}
							$shareWithField.addClass('error')
								.attr('data-original-title', title)
								.tooltip('hide')
								.tooltip({
									placement: 'bottom',
									trigger: 'manual'
								})
								.tooltip('fixTitle')
								.tooltip('show');
							response();
						}
					} else {
						response();
					}
				}
			).fail(function() {
				$loading.addClass('hidden');
				$loading.removeClass('inlineblock');
				$shareInfo.removeClass('hidden');
				OC.Notification.show(t('core', 'An error occurred. Please try again'));
				window.setTimeout(OC.Notification.hide, 5000);
			});
		},

		autocompleteRenderItem: function(ul, item) {

			var text = item.label;
			if (item.value.shareType === OC.Share.SHARE_TYPE_GROUP) {
				text = t('core', '{sharee} (group)', { sharee: text }, undefined, { escape: false });
			} else if (item.value.shareType === OC.Share.SHARE_TYPE_REMOTE) {
				text = t('core', '{sharee} (remote)', { sharee: text }, undefined, { escape: false });
			} else if (item.value.shareType === OC.Share.SHARE_TYPE_EMAIL) {
				text = t('core', '{sharee} (email)', { sharee: text }, undefined, { escape: false });
			} else if (item.value.shareType === OC.Share.SHARE_TYPE_CIRCLE) {
				text = t('core', '{sharee} ({type}, {owner})', {sharee: text, type: item.value.circleInfo, owner: item.value.circleOwner}, undefined, {escape: false});
			}
			var insert = $("<div class='share-autocomplete-item'/>");
			var avatar = $("<div class='avatardiv'></div>").appendTo(insert);
			if (item.value.shareType === OC.Share.SHARE_TYPE_USER || item.value.shareType === OC.Share.SHARE_TYPE_CIRCLE) {
				avatar.avatar(item.value.shareWith, 32, undefined, undefined, undefined, item.label);
			} else {
				avatar.imageplaceholder(text, undefined, 32);
			}

			$("<div class='autocomplete-item-text'></div>")
				.text(text)
				.appendTo(insert);
			insert.attr('title', item.value.shareWith);
			insert = $("<a>")
				.append(insert);
			return $("<li>")
				.addClass((item.value.shareType === OC.Share.SHARE_TYPE_GROUP) ? 'group' : 'user')
				.append(insert)
				.appendTo(ul);
		},

		_onSelectRecipient: function(e, s) {
			e.preventDefault();
			$(e.target).attr('disabled', true)
				.val(s.item.label);
			var $loading = this.$el.find('.shareWithLoading');
			$loading.removeClass('hidden')
				.addClass('inlineblock');
			var $shareInfo = this.$el.find('.shareWithRemoteInfo');
			$shareInfo.addClass('hidden');

			this.model.addShare(s.item.value, {success: function() {
				$(e.target).val('')
					.attr('disabled', false);
				$loading.addClass('hidden')
					.removeClass('inlineblock');
				$shareInfo.removeClass('hidden');
			}, error: function(obj, msg) {
				OC.Notification.showTemporary(msg);
				$(e.target).attr('disabled', false)
					.autocomplete('search', $(e.target).val());
				$loading.addClass('hidden')
					.removeClass('inlineblock');
				$shareInfo.removeClass('hidden');
			}});
		},

		_toggleLoading: function(state) {
			this._loading = state;
			this.$el.find('.subView').toggleClass('hidden', state);
			this.$el.find('.loading').toggleClass('hidden', !state);
		},

		_onRequest: function() {
			// only show the loading spinner for the first request (for now)
			if (!this._loadingOnce) {
				this._toggleLoading(true);
			}
		},

		_onEndRequest: function() {
			var self = this;
			this._toggleLoading(false);
			if (!this._loadingOnce) {
				this._loadingOnce = true;
				// the first time, focus on the share field after the spinner disappeared
				if (!OC.Util.isIE()) {
					_.defer(function () {
						self.$('.shareWithField').focus();
					});
				}
			}
		},

		render: function() {
			var baseTemplate = this._getTemplate('base', TEMPLATE_BASE);

			this.$el.html(baseTemplate({
				cid: this.cid,
				shareLabel: t('core', 'Share'),
				sharePlaceholder: this._renderSharePlaceholderPart(),
				shareInfo: this._renderShareInfoPart(),
				isSharingAllowed: this.model.sharePermissionPossible()
			}));

			var $shareField = this.$el.find('.shareWithField');
			if ($shareField.length) {
				$shareField.autocomplete({
					minLength: 1,
					delay: 750,
					focus: function(event) {
						event.preventDefault();
					},
					source: this.autocompleteHandler,
					select: this._onSelectRecipient
				}).data('ui-autocomplete')._renderItem = this.autocompleteRenderItem;
			}

			this.resharerInfoView.$el = this.$el.find('.resharerInfoView');
			this.resharerInfoView.render();

			this.linkShareView.$el = this.$el.find('.linkShareView');
			this.linkShareView.render();

			this.expirationView.$el = this.$el.find('.expirationView');
			this.expirationView.render();

			this.shareeListView.$el = this.$el.find('.shareeListView');
			this.shareeListView.render();

			this.$el.find('.hasTooltip').tooltip();

			return this;
		},

		/**
		 * sets whether share by link should be displayed or not. Default is
		 * true.
		 *
		 * @param {bool} showLink
		 */
		setShowLink: function(showLink) {
			this._showLink = (typeof showLink === 'boolean') ? showLink : true;
			this.linkShareView.showLink = this._showLink;
		},

		_renderShareInfoPart: function() {
			var shareInfo = '';
			var infoTemplate = this._getShareInfoTemplate();

			if(this.configModel.get('isMailShareAllowed') && this.configModel.get('isRemoteShareAllowed')) {
				shareInfo = infoTemplate({
					tooltip: t('core', 'Share with other people by entering a user or group, a federated cloud ID or an email address.')
				});
			} else if(this.configModel.get('isRemoteShareAllowed')) {
				shareInfo = infoTemplate({
					tooltip: t('core', 'Share with other people by entering a user or group or a federated cloud ID.')
				});
			} else if(this.configModel.get('isMailShareAllowed')) {
				shareInfo = infoTemplate({
					tooltip: t('core', 'Share with other people by entering a user or group or an email address.')
				});
			}

			return shareInfo;
		},

		_renderSharePlaceholderPart: function () {
			var allowRemoteSharing = this.configModel.get('isRemoteShareAllowed');
			var allowMailSharing = this.configModel.get('isMailShareAllowed');

			if (!allowRemoteSharing && allowMailSharing) {
				return t('core', 'Name or email address...');
			}
			if (allowRemoteSharing && !allowMailSharing) {
				return t('core', 'Name or federated cloud ID...');
			}
			if (allowRemoteSharing && allowMailSharing) {
				return t('core', 'Name, federated cloud ID or email address...');
			}

			return 	t('core', 'Name...');
		},

		/**
		 *
		 * @param {string} key - an identifier for the template
		 * @param {string} template - the HTML to be compiled by Handlebars
		 * @returns {Function} from Handlebars
		 * @private
		 */
		_getTemplate: function (key, template) {
			if (!this._templates[key]) {
				this._templates[key] = Handlebars.compile(template);
			}
			return this._templates[key];
		},

		/**
		 * returns the info template for remote sharing
		 *
		 * @returns {Function}
		 * @private
		 */
		_getShareInfoTemplate: function() {
			return this._getTemplate('shareInfo', TEMPLATE_SHARE_INFO);
		}
	});

	OC.Share.ShareDialogView = ShareDialogView;

})();
