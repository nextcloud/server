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

		_lookup: false,

		_lookupAllowed: false,

		/** @type {string} **/
		tagName: 'div',

		/** @type {OC.Share.ShareConfigModel} **/
		configModel: undefined,

		/** @type {object} **/
		resharerInfoView: undefined,

		/** @type {object} **/
		linkShareView: undefined,

		/** @type {object} **/
		shareeListView: undefined,

		/** @type {object} **/
		_lastSuggestions: undefined,

		/** @type {object} **/
		_lastRecommendations: undefined,

		/** @type {int} **/
		_pendingOperationsCount: 0,

		events: {
			'focus .shareWithField': 'onShareWithFieldFocus',
			'input .shareWithField': 'onShareWithFieldChanged',
			'click .shareWithConfirm': '_confirmShare'
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
			this.configModel.on('change:isRemoteGroupShareAllowed', function() {
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
			var $shareWithField = this.$el.find('.shareWithField');
			$shareWithField.autocomplete("search", $shareWithField.val());
		},

		_getSuggestions: function(searchTerm, perPage, model, lookup) {
			if (this._lastSuggestions &&
				this._lastSuggestions.searchTerm === searchTerm &&
				this._lastSuggestions.lookup === lookup &&
				this._lastSuggestions.perPage === perPage &&
				this._lastSuggestions.model === model) {
				return this._lastSuggestions.promise;
			}

			var deferred = $.Deferred();
			var view = this;

			$.get(
				OC.linkToOCS('apps/files_sharing/api/v1') + 'sharees',
				{
					format: 'json',
					search: searchTerm,
					lookup: lookup,
					perPage: perPage,
					itemType: model.get('itemType')
				},
				function (result) {
					if (result.ocs.meta.statuscode === 100) {
						var filter = function(users, groups, remotes, remote_groups, emails, circles, rooms) {
							if (typeof(emails) === 'undefined') {
								emails = [];
							}
							if (typeof(circles) === 'undefined') {
								circles = [];
							}
							if (typeof(rooms) === 'undefined') {
								rooms = [];
							}

							var usersLength;
							var groupsLength;
							var remotesLength;
							var remoteGroupsLength;
							var emailsLength;
							var circlesLength;
							var roomsLength;

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
							if (model.hasReshare()) {
								usersLength = users.length;
								for (i = 0 ; i < usersLength; i++) {
									if (users[i].value.shareWith === model.getReshareOwner()) {
										users.splice(i, 1);
										break;
									}
								}
							}

							var shares = model.get('shares');
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
								} else if (share.share_type === OC.Share.SHARE_TYPE_REMOTE_GROUP) {
									remoteGroupsLength = remote_groups.length;
									for (j = 0; j < remoteGroupsLength; j++) {
										if (remote_groups[j].value.shareWith === share.share_with) {
											remote_groups.splice(j, 1);
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
								} else if (share.share_type === OC.Share.SHARE_TYPE_ROOM) {
									roomsLength = rooms.length;
									for (j = 0; j < roomsLength; j++) {
										if (rooms[j].value.shareWith === share.share_with) {
											rooms.splice(j, 1);
											break;
										}
									}
								}
							}
						};

						filter(
							result.ocs.data.exact.users,
							result.ocs.data.exact.groups,
							result.ocs.data.exact.remotes,
							result.ocs.data.exact.remote_groups,
							result.ocs.data.exact.emails,
							result.ocs.data.exact.circles,
							result.ocs.data.exact.rooms
						);

						var exactUsers   = result.ocs.data.exact.users;
						var exactGroups  = result.ocs.data.exact.groups;
						var exactRemotes = result.ocs.data.exact.remotes;
						var exactRemoteGroups = result.ocs.data.exact.remote_groups;
						var exactEmails = [];
						if (typeof(result.ocs.data.emails) !== 'undefined') {
							exactEmails = result.ocs.data.exact.emails;
						}
						var exactCircles = [];
						if (typeof(result.ocs.data.circles) !== 'undefined') {
							exactCircles = result.ocs.data.exact.circles;
						}
						var exactRooms = [];
						if (typeof(result.ocs.data.rooms) !== 'undefined') {
							exactRooms = result.ocs.data.exact.rooms;
						}

						var exactMatches = exactUsers.concat(exactGroups).concat(exactRemotes).concat(exactRemoteGroups).concat(exactEmails).concat(exactCircles).concat(exactRooms);

						filter(
							result.ocs.data.users,
							result.ocs.data.groups,
							result.ocs.data.remotes,
							result.ocs.data.remote_groups,
							result.ocs.data.emails,
							result.ocs.data.circles,
							result.ocs.data.rooms
						);

						var users   = result.ocs.data.users;
						var groups  = result.ocs.data.groups;
						var remotes = result.ocs.data.remotes;
						var remoteGroups = result.ocs.data.remote_groups;
						var lookup = result.ocs.data.lookup;
						var lookupEnabled = result.ocs.data.lookupEnabled;
						var emails = [];
						if (typeof(result.ocs.data.emails) !== 'undefined') {
							emails = result.ocs.data.emails;
						}
						var circles = [];
						if (typeof(result.ocs.data.circles) !== 'undefined') {
							circles = result.ocs.data.circles;
						}
						var rooms = [];
						if (typeof(result.ocs.data.rooms) !== 'undefined') {
							rooms = result.ocs.data.rooms;
						}

						var suggestions = exactMatches.concat(users).concat(groups).concat(remotes).concat(remoteGroups).concat(emails).concat(circles).concat(rooms).concat(lookup);

						function dynamicSort(property) {
							return function (a,b) {
								var aProperty = '';
								var bProperty = '';
								if (typeof a[property] !== 'undefined') {
									aProperty = a[property];
								}
								if (typeof b[property] !== 'undefined') {
									bProperty = b[property];
								}
								return (aProperty < bProperty) ? -1 : (aProperty > bProperty) ? 1 : 0;
							}
						}

						/**
						 * Sort share entries by uuid to properly group them
						 */
						var grouped = suggestions.sort(dynamicSort('uuid'));

						var previousUuid = null;
						var groupedLength = grouped.length;
						var result = [];
						/**
						 * build the result array that only contains all contact entries from
						 * merged contacts, if the search term matches its contact name
						 */
						for (var i = 0; i < groupedLength; i++) {
							if (typeof grouped[i].uuid !== 'undefined' && grouped[i].uuid === previousUuid) {
								grouped[i].merged = true;
							}
							if (searchTerm === grouped[i].name || typeof grouped[i].merged === 'undefined') {
								result.push(grouped[i]);
							}
							previousUuid = grouped[i].uuid;
						}
						var moreResultsAvailable =
							(
								OC.config['sharing.maxAutocompleteResults'] > 0
								&& Math.min(perPage, OC.config['sharing.maxAutocompleteResults'])
									<= Math.max(
										users.length + exactUsers.length,
										groups.length + exactGroups.length,
										remoteGroups.length + exactRemoteGroups.length,
										remotes.length + exactRemotes.length,
										emails.length + exactEmails.length,
										circles.length + exactCircles.length,
										rooms.length + exactRooms.length,
										lookup.length
									)
							);
						if (!view._lookup && lookupEnabled) {
							result.push(
								{
									label: t('core', 'Search globally'),
									value: {},
									lookup: true
								}
							)
						}

						deferred.resolve(result, exactMatches, moreResultsAvailable, lookupEnabled);
					} else {
						deferred.reject(result.ocs.meta.message);
					}
				}
			).fail(function() {
				deferred.reject();
			});

			this._lastSuggestions = {
				searchTerm: searchTerm,
				lookup: lookup,
				perPage: perPage,
				model: model,
				promise: deferred.promise()
			};

			return this._lastSuggestions.promise;
		},

		_getRecommendations: function(model) {
			if (this._lastRecommendations &&
				this._lastRecommendations.model === model) {
				return this._lastRecommendations.promise;
			}

			var deferred = $.Deferred();

			$.get(
				OC.linkToOCS('apps/files_sharing/api/v1') + 'sharees_recommended',
				{
					format: 'json',
					itemType: model.get('itemType')
				},
				function (result) {
					if (result.ocs.meta.statuscode === 100) {
						var filter = function(users, groups, remotes, remote_groups, emails, circles, rooms) {
							if (typeof(emails) === 'undefined') {
								emails = [];
							}
							if (typeof(circles) === 'undefined') {
								circles = [];
							}
							if (typeof(rooms) === 'undefined') {
								rooms = [];
							}

							var usersLength;
							var groupsLength;
							var remotesLength;
							var remoteGroupsLength;
							var emailsLength;
							var circlesLength;
							var roomsLength;

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
							if (model.hasReshare()) {
								usersLength = users.length;
								for (i = 0 ; i < usersLength; i++) {
									if (users[i].value.shareWith === model.getReshareOwner()) {
										users.splice(i, 1);
										break;
									}
								}
							}

							var shares = model.get('shares');
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
								} else if (share.share_type === OC.Share.SHARE_TYPE_REMOTE_GROUP) {
									remoteGroupsLength = remote_groups.length;
									for (j = 0; j < remoteGroupsLength; j++) {
										if (remote_groups[j].value.shareWith === share.share_with) {
											remote_groups.splice(j, 1);
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
								} else if (share.share_type === OC.Share.SHARE_TYPE_ROOM) {
									roomsLength = rooms.length;
									for (j = 0; j < roomsLength; j++) {
										if (rooms[j].value.shareWith === share.share_with) {
											rooms.splice(j, 1);
											break;
										}
									}
								}
							}
						};

						filter(
							result.ocs.data.exact.users,
							result.ocs.data.exact.groups,
							result.ocs.data.exact.remotes,
							result.ocs.data.exact.remote_groups,
							result.ocs.data.exact.emails,
							result.ocs.data.exact.circles,
							result.ocs.data.exact.rooms
						);

						var exactUsers   = result.ocs.data.exact.users;
						var exactGroups  = result.ocs.data.exact.groups;
						var exactRemotes = result.ocs.data.exact.remotes || [];
						var exactRemoteGroups = result.ocs.data.exact.remote_groups || [];
						var exactEmails = [];
						if (typeof(result.ocs.data.emails) !== 'undefined') {
							exactEmails = result.ocs.data.exact.emails;
						}
						var exactCircles = [];
						if (typeof(result.ocs.data.circles) !== 'undefined') {
							exactCircles = result.ocs.data.exact.circles;
						}
						var exactRooms = [];
						if (typeof(result.ocs.data.rooms) !== 'undefined') {
							exactRooms = result.ocs.data.exact.rooms;
						}

						var exactMatches = exactUsers.concat(exactGroups).concat(exactRemotes).concat(exactRemoteGroups).concat(exactEmails).concat(exactCircles).concat(exactRooms);

						filter(
							result.ocs.data.users,
							result.ocs.data.groups,
							result.ocs.data.remotes,
							result.ocs.data.remote_groups,
							result.ocs.data.emails,
							result.ocs.data.circles,
							result.ocs.data.rooms
						);

						var users   = result.ocs.data.users;
						var groups  = result.ocs.data.groups;
						var remotes = result.ocs.data.remotes || [];
						var remoteGroups = result.ocs.data.remote_groups || [];
						var lookup = result.ocs.data.lookup || [];
						var emails = [];
						if (typeof(result.ocs.data.emails) !== 'undefined') {
							emails = result.ocs.data.emails;
						}
						var circles = [];
						if (typeof(result.ocs.data.circles) !== 'undefined') {
							circles = result.ocs.data.circles;
						}
						var rooms = [];
						if (typeof(result.ocs.data.rooms) !== 'undefined') {
							rooms = result.ocs.data.rooms;
						}

						var suggestions = exactMatches.concat(users).concat(groups).concat(remotes).concat(remoteGroups).concat(emails).concat(circles).concat(rooms).concat(lookup);

						function dynamicSort(property) {
							return function (a,b) {
								var aProperty = '';
								var bProperty = '';
								if (typeof a[property] !== 'undefined') {
									aProperty = a[property];
								}
								if (typeof b[property] !== 'undefined') {
									bProperty = b[property];
								}
								return (aProperty < bProperty) ? -1 : (aProperty > bProperty) ? 1 : 0;
							}
						}

						/**
						 * Sort share entries by uuid to properly group them
						 */
						var grouped = suggestions.sort(dynamicSort('uuid'));

						var previousUuid = null;
						var groupedLength = grouped.length;
						var result = [];
						/**
						 * build the result array that only contains all contact entries from
						 * merged contacts, if the search term matches its contact name
						 */
						for (var i = 0; i < groupedLength; i++) {
							if (typeof grouped[i].uuid !== 'undefined' && grouped[i].uuid === previousUuid) {
								grouped[i].merged = true;
							}
							if (typeof grouped[i].merged === 'undefined') {
								result.push(grouped[i]);
							}
							previousUuid = grouped[i].uuid;
						}

						deferred.resolve(result, exactMatches, false);
					} else {
						deferred.reject(result.ocs.meta.message);
					}
				}
			).fail(function() {
				deferred.reject();
			});

			this._lastRecommendations = {
				model: model,
				promise: deferred.promise()
			};

			return this._lastRecommendations.promise;
		},

		recommendationHandler: function (response) {
			var view = this;
			var $shareWithField = $('.shareWithField');
			this._getRecommendations(
				view.model
			).done(function(suggestions) {
				console.info('recommendations', suggestions);
				if (suggestions.length > 0) {
					$shareWithField
						.autocomplete("option", "autoFocus", true);

					response(suggestions);
				} else {
					console.info('no sharing recommendations found');
					response();
				}
			}).fail(function(message) {
				console.error('could not load recommendations', message)
			});
		},

		autocompleteHandler: function (search, response) {
			// If nothing is entered we show recommendations instead of search
			// results
			if (search.term.length === 0) {
				console.info(search.term, 'empty search term -> using recommendations');
				this.recommendationHandler(response);
				return;
			}

			var $shareWithField = $('.shareWithField'),
				view = this,
				$loading = this.$el.find('.shareWithLoading'),
				$confirm = this.$el.find('.shareWithConfirm');

			var count = OC.config['sharing.minSearchStringLength'];
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
			$confirm.addClass('hidden');
			this._pendingOperationsCount++;

			$shareWithField.removeClass('error')
				.tooltip('hide');

			var perPage = parseInt(OC.config['sharing.maxAutocompleteResults'], 10) || 200;
			this._getSuggestions(
				search.term.trim(),
				perPage,
				view.model,
				view._lookup
			).done(function(suggestions, exactMatches, moreResultsAvailable) {
				view._pendingOperationsCount--;
				if (view._pendingOperationsCount === 0) {
					$loading.addClass('hidden');
					$loading.removeClass('inlineblock');
					$confirm.removeClass('hidden');
				}

				if (suggestions.length > 0) {
					$shareWithField
						.autocomplete("option", "autoFocus", true);

					response(suggestions);

					// show a notice that the list is truncated
					// this is the case if one of the search results is at least as long as the max result config option
					if(moreResultsAvailable) {
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
							placement: 'top',
							trigger: 'manual'
						})
						.tooltip('fixTitle')
						.tooltip('show');
					response();
				}
			}).fail(function(message) {
				view._pendingOperationsCount--;
				if (view._pendingOperationsCount === 0) {
					$loading.addClass('hidden');
					$loading.removeClass('inlineblock');
					$confirm.removeClass('hidden');
				}

				if (message) {
					OC.Notification.showTemporary(t('core', 'An error occurred ("{message}"). Please try again', { message: message }));
				} else {
					OC.Notification.showTemporary(t('core', 'An error occurred. Please try again'));
				}
			});
		},

		autocompleteRenderItem: function(ul, item) {
			var icon = 'icon-user';
			var text = escapeHTML(item.label);
			var description = '';
			var type = '';
			var getTranslatedType = function(type) {
				switch (type) {
					case 'HOME':
						return t('core', 'Home');
					case 'WORK':
						return t('core', 'Work');
					case 'OTHER':
						return t('core', 'Other');
					default:
						return '' + type;
				}
			};
			if (typeof item.type !== 'undefined' && item.type !== null) {
				type = getTranslatedType(item.type) + ' ';
			}

			if (typeof item.name !== 'undefined') {
				text = escapeHTML(item.name);
			}
			if (item.value.shareType === OC.Share.SHARE_TYPE_GROUP) {
				icon = 'icon-contacts-dark';
			} else if (item.value.shareType === OC.Share.SHARE_TYPE_REMOTE) {
				icon = 'icon-shared';
				description += item.value.shareWith;
			} else if (item.value.shareType === OC.Share.SHARE_TYPE_REMOTE_GROUP) {
				text = t('core', '{sharee} (remote group)', { sharee: text }, undefined, { escape: false });
				icon = 'icon-shared';
				description += item.value.shareWith;
			} else if (item.value.shareType === OC.Share.SHARE_TYPE_EMAIL) {
				icon = 'icon-mail';
				description += item.value.shareWith;
			} else if (item.value.shareType === OC.Share.SHARE_TYPE_CIRCLE) {
				text = t('core', '{sharee} ({type}, {owner})', {sharee: text, type: item.value.circleInfo, owner: item.value.circleOwner}, undefined, {escape: false});
				icon = 'icon-circle';
			} else if (item.value.shareType === OC.Share.SHARE_TYPE_ROOM) {
				icon = 'icon-talk';
			}

			var insert = $("<div class='share-autocomplete-item'/>");
			if (item.merged) {
				insert.addClass('merged');
				text = item.value.shareWith;
				description = type;
			} else if (item.lookup) {
				text = item.label;
				icon = false;
				insert.append('<span class="icon icon-search search-globally"></span>');
			} else {
				var avatar = $("<div class='avatardiv'></div>").appendTo(insert);
				if (item.value.shareType === OC.Share.SHARE_TYPE_USER || item.value.shareType === OC.Share.SHARE_TYPE_CIRCLE) {
					avatar.avatar(item.value.shareWith, 32, undefined, undefined, undefined, item.label);
				} else {
					if (typeof item.uuid === 'undefined') {
						item.uuid = text;
					}
					avatar.imageplaceholder(item.uuid, text, 32);
				}
				description = type + description;
			}
			if (description !== '') {
				insert.addClass('with-description');
			}

			$("<div class='autocomplete-item-text'></div>")
				.html(
					text.replace(
					new RegExp(this.term, "gi"),
					"<span class='ui-state-highlight'>$&</span>")
					+ '<span class="autocomplete-item-details">' + description + '</span>'
				)
				.appendTo(insert);
			insert.attr('title', item.value.shareWith);
			if (icon) {
				insert.append('<span class="icon ' + icon + '" title="' + text + '"></span>');
			}
			insert = $("<a>")
				.append(insert);
			return $("<li>")
				.addClass((item.value.shareType === OC.Share.SHARE_TYPE_GROUP) ? 'group' : 'user')
				.append(insert)
				.appendTo(ul);
		},

		_onSelectRecipient: function(e, s) {
			var self = this;

			if (e.keyCode == 9) {
				e.preventDefault();
				if (typeof s.item.name !== 'undefined') {
					e.target.value = s.item.name;
				} else {
					e.target.value = s.item.label;
				}
				setTimeout(function() {
					$(e.target).attr('disabled', false)
						.autocomplete('search', $(e.target).val());
				}, 0);
				return false;
			}

			if (s.item.lookup) {
				// Retrigger search but with global lookup this time
				this._lookup = true;
				var $shareWithField = this.$el.find('.shareWithField');
				var val = $shareWithField.val();
				setTimeout(function() {
					console.debug('searching again, but globally. search term: ' + val);
					$shareWithField.autocomplete("search", val);
				}, 0);
				return false;
			}

			e.preventDefault();
			// Ensure that the keydown handler for the input field is not
			// called; otherwise it would try to add the recipient again, which
			// would fail.
			e.stopImmediatePropagation();
			$(e.target).attr('disabled', true)
				.val(s.item.label);

			var $loading = this.$el.find('.shareWithLoading');
			var $confirm = this.$el.find('.shareWithConfirm');

			$loading.removeClass('hidden');
			$loading.addClass('inlineblock');
			$confirm.addClass('hidden');
			this._pendingOperationsCount++;

			this.model.addShare(s.item.value, {success: function() {
				// Adding a share changes the suggestions.
				self._lastSuggestions = undefined;

				$(e.target).val('')
					.attr('disabled', false);

				self._pendingOperationsCount--;
				if (self._pendingOperationsCount === 0) {
					$loading.addClass('hidden');
					$loading.removeClass('inlineblock');
					$confirm.removeClass('hidden');
				}
			}, error: function(obj, msg) {
				OC.Notification.showTemporary(msg);
				$(e.target).attr('disabled', false)
					.autocomplete('search', $(e.target).val());

				self._pendingOperationsCount--;
				if (self._pendingOperationsCount === 0) {
					$loading.addClass('hidden');
					$loading.removeClass('inlineblock');
					$confirm.removeClass('hidden');
				}
			}});
		},

		_confirmShare: function() {
			var self = this;
			var $shareWithField = $('.shareWithField');
			var $loading = this.$el.find('.shareWithLoading');
			var $confirm = this.$el.find('.shareWithConfirm');

			$loading.removeClass('hidden');
			$loading.addClass('inlineblock');
			$confirm.addClass('hidden');
			this._pendingOperationsCount++;

			$shareWithField.prop('disabled', true);

			// Disabling the autocompletion does not clear its search timeout;
			// removing the focus from the input field does, but only if the
			// autocompletion is not disabled when the field loses the focus.
			// Thus, the field has to be disabled before disabling the
			// autocompletion to prevent an old pending search result from
			// appearing once the field is enabled again.
			$shareWithField.autocomplete('close');
			$shareWithField.autocomplete('disable');

			var restoreUI = function() {
				self._pendingOperationsCount--;
				if (self._pendingOperationsCount === 0) {
					$loading.addClass('hidden');
					$loading.removeClass('inlineblock');
					$confirm.removeClass('hidden');
				}

				$shareWithField.prop('disabled', false);
				$shareWithField.focus();
			};

			var perPage = parseInt(OC.config['sharing.maxAutocompleteResults'], 10) || 200;
			this._getSuggestions(
				$shareWithField.val(),
				perPage,
				this.model,
				this._lookup
			).done(function(suggestions, exactMatches) {
				if (suggestions.length === 0) {
					restoreUI();

					$shareWithField.autocomplete('enable');

					// There is no need to show an error message here; it will
					// be automatically shown when the autocomplete is activated
					// again (due to the focus on the field) and it finds no
					// matches.

					return;
				}

				if (exactMatches.length !== 1) {
					restoreUI();

					$shareWithField.autocomplete('enable');

					return;
				}

				var actionSuccess = function() {
					// Adding a share changes the suggestions.
					self._lastSuggestions = undefined;

					$shareWithField.val('');

					restoreUI();

					$shareWithField.autocomplete('enable');
				};

				var actionError = function(obj, msg) {
					restoreUI();

					$shareWithField.autocomplete('enable');

					OC.Notification.showTemporary(msg);
				};

				self.model.addShare(exactMatches[0].value, {
					success: actionSuccess,
					error: actionError
				});
			}).fail(function(message) {
				restoreUI();

				$shareWithField.autocomplete('enable');

				// There is no need to show an error message here; it will be
				// automatically shown when the autocomplete is activated again
				// (due to the focus on the field) and getting the suggestions
				// fail.
			});
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
			}
		},

		render: function() {
			var self = this;
			var baseTemplate = OC.Share.Templates['sharedialogview'];

			this.$el.html(baseTemplate({
				cid: this.cid,
				shareLabel: t('core', 'Share'),
				sharePlaceholder: this._renderSharePlaceholderPart(),
				isSharingAllowed: this.model.sharePermissionPossible()
			}));

			var $shareField = this.$el.find('.shareWithField');
			if ($shareField.length) {
				var shareFieldKeydownHandler = function(event) {
					if (event.keyCode !== 13) {
						return true;
					}

					self._confirmShare();

					return false;
				};

				$shareField.autocomplete({
					minLength: 0,
					delay: 750,
					focus: function(event) {
						event.preventDefault();
					},
					source: this.autocompleteHandler,
					select: this._onSelectRecipient,
					open: function() {
						var autocomplete = $(this).autocomplete('widget');
						var numberOfItems = autocomplete.find('li').size();
						autocomplete.removeClass('item-count-1');
						autocomplete.removeClass('item-count-2');
						if (numberOfItems <= 2) {
							autocomplete.addClass('item-count-' + numberOfItems);
						}
					}
				}).data('ui-autocomplete')._renderItem = this.autocompleteRenderItem;

				$shareField.on('keydown', null, shareFieldKeydownHandler);
			}

			this.resharerInfoView.$el = this.$el.find('.resharerInfoView');
			this.resharerInfoView.render();

			this.linkShareView.$el = this.$el.find('.linkShareView');
			this.linkShareView.render();

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

	});

	OC.Share.ShareDialogView = ShareDialogView;

})();
