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
	offset: 30, //The first 30 users are there. No prob, if less in total.
				//hardcoded in settings/users.php

	usersToLoad: 10, //So many users will be loaded when user scrolls down
	currentGid: '',

	add: function (username, displayname, groups, subadmin, quota, storageLocation, lastLogin, sort) {
		var $tr = $userListBody.find('tr:first-child').clone();
		var subAdminsEl;
		var subAdminSelect;
		var groupsSelect;
		if ($tr.find('div.avatardiv').length){
			$tr.find('.avatardiv').imageplaceholder(username, displayname);
			$('div.avatardiv', $tr).avatar(username, 32);
		}
		$tr.data('uid', username);
		$tr.data('displayname', displayname);
		$tr.find('td.name').text(username);
		$tr.find('td.displayName > span').text(displayname);

		// make them look like the multiselect buttons
		// until they get time to really get initialized
		groupsSelect = $('<select multiple="multiple" class="groupsselect multiselect button" data-placehoder="Groups" title="' + t('settings', 'Groups') + '"></select>')
			.data('username', username)
			.data('user-groups', groups);
		if ($tr.find('td.subadmins').length > 0) {
			subAdminSelect = $('<select multiple="multiple" class="subadminsselect multiselect button" data-placehoder="subadmins" title="' + t('settings', 'Group Admin') + '">')
				.data('username', username)
				.data('user-groups', groups)
				.data('subadmin', subadmin);
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
		if ($tr.find('td.remove img').length === 0 && OC.currentUser !== username) {
			var deleteImage = $('<img class="svg action">').attr({
				src: OC.imagePath('core', 'actions/delete')
			});
			var deleteLink = $('<a class="action delete">')
				.attr({ href: '#', 'original-title': t('settings', 'Delete')})
				.append(deleteImage);
			$tr.find('td.remove').append(deleteLink);
		} else if (OC.currentUser === username) {
			$tr.find('td.remove a').remove();
		}
		var $quotaSelect = $tr.find('.quota-user');
		if (quota === 'default') {
			$quotaSelect
				.data('previous', 'default')
				.find('option').attr('selected', null)
				.first().attr('selected', 'selected');
		} else {
			if ($quotaSelect.find('option').filterAttr('value', quota).length > 0) {
				$quotaSelect.find('option').filterAttr('value', quota).attr('selected', 'selected');
			} else {
				$quotaSelect.append('<option value="' + escapeHTML(quota) + '" selected="selected">' + escapeHTML(quota) + '</option>');
			}
		}
		$tr.find('td.storageLocation').text(storageLocation);

		var lastLoginRel = t('settings', 'never');
		var lastLoginAbs = lastLoginRel;
		if(lastLogin !== 0) {
			lastLogin = new Date(lastLogin * 1000);
			lastLoginRel = relative_modified_date(lastLogin.getTime() / 1000);
			lastLoginAbs = formatDate(lastLogin.getTime());
		}
		var $tdLastLogin = $tr.find('td.lastLogin');
		$tdLastLogin.text(lastLoginRel);
		//tooltip makes it complicated … to not insert new HTML, we adjust the
		//original title. We use a temporary div to get back the html that we
		//can pass later. It is also required to initialise tipsy.
		var tooltip = $('<div>').html($($tdLastLogin.attr('original-title')).text(lastLoginAbs)).html();
		$tdLastLogin.tipsy({gravity:'s', fade:true, html:true});
		$tdLastLogin.attr('title', tooltip);
		$tr.appendTo($userList);
		if(UserList.isEmpty === true) {
			//when the list was emptied, one row was left, necessary to keep
			//add working and the layout unbroken. We need to remove this item
			$tr.show();
			$userListBody.find('tr:first').remove();
			UserList.isEmpty = false;
			UserList.checkUsersToLoad();
		}
		if (sort) {
			UserList.doSort();
		}

		$quotaSelect.on('change', function () {
			var uid = UserList.getUID(this);
			var quota = $(this).val();
			setQuota(uid, quota, function(returnedQuota){
				if (quota !== returnedQuota) {
					$($quotaSelect).find(':selected').text(returnedQuota);
				}
			});
		});

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
			a = $(a).find('td.name').text();
			b = $(b).find('td.name').text();
			var firstSort = UserList.preSortSearchString(a, b);
			if(typeof firstSort !== 'undefined') {
				return firstSort;
			}
			return UserList.alphanum(a, b);
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
	initDeleteHandling: function() {
		//set up handler
		UserDeleteHandler = new DeleteHandler('removeuser.php', 'username',
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
			UserDeleteHandler.delete();
		});
	},
	update: function (gid) {
		if (UserList.updating) {
			return;
		}
		$userList.siblings('.loading').css('visibility', 'visible');
		UserList.updating = true;
		if(gid === undefined) {
			gid = '';
		}
		UserList.currentGid = gid;
		var pattern = filter.getPattern();
		$.get(
			OC.generateUrl('/settings/ajax/userlist'),
			{ offset: UserList.offset, limit: UserList.usersToLoad, gid: gid, pattern: pattern },
			function (result) {
				var loadedUsers = 0;
				var trs = [];
				if (result.status === 'success') {
					//The offset does not mirror the amount of users available,
					//because it is backend-dependent. For correct retrieval,
					//always the limit(requested amount of users) needs to be added.
					$.each(result.data, function (index, user) {
						if(UserList.has(user.name)) {
							return true;
						}
						var $tr = UserList.add(user.name, user.displayname, user.groups, user.subadmin, user.quota, user.storageLocation, user.lastLogin, false);
						$tr.addClass('appear transparent');
						trs.push($tr);
						loadedUsers++;
					});
					if (result.data.length > 0) {
						UserList.doSort();
						$userList.siblings('.loading').css('visibility', 'hidden');
					}
					else {
						UserList.noMoreEntries = true;
						$userList.siblings('.loading').remove();
					}
					UserList.offset += loadedUsers;
					// animate
					setTimeout(function() {
						for (var i = 0; i < trs.length; i++) {
							trs[i].removeClass('transparent');
						}
					}, 0);
				}
				UserList.updating = false;
			});
	},

	applyGroupSelect: function (element) {
		var checked = [];
		var $element = $(element);
		var user = UserList.getUID($element);

		if ($element.data('user-groups')) {
			if (typeof $element.data('user-groups') == 'string') {
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
			if (typeof $element.data('subadmin') == 'string') {
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
			UserList.update(UserList.currentGid, true);
		}
	}
};

function setQuota (uid, quota, ready) {
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

	$('.groupsselect').each(function (index, element) {
		UserList.applyGroupSelect(element);
	});
	$('.subadminsselect').each(function (index, element) {
		UserList.applySubadminSelect(element);
	});

	$userListBody.on('click', '.password', function (event) {
		event.stopPropagation();

		var $td = $(this).closest('td');
		var uid = UserList.getUID($td);
		var $input = $('<input type="password">');
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
						$tr.find('.avatardiv').imageplaceholder(uid, displayName);
						$.post(
							OC.filePath('settings', 'ajax', 'changedisplayname.php'),
							{username: uid, displayName: $(this).val()},
							function (result) {
								if (result && result.status==='success'){
									$tr.find('.avatardiv').avatar(result.data.username, 32);
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

	$('#default_quota, .quota-user').singleSelect().on('change', function () {
		var $select = $(this);
		var uid = UserList.getUID($select);
		var quota = $select.val();
		setQuota(uid, quota, function(returnedQuota){
			if (quota !== returnedQuota) {
				$select.find(':selected').text(returnedQuota);
			}
		});
	});

	$('#newuser').submit(function (event) {
		event.preventDefault();
		var username = $('#newusername').val();
		var password = $('#newuserpassword').val();
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
		var groups = $('#newusergroups').val();
		$('#newuser').get(0).reset();
		$.post(
			OC.filePath('settings', 'ajax', 'createuser.php'),
			{
				username: username,
				password: password,
				groups: groups
			},
			function (result) {
				if (result.status !== 'success') {
					OC.dialogs.alert(result.data.message,
						t('settings', 'Error creating user'));
				} else {
					if (result.data.groups) {
						var addedGroups = result.data.groups;
						UserList.availableGroups = $.unique($.merge(UserList.availableGroups, addedGroups));
						for (var i in result.data.groups) {
							var gid = result.data.groups[i];
							$li = GroupList.getGroupLI(gid);
							userCount = GroupList.getUserCount($li);
							GroupList.setUserCount($li, userCount + 1);
						}
					}
					if (result.data.homeExists){
						OC.Notification.hide();
						OC.Notification.show(t('settings', 'Warning: Home directory for user "{user}" already exists', {user: result.data.username}));
						if (UserList.notificationTimeout){
							window.clearTimeout(UserList.notificationTimeout);
						}
						UserList.notificationTimeout = window.setTimeout(
							function(){
								OC.Notification.hide();
								UserList.notificationTimeout = null;
							}, 10000);
					}
					if(!UserList.has(username)) {
						UserList.add(username, username, result.data.groups, null, 'default', result.data.storageLocation, 0, true);
					}
					$('#newusername').focus();
					GroupList.incEveryoneCount();
				}
			}
		);
	});

});
