/**
 * Copyright (c) 2014, Arthur Schiwon <blizzz@owncloud.com>
 * Copyright (c) 2014, Raghu Nayyar <beingminimal@gmail.com>
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

var $userList;
var $userListBody;
var filter;

var UserList = {
	availableGroups: [],
	offset: 0,
	usersToLoad: 10, //So many users will be loaded when user scrolls down
	currentGid: '',

	/**
	 * Initializes the user list
	 * @param $el user list table element
	 */
	initialize: function($el) {
		this.$el = $el;

		// initially the list might already contain user entries (not fully ajaxified yet)
		// initialize these entries
		this.$el.find('.quota-user').singleSelect().on('change', this.onQuotaSelect);
	},

	/**
	 * Add a user row from user object
	 *
	 * @param user object containing following keys:
	 * 			{
	 * 				'name': 			'username',
	 * 				'displayname': 		'Users display name',
	 * 				'groups': 			['group1', 'group2'],
	 * 				'subadmin': 		['group4', 'group5'],
	 *				'quota': 			'10 GB',
	 *				'storageLocation':	'/srv/www/owncloud/data/username',
	 *				'lastLogin':		'1418632333'
	 *				'backend':			'LDAP',
	 *				'email':			'username@example.org'
	 *				'isRestoreDisabled':false
	 * 			}
	 * @param sort
	 * @returns table row created for this user
	 */
	add: function (user, sort) {
		var $tr = $userListBody.find('tr:first-child').clone();
		// this removes just the `display:none` of the template row
		$tr.removeAttr('style');
		var subAdminsEl;
		var subAdminSelect;
		var groupsSelect;

		/**
		 * Avatar or placeholder
		 */
		if ($tr.find('div.avatardiv').length){
			$tr.find('.avatardiv').imageplaceholder(user.name, user.displayname);
			$('div.avatardiv', $tr).avatar(user.name, 32);
		}

		/**
		 * add username and displayname to row (in data and visible markup)
		 */
		$tr.data('uid', user.name);
		$tr.data('displayname', user.displayname);
		$tr.data('mailAddress', user.email);
		$tr.data('restoreDisabled', user.isRestoreDisabled);
		$tr.find('td.name').text(user.name);
		$tr.find('td.displayName > span').text(user.displayname);
		$tr.find('td.mailAddress > span').text(user.email);

		/**
		 * groups and subadmins
		 */
		// make them look like the multiselect buttons
		// until they get time to really get initialized
		groupsSelect = $('<select multiple="multiple" class="groupsselect multiselect button" data-placehoder="Groups" title="' + t('settings', 'no group') + '"></select>')
			.data('username', user.name)
			.data('user-groups', user.groups);
		if ($tr.find('td.subadmins').length > 0) {
			subAdminSelect = $('<select multiple="multiple" class="subadminsselect multiselect button" data-placehoder="subadmins" title="' + t('settings', 'no group') + '">')
				.data('username', user.name)
				.data('user-groups', user.groups)
				.data('subadmin', user.subadmin);
			$tr.find('td.subadmins').empty();
		}
		$.each(this.availableGroups, function (i, group) {
			groupsSelect.append($('<option value="' + escapeHTML(group) + '">' + escapeHTML(group) + '</option>'));
			if (typeof subAdminSelect !== 'undefined' && group !== 'admin') {
				subAdminSelect.append($('<option value="' + escapeHTML(group) + '">' + escapeHTML(group) + '</option>'));
			}
		});
		$tr.find('td.groups').empty().append(groupsSelect);
		subAdminsEl = $tr.find('td.subadmins');
		if (subAdminsEl.length > 0) {
			subAdminsEl.append(subAdminSelect);
		}

		/**
		 * remove action
		 */
		if ($tr.find('td.remove img').length === 0 && OC.currentUser !== user.name) {
			var deleteImage = $('<img class="svg action">').attr({
				src: OC.imagePath('core', 'actions/delete')
			});
			var deleteLink = $('<a class="action delete">')
				.attr({ href: '#', 'original-title': t('settings', 'Delete')})
				.append(deleteImage);
			$tr.find('td.remove').append(deleteLink);
		} else if (OC.currentUser === user.name) {
			$tr.find('td.remove a').remove();
		}

		/**
		 * quota
		 */
		var $quotaSelect = $tr.find('.quota-user');
		if (user.quota === 'default') {
			$quotaSelect
				.data('previous', 'default')
				.find('option').attr('selected', null)
				.first().attr('selected', 'selected');
		} else {
			if ($quotaSelect.find('option').filterAttr('value', user.quota).length > 0) {
				$quotaSelect.find('option').filterAttr('value', user.quota).attr('selected', 'selected');
			} else {
				$quotaSelect.append('<option value="' + escapeHTML(user.quota) + '" selected="selected">' + escapeHTML(user.quota) + '</option>');
			}
		}

		/**
		 * storage location
		 */
		$tr.find('td.storageLocation').text(user.storageLocation);

		/**
		 * user backend
		 */
		$tr.find('td.userBackend').text(user.backend);

		/**
		 * last login
		 */
		var lastLoginRel = t('settings', 'never');
		var lastLoginAbs = lastLoginRel;
		if(user.lastLogin !== 0) {
			lastLoginRel = OC.Util.relativeModifiedDate(user.lastLogin);
			lastLoginAbs = OC.Util.formatDate(user.lastLogin);
		}
		var $tdLastLogin = $tr.find('td.lastLogin');
		$tdLastLogin.text(lastLoginRel);
		//tooltip makes it complicated … to not insert new HTML, we adjust the
		//original title. We use a temporary div to get back the html that we
		//can pass later. It is also required to initialise tipsy.
		var tooltip = $('<div>').html($($tdLastLogin.attr('original-title')).text(lastLoginAbs)).html();
		$tdLastLogin.tipsy({gravity:'s', fade:true, html:true});
		$tdLastLogin.attr('title', tooltip);

		/**
		 * append generated row to user list
		 */
		$tr.appendTo($userList);
		if(UserList.isEmpty === true) {
			//when the list was emptied, one row was left, necessary to keep
			//add working and the layout unbroken. We need to remove this item
			$tr.show();
			$userListBody.find('tr:first').remove();
			UserList.isEmpty = false;
			UserList.checkUsersToLoad();
		}

		/**
		 * sort list
		 */
		if (sort) {
			UserList.doSort();
		}

		$quotaSelect.on('change', UserList.onQuotaSelect);

		// defer init so the user first sees the list appear more quickly
		window.setTimeout(function(){
			$quotaSelect.singleSelect();
			UserList.applyGroupSelect(groupsSelect);
			if (subAdminSelect) {
				UserList.applySubadminSelect(subAdminSelect);
			}
		}, 0);
		return $tr;
	},
	// From http://my.opera.com/GreyWyvern/blog/show.dml/1671288
	alphanum: function(a, b) {
		function chunkify(t) {
			var tz = [], x = 0, y = -1, n = 0, i, j;

			while (i = (j = t.charAt(x++)).charCodeAt(0)) {
				var m = (i === 46 || (i >=48 && i <= 57));
				if (m !== n) {
					tz[++y] = "";
					n = m;
				}
				tz[y] += j;
			}
			return tz;
		}

		var aa = chunkify(a.toLowerCase());
		var bb = chunkify(b.toLowerCase());

		for (var x = 0; aa[x] && bb[x]; x++) {
			if (aa[x] !== bb[x]) {
				var c = Number(aa[x]), d = Number(bb[x]);
				if (c === aa[x] && d === bb[x]) {
					return c - d;
				} else {
					return (aa[x] > bb[x]) ? 1 : -1;
				}
			}
		}
		return aa.length - bb.length;
	},
	preSortSearchString: function(a, b) {
		var pattern = filter.getPattern();
		if(typeof pattern === 'undefined') {
			return undefined;
		}
		pattern = pattern.toLowerCase();
		var aMatches = false;
		var bMatches = false;
		if(typeof a === 'string' && a.toLowerCase().indexOf(pattern) === 0) {
			aMatches = true;
		}
		if(typeof b === 'string' && b.toLowerCase().indexOf(pattern) === 0) {
			bMatches = true;
		}

		if((aMatches && bMatches) || (!aMatches && !bMatches)) {
			return undefined;
		}

		if(aMatches) {
			return -1;
		} else {
			return 1;
		}
	},
	doSort: function() {
		var rows = $userListBody.find('tr').get();

		rows.sort(function(a, b) {
			// FIXME: inefficient way of getting the names,
			// better use a data attribute
			a = $(a).find('td.name').text();
			b = $(b).find('td.name').text();
			var firstSort = UserList.preSortSearchString(a, b);
			if(typeof firstSort !== 'undefined') {
				return firstSort;
			}
			return OC.Util.naturalSortCompare(a, b);
		});

		var items = [];
		$.each(rows, function(index, row) {
			items.push(row);
			if(items.length === 100) {
				$userListBody.append(items);
				items = [];
			}
		});
		if(items.length > 0) {
			$userListBody.append(items);
		}
	},
	checkUsersToLoad: function() {
		//30 shall be loaded initially, from then on always 10 upon scrolling
		if(UserList.isEmpty === false) {
			UserList.usersToLoad = 10;
		} else {
			UserList.usersToLoad = 30;
		}
	},
	empty: function() {
		//one row needs to be kept, because it is cloned to add new rows
		$userListBody.find('tr:not(:first)').remove();
		var $tr = $userListBody.find('tr:first');
		$tr.hide();
		//on an update a user may be missing when the username matches with that
		//of the hidden row. So change this to a random string.
		$tr.data('uid', Math.random().toString(36).substring(2));
		UserList.isEmpty = true;
		UserList.offset = 0;
		UserList.checkUsersToLoad();
	},
	hide: function(uid) {
		UserList.getRow(uid).hide();
	},
	show: function(uid) {
		UserList.getRow(uid).show();
	},
	markRemove: function(uid) {
		var $tr = UserList.getRow(uid);
		var groups = $tr.find('.groups .groupsselect').val();
		for(var i in groups) {
			var gid = groups[i];
			var $li = GroupList.getGroupLI(gid);
			var userCount = GroupList.getUserCount($li);
			if(userCount === 1) {
				GroupList.setUserCount($li, '');
			} else {
				GroupList.setUserCount($li, userCount - 1);
			}
		}
		GroupList.decEveryoneCount();
		UserList.hide(uid);
	},
	remove: function(uid) {
		UserList.getRow(uid).remove();
	},
	undoRemove: function(uid) {
		var $tr = UserList.getRow(uid);
		var groups = $tr.find('.groups .groupsselect').val();
		for(var i in groups) {
			var gid = groups[i];
			var $li = GroupList.getGroupLI(gid);
			var userCount = GroupList.getUserCount($li);
			if(userCount === 1) {
				GroupList.setUserCount($li, '');
			} else {
				GroupList.setUserCount($li, userCount + 1);
			}
		}
		GroupList.incEveryoneCount();
		UserList.getRow(uid).show();
	},
	has: function(uid) {
		return UserList.getRow(uid).length > 0;
	},
	getRow: function(uid) {
		return $userListBody.find('tr').filter(function(){
			return UserList.getUID(this) === uid;
		});
	},
	getUID: function(element) {
		return ($(element).closest('tr').data('uid') || '').toString();
	},
	getDisplayName: function(element) {
		return ($(element).closest('tr').data('displayname') || '').toString();
	},
	getMailAddress: function(element) {
		return ($(element).closest('tr').data('mailAddress') || '').toString();
	},
	getRestoreDisabled: function(element) {
		return ($(element).closest('tr').data('restoreDisabled') || '');
	},
	initDeleteHandling: function() {
		//set up handler
		UserDeleteHandler = new DeleteHandler('/settings/users/users', 'username',
											UserList.markRemove, UserList.remove);

		//configure undo
		OC.Notification.hide();
		var msg = escapeHTML(t('settings', 'deleted {userName}', {userName: '%oid'})) + '<span class="undo">' +
			escapeHTML(t('settings', 'undo')) + '</span>';
		UserDeleteHandler.setNotification(OC.Notification, 'deleteuser', msg,
										UserList.undoRemove);

		//when to mark user for delete
		$userListBody.on('click', '.delete', function () {
			// Call function for handling delete/undo
			var uid = UserList.getUID(this);
			UserDeleteHandler.mark(uid);
		});

		//delete a marked user when leaving the page
		$(window).on('beforeunload', function () {
			UserDeleteHandler.deleteEntry();
		});
	},
	update: function (gid, limit) {
		if (UserList.updating) {
			return;
		}
		if(!limit) {
			limit = UserList.usersToLoad;
		}
		$userList.siblings('.loading').css('visibility', 'visible');
		UserList.updating = true;
		if(gid === undefined) {
			gid = '';
		}
		UserList.currentGid = gid;
		var pattern = filter.getPattern();
		$.get(
			OC.generateUrl('/settings/users/users'),
			{ offset: UserList.offset, limit: limit, gid: gid, pattern: pattern },
			function (result) {
				var loadedUsers = 0;
				var trs = [];
				//The offset does not mirror the amount of users available,
				//because it is backend-dependent. For correct retrieval,
				//always the limit(requested amount of users) needs to be added.
				$.each(result, function (index, user) {
					if(UserList.has(user.name)) {
						return true;
					}
					var $tr = UserList.add(user, user.lastLogin, false, user.backend);
					trs.push($tr);
					loadedUsers++;
				});
				if (result.length > 0) {
					UserList.doSort();
					$userList.siblings('.loading').css('visibility', 'hidden');
					// reset state on load
					UserList.noMoreEntries = false;
				}
				else {
					UserList.noMoreEntries = true;
					$userList.siblings('.loading').remove();
				}
				UserList.offset += limit;
			}).always(function() {
				UserList.updating = false;
			});
	},

	applyGroupSelect: function (element) {
		var checked = [];
		var $element = $(element);
		var user = UserList.getUID($element);

		if ($element.data('user-groups')) {
			if (typeof $element.data('user-groups') === 'string') {
				checked = $element.data('user-groups').split(", ");
			}
			else {
				checked = $element.data('user-groups');
			}
		}
		var checkHandler = null;
		if(user) { // Only if in a user row, and not the #newusergroups select
			checkHandler = function (group) {
				if (user === OC.currentUser && group === 'admin') {
					return false;
				}
				if (!oc_isadmin && checked.length === 1 && checked[0] === group) {
					return false;
				}
				$.post(
					OC.filePath('settings', 'ajax', 'togglegroups.php'),
					{
						username: user,
						group: group
					},
					function (response) {
						if (response.status === 'success') {
							GroupList.update();
							var groupName = response.data.groupname;
							if (UserList.availableGroups.indexOf(groupName) === -1 &&
								response.data.action === 'add'
							) {
								UserList.availableGroups.push(groupName);
							}

							// in case this was the last user in that group the group has to be removed
							var groupElement = GroupList.getGroupLI(groupName);
							var userCount = GroupList.getUserCount(groupElement);
							if (response.data.action === 'remove' && userCount === 1) {
								_.without(UserList.availableGroups, groupName);
								GroupList.remove(groupName);
								$('.groupsselect option').filterAttr('value', groupName).remove();
								$('.subadminsselect option').filterAttr('value', groupName).remove();
							}


						}
						if (response.data.message) {
							OC.Notification.show(response.data.message);
						}
					}
				);
			};
		}
		var addGroup = function (select, group) {
			$('select[multiple]').each(function (index, element) {
				$element = $(element);
				if ($element.find('option').filterAttr('value', group).length === 0 &&
					select.data('msid') !== $element.data('msid')) {
					$element.append('<option value="' + escapeHTML(group) + '">' + escapeHTML(group) + '</option>');
				}
			});
			GroupList.addGroup(escapeHTML(group));
		};
		var label;
		if (oc_isadmin) {
			label = t('settings', 'add group');
		}
		else {
			label = null;
		}
		$element.multiSelect({
			createCallback: addGroup,
			createText: label,
			selectedFirst: true,
			checked: checked,
			oncheck: checkHandler,
			onuncheck: checkHandler,
			minWidth: 100
		});
	},

	applySubadminSelect: function (element) {
		var checked = [];
		var $element = $(element);
		var user = UserList.getUID($element);

		if ($element.data('subadmin')) {
			if (typeof $element.data('subadmin') === 'string') {
				checked = $element.data('subadmin').split(", ");
			}
			else {
				checked = $element.data('subadmin');
			}
		}
		var checkHandler = function (group) {
			if (group === 'admin') {
				return false;
			}
			$.post(
				OC.filePath('settings', 'ajax', 'togglesubadmins.php'),
				{
					username: user,
					group: group
				},
				function () {
				}
			);
		};

		var addSubAdmin = function (group) {
			$('select[multiple]').each(function (index, element) {
				if ($(element).find('option').filterAttr('value', group).length === 0) {
					$(element).append('<option value="' + escapeHTML(group) + '">' + escapeHTML(group) + '</option>');
				}
			});
		};
		$element.multiSelect({
			createCallback: addSubAdmin,
			createText: null,
			checked: checked,
			oncheck: checkHandler,
			onuncheck: checkHandler,
			minWidth: 100
		});
	},

	_onScroll: function() {
		if (!!UserList.noMoreEntries) {
			return;
		}
		if (UserList.scrollArea.scrollTop() + UserList.scrollArea.height() > UserList.scrollArea.get(0).scrollHeight - 500) {
			UserList.update(UserList.currentGid);
		}
	},

	/**
	 * Event handler for when a quota has been changed through a single select.
	 * This will save the value.
	 */
	onQuotaSelect: function(ev) {
		var $select = $(ev.target);
		var uid = UserList.getUID($select);
		var quota = $select.val();
		UserList._updateQuota(uid, quota, function(returnedQuota){
			if (quota !== returnedQuota) {
				$select.find(':selected').text(returnedQuota);
			}
		});
	},

	/**
	 * Saves the quota for the given user
	 * @param {String} [uid] optional user id, sets default quota if empty
	 * @param {String} quota quota value
	 * @param {Function} ready callback after save
	 */
	_updateQuota: function(uid, quota, ready) {
		$.post(
			OC.filePath('settings', 'ajax', 'setquota.php'),
			{username: uid, quota: quota},
			function (result) {
				if (ready) {
					ready(result.data.quota);
				}
			}
		);
	}
};

$(document).ready(function () {
	$userList = $('#userlist');
	$userListBody = $userList.find('tbody');

	UserList.initDeleteHandling();

	// Implements User Search
	filter = new UserManagementFilter($('#usersearchform input'), UserList, GroupList);

	UserList.doSort();
	UserList.availableGroups = $userList.data('groups');

	UserList.scrollArea = $('#app-content');
	UserList.scrollArea.scroll(function(e) {UserList._onScroll(e);});

	$userList.after($('<div class="loading" style="height: 200px; visibility: hidden;"></div>'));

	// TODO: move other init calls inside of initialize
	UserList.initialize($('#userlist'));

	$('.groupsselect').each(function (index, element) {
		UserList.applyGroupSelect(element);
	});
	$('.subadminsselect').each(function (index, element) {
		UserList.applySubadminSelect(element);
	});

	$userListBody.on('click', '.password', function (event) {
		event.stopPropagation();

		var $td = $(this).closest('td');
		var $tr = $(this).closest('tr');
		var uid = UserList.getUID($td);
		var $input = $('<input type="password">');
		var isRestoreDisabled = UserList.getRestoreDisabled($td) === true;
		if(isRestoreDisabled) {
			$tr.addClass('row-warning');
			// add tipsy if the password change could cause data loss - no recovery enabled
			$input.tipsy({gravity:'s', fade:false});
			$input.attr('title', t('settings', 'Changing the password will result in data loss, because data recovery is not available for this user'));
		}
		$td.find('img').hide();
		$td.children('span').replaceWith($input);
		$input
			.focus()
			.keypress(function (event) {
				if (event.keyCode === 13) {
					if ($(this).val().length > 0) {
						var recoveryPasswordVal = $('input:password[id="recoveryPassword"]').val();
						$.post(
							OC.generateUrl('/settings/users/changepassword'),
							{username: uid, password: $(this).val(), recoveryPassword: recoveryPasswordVal},
							function (result) {
								if (result.status != 'success') {
									OC.Notification.show(t('admin', result.data.message));
								}
							}
						);
						$input.blur();
					} else {
						$input.blur();
					}
				}
			})
			.blur(function () {
				$(this).replaceWith($('<span>●●●●●●●</span>'));
				$td.find('img').show();
				// remove highlight class from users without recovery ability
				$tr.removeClass('row-warning');
			});
	});
	$('input:password[id="recoveryPassword"]').keyup(function() {
		OC.Notification.hide();
	});

	$userListBody.on('click', '.displayName', function (event) {
		event.stopPropagation();
		var $td = $(this).closest('td');
		var $tr = $td.closest('tr');
		var uid = UserList.getUID($td);
		var displayName = escapeHTML(UserList.getDisplayName($td));
		var $input = $('<input type="text" value="' + displayName + '">');
		$td.find('img').hide();
		$td.children('span').replaceWith($input);
		$input
			.focus()
			.keypress(function (event) {
				if (event.keyCode === 13) {
					if ($(this).val().length > 0) {
						var $div = $tr.find('div.avatardiv');
						if ($div.length) {
							$div.imageplaceholder(uid, displayName);
						}
						$.post(
							OC.filePath('settings', 'ajax', 'changedisplayname.php'),
							{username: uid, displayName: $(this).val()},
							function (result) {
								if (result && result.status==='success' && $div.length){
									$div.avatar(result.data.username, 32);
								}
							}
						);
						$input.blur();
					} else {
						$input.blur();
					}
				}
			})
			.blur(function () {
				var displayName = $input.val();
				$tr.data('displayname', displayName);
				$input.replaceWith('<span>' + escapeHTML(displayName) + '</span>');
				$td.find('img').show();
			});
	});

	$userListBody.on('click', '.mailAddress', function (event) {
		event.stopPropagation();
		var $td = $(this).closest('td');
		var $tr = $td.closest('tr');
		var uid = UserList.getUID($td);
		var mailAddress = escapeHTML(UserList.getMailAddress($td));
		var $input = $('<input type="text">').val(mailAddress);
		$td.children('span').replaceWith($input);
		$input
			.focus()
			.keypress(function (event) {
				if (event.keyCode === 13) {
					if ($(this).val().length > 0) {
						$input.blur();
						$.ajax({
							type: 'PUT',
							url: OC.generateUrl('/settings/users/{id}/mailAddress', {id: uid}),
							data: {
								mailAddress: $(this).val()
							}
						}).fail(function (result) {
							OC.Notification.show(result.responseJSON.data.message);
							// reset the values
							$tr.data('mailAddress', mailAddress);
							$tr.children('.mailAddress').children('span').text(mailAddress);
						});
					} else {
						$input.blur();
					}
				}
			})
			.blur(function () {
				var mailAddress = $input.val();
				var $span = $('<span>').text(mailAddress);
				$tr.data('mailAddress', mailAddress);
				$input.replaceWith($span);
			});
	});

	// init the quota field select box after it is shown the first time
	$('#app-settings').one('show', function() {
		$(this).find('#default_quota').singleSelect().on('change', UserList.onQuotaSelect);
	});

	$('#newuser').submit(function (event) {
		event.preventDefault();
		var username = $('#newusername').val();
		var password = $('#newuserpassword').val();
		var email = $('#newemail').val();
		if ($.trim(username) === '') {
			OC.dialogs.alert(
				t('settings', 'A valid username must be provided'),
				t('settings', 'Error creating user'));
			return false;
		}
		if ($.trim(password) === '') {
			OC.dialogs.alert(
				t('settings', 'A valid password must be provided'),
				t('settings', 'Error creating user'));
			return false;
		}
		if(!$('#CheckboxMailOnUserCreate').is(':checked')) {
			email = '';
		}
		if ($('#CheckboxMailOnUserCreate').is(':checked') && $.trim(email) === '') {
			OC.dialogs.alert(
				t('settings', 'A valid email must be provided'),
				t('settings', 'Error creating user'));
			return false;
		}
		var groups = $('#newusergroups').val() || [];
		$.post(
			OC.generateUrl('/settings/users/users'),
			{
				username: username,
				password: password,
				groups: groups,
				email: email
			},
			function (result) {
				if (result.groups) {
					for (var i in result.groups) {
						var gid = result.groups[i];
						if(UserList.availableGroups.indexOf(gid) === -1) {
							UserList.availableGroups.push(gid);
						}
						$li = GroupList.getGroupLI(gid);
						userCount = GroupList.getUserCount($li);
						GroupList.setUserCount($li, userCount + 1);
					}
				}
				if(!UserList.has(username)) {
					UserList.add(result, true);
				}
				$('#newusername').focus();
				GroupList.incEveryoneCount();
			}).fail(function(result, textStatus, errorThrown) {
				OC.dialogs.alert(result.responseJSON.message, t('settings', 'Error creating user'));
			}).success(function(){
				$('#newuser').get(0).reset();
			});
	});

	// Option to display/hide the "Storage location" column
	$('#CheckboxStorageLocation').click(function() {
		if ($('#CheckboxStorageLocation').is(':checked')) {
			$("#userlist .storageLocation").show();
		} else {
			$("#userlist .storageLocation").hide();
		}
	});
	// Option to display/hide the "Last Login" column
	$('#CheckboxLastLogin').click(function() {
		if ($('#CheckboxLastLogin').is(':checked')) {
			$("#userlist .lastLogin").show();
		} else {
			$("#userlist .lastLogin").hide();
		}
	});
	// Option to display/hide the "Mail Address" column
	$('#CheckboxEmailAddress').click(function() {
		if ($('#CheckboxEmailAddress').is(':checked')) {
			$("#userlist .mailAddress").show();
		} else {
			$("#userlist .mailAddress").hide();
		}
	});
	// Option to display/hide the "User Backend" column
	$('#CheckboxUserBackend').click(function() {
		if ($('#CheckboxUserBackend').is(':checked')) {
			$("#userlist .userBackend").show();
		} else {
			$("#userlist .userBackend").hide();
		}
	});
	// Option to display/hide the "E-Mail" input field
	$('#CheckboxMailOnUserCreate').click(function() {
		if ($('#CheckboxMailOnUserCreate').is(':checked')) {
			$("#newemail").show();
		} else {
			$("#newemail").hide();
		}
	});

	// calculate initial limit of users to load
	var initialUserCountLimit = 20,
		containerHeight = $('#app-content').height();
	if(containerHeight > 40) {
		initialUserCountLimit = Math.floor(containerHeight/40);
		while((initialUserCountLimit % UserList.usersToLoad) !== 0) {
			// must be a multiple of this, otherwise LDAP freaks out.
			// FIXME: solve this in LDAP backend in  8.1
			initialUserCountLimit = initialUserCountLimit + 1;
		}
	}

	// trigger loading of users on startup
	UserList.update(UserList.currentGid, initialUserCountLimit);

});
