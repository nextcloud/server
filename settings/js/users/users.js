/**
 * Copyright (c) 2014, Arthur Schiwon <blizzz@owncloud.com>
 * Copyright (c) 2014, Raghu Nayyar <beingminimal@gmail.com>
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

var UserList = {
	availableGroups: [],
	offset: 30, //The first 30 users are there. No prob, if less in total.
				//hardcoded in settings/users.php

	usersToLoad: 10, //So many users will be loaded when user scrolls down
	currentGid: '',

	add: function (username, displayname, groups, subadmin, quota, storageLocation, lastLogin, sort) {
		var tr = $('tbody tr').first().clone();
		var subadminsEl;
		var subadminSelect;
		var groupsSelect;
		if (tr.find('div.avatardiv').length){
			$('div.avatardiv', tr).avatar(username, 32);
		}
		tr.attr('data-uid', username);
		tr.attr('data-displayName', displayname);
		tr.find('td.name').text(username);
		tr.find('td.displayName > span').text(displayname);

		// make them look like the multiselect buttons
		// until they get time to really get initialized
		groupsSelect = $('<select multiple="multiple" class="groupsselect multiselect button" data-placehoder="Groups" title="' + t('settings', 'Groups') + '"></select>')
			.attr('data-username', username)
			.data('user-groups', groups);
		if (tr.find('td.subadmins').length > 0) {
			subadminSelect = $('<select multiple="multiple" class="subadminsselect multiselect button" data-placehoder="subadmins" title="' + t('settings', 'Group Admin') + '">')
				.attr('data-username', username)
				.data('user-groups', groups)
				.data('subadmin', subadmin);
			tr.find('td.subadmins').empty();
		}
		$.each(this.availableGroups, function (i, group) {
			groupsSelect.append($('<option value="' + escapeHTML(group) + '">' + escapeHTML(group) + '</option>'));
			if (typeof subadminSelect !== 'undefined' && group !== 'admin') {
				subadminSelect.append($('<option value="' + escapeHTML(group) + '">' + escapeHTML(group) + '</option>'));
			}
		});
		tr.find('td.groups').empty().append(groupsSelect);
		subadminsEl = tr.find('td.subadmins');
		if (subadminsEl.length > 0) {
			subadminsEl.append(subadminSelect);
		}
		if (tr.find('td.remove img').length === 0 && OC.currentUser !== username) {
			var rm_img = $('<img class="svg action">').attr({
				src: OC.imagePath('core', 'actions/delete')
			});
			var rm_link = $('<a class="action delete">')
				.attr({ href: '#', 'original-title': t('settings', 'Delete')})
				.append(rm_img);
			tr.find('td.remove').append(rm_link);
		} else if (OC.currentUser === username) {
			tr.find('td.remove a').remove();
		}
		var quotaSelect = tr.find('select.quota-user');
		if (quota === 'default') {
			quotaSelect.find('option').attr('selected', null);
			quotaSelect.find('option').first().attr('selected', 'selected');
			quotaSelect.data('previous', 'default');
		} else {
			if (quotaSelect.find('option[value="' + quota + '"]').length > 0) {
				quotaSelect.find('option[value="' + quota + '"]').attr('selected', 'selected');
			} else {
				quotaSelect.append('<option value="' + escapeHTML(quota) + '" selected="selected">' + escapeHTML(quota) + '</option>');
			}
		}
		tr.find('td.storageLocation').text(storageLocation);
		if(lastLogin === 0) {
			lastLogin = t('settings', 'never');
		} else {
			lastLogin = new Date(lastLogin);
			lastLogin = relative_modified_date(lastLogin.getTime() / 1000);
		}
		tr.find('td.lastLogin').text(lastLogin);
		$(tr).appendTo('tbody');
		if(UserList.isEmpty === true) {
			//when the list was emptied, one row was left, necessary to keep
			//add working and the layout unbroken. We need to remove this item
			tr.show();
			$('tbody tr').first().remove();
			UserList.isEmpty = false;
			UserList.checkUsersToLoad();
		}
		if (sort) {
			UserList.doSort();
		}

		quotaSelect.on('change', function () {
			var uid = $(this).parent().parent().attr('data-uid');
			var quota = $(this).val();
			setQuota(uid, quota, function(returnedQuota){
				if (quota !== returnedQuota) {
					$(quotaSelect).find(':selected').text(returnedQuota);
				}
			});
		});

		// defer init so the user first sees the list appear more quickly
		window.setTimeout(function(){
			quotaSelect.singleSelect();
			UserList.applyMultiplySelect(groupsSelect);
			if (subadminSelect) {
				UserList.applyMultiplySelect(subadminSelect);
			}
		}, 0);
		return tr;
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

		for (x = 0; aa[x] && bb[x]; x++) {
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
	doSort: function() {
		var self = this;
		var rows = $('tbody tr').get();

		rows.sort(function(a, b) {
			return UserList.alphanum($(a).find('td.name').text(), $(b).find('td.name').text());
		});

		var items = [];
		$.each(rows, function(index, row) {
			items.push(row);
			if(items.length === 100) {
				$('tbody').append(items);
				items = [];
			}
		});
		if(items.length > 0) {
			$('tbody').append(items);
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
		$('tbody tr:not(:first)').remove();
		tr = $('tbody tr').first();
		tr.hide();
		//on an update a user may be missing when the username matches with that
		//of the hidden row. So change this to a random string.
		tr.attr('data-uid', Math.random().toString(36).substring(2));
		UserList.isEmpty = true;
		UserList.offset = 0;
		UserList.checkUsersToLoad();
	},
	hide: function(uid) {
		$('tr[data-uid="' + uid + '"]').hide();
	},
	show: function(uid) {
		$('tr[data-uid="' + uid + '"]').show();
	},
	remove: function(uid) {
		$('tr').filterAttr('data-uid', uid).remove();
	},
	initDeleteHandling: function() {
		//set up handler
		UserDeleteHandler = new DeleteHandler('removeuser.php', 'username',
											  UserList.hide, UserList.remove);

		//configure undo
		OC.Notification.hide();
		msg = t('settings', 'deleted') + ' %oid <span class="undo">' +
			  t('settings', 'undo') + '</span>';
		UserDeleteHandler.setNotification(OC.Notification, 'deleteuser', msg,
										  UserList.show);

		//when to mark user for delete
		$('table').on('click', 'td.remove>a', function (event) {
			// Call function for handling delete/undo
			uid = $(this).parent().parent().attr('data-uid');
			UserDeleteHandler.mark(uid);
		});

		//delete a marked user when leaving the page
		console.log('init del users');
		$(window).on('beforeunload', function () {
			UserDeleteHandler.delete();
		});
	},
	update: function (gid) {
		if (UserList.updating) {
			return;
		}
		$('table+.loading').css('visibility', 'visible');
		UserList.updating = true;
		if(gid === undefined) {
			gid = '';
		}
		UserList.currentGid = gid;
		pattern = filter.getPattern();
		var query = $.param({ offset: UserList.offset, limit: UserList.usersToLoad, gid: gid, pattern: pattern });
		$.get(OC.generateUrl('/settings/ajax/userlist') + '?' + query, function (result) {
			var loadedUsers = 0;
			var trs = [];
			if (result.status === 'success') {
				//The offset does not mirror the amount of users available,
				//because it is backend-dependent. For correct retrieval,
				//always the limit(requested amount of users) needs to be added.
				$.each(result.data, function (index, user) {
					if($('tr[data-uid="' + user.name + '"]').length > 0) {
						return true;
					}
					var tr = UserList.add(user.name, user.displayname, user.groups, user.subadmin, user.quota, user.storageLocation, user.lastLogin, false);
					tr.addClass('appear transparent');
					trs.push(tr);
					loadedUsers++;
				});
				if (result.data.length > 0) {
					UserList.doSort();
					$('table+.loading').css('visibility', 'hidden');
				}
				else {
					UserList.noMoreEntries = true;
					$('table+.loading').remove();
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

	applyMultiplySelect: function (element) {
		var checked = [];
		var user = element.attr('data-username');
		if ($(element).hasClass('groupsselect')) {
			if (element.data('userGroups')) {
				checked = element.data('userGroups');
			}
			if (user) {
				var checkHandeler = function (group) {
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
							if(response.status === 'success'
									&& UserList.availableGroups.indexOf(response.data.groupname) === -1
									&& response.data.action === 'add') {
								UserList.availableGroups.push(response.data.groupname);
							}
							if(response.data.message) {
								OC.Notification.show(response.data.message);
							}
						}
					);
				};
			} else {
				checkHandeler = false;
			}
			var addGroup = function (select, group) {
				$('select[multiple]').each(function (index, element) {
					if ($(element).find('option[value="' + group + '"]').length === 0 && select.data('msid') !== $(element).data('msid')) {
						$(element).append('<option value="' + escapeHTML(group) + '">' + escapeHTML(group) + '</option>');
					}
				});
				GroupList.addGroup(escapeHTML(group));
			};
			var label;
			if (oc_isadmin) {
				label = t('settings', 'add group');
			} else {
				label = null;
			}
			element.multiSelect({
				createCallback: addGroup,
				createText: label,
				selectedFirst: true,
				checked: checked,
				oncheck: checkHandeler,
				onuncheck: checkHandeler,
				minWidth: 100
			});
		}
		if ($(element).hasClass('subadminsselect')) {
			if (element.data('subadmin')) {
				checked = element.data('subadmin');
			}
			var checkHandeler = function (group) {
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
					if ($(element).find('option[value="' + group + '"]').length === 0) {
						$(element).append('<option value="' + escapeHTML(group) + '">' + escapeHTML(group) + '</option>');
					}
				});
			};
			element.multiSelect({
				createCallback: addSubAdmin,
				createText: null,
				checked: checked,
				oncheck: checkHandeler,
				onuncheck: checkHandeler,
				minWidth: 100
			});
		}
	},

	_onScroll: function(e) {
		if (!!UserList.noMoreEntries) {
			return;
		}
		if (UserList.scrollArea.scrollTop() + UserList.scrollArea.height() > UserList.scrollArea.get(0).scrollHeight - 500) {
			UserList.update(UserList.currentGid, true);
		}
	},
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
	UserList.initDeleteHandling();

	UserList.doSort();
	UserList.availableGroups = $('#content table').data('groups');


	UserList.scrollArea = $('#app-content');
	UserList.scrollArea.scroll(function(e) {UserList._onScroll(e);});


	$('table').after($('<div class="loading" style="height: 200px; visibility: hidden;"></div>'));

	$('select[multiple]').each(function (index, element) {
		UserList.applyMultiplySelect($(element));
	});



	$('table').on('click', 'td.password>img', function (event) {
		event.stopPropagation();
		var img = $(this);
		var uid = img.parent().parent().attr('data-uid');
		var input = $('<input type="password">');
		img.css('display', 'none');
		img.parent().children('span').replaceWith(input);
		input.focus();
		input.keypress(function (event) {
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
					input.blur();
				} else {
					input.blur();
				}
			}
		});
		input.blur(function () {
			$(this).replaceWith($('<span>●●●●●●●</span>'));
			img.css('display', '');
		});
	});
	$('input:password[id="recoveryPassword"]').keyup(function(event) {
		OC.Notification.hide();
	});

	$('table').on('click', 'td.password', function (event) {
		$(this).children('img').click();
	});

	$('table').on('click', 'td.displayName>img', function (event) {
		event.stopPropagation();
		var img = $(this);
		var uid = img.parent().parent().attr('data-uid');
		var displayName = escapeHTML(img.parent().parent().attr('data-displayName'));
		var input = $('<input type="text" value="' + displayName + '">');
		img.css('display', 'none');
		img.parent().children('span').replaceWith(input);
		input.focus();
		input.keypress(function (event) {
			if (event.keyCode === 13) {
				if ($(this).val().length > 0) {
					$.post(
						OC.filePath('settings', 'ajax', 'changedisplayname.php'),
						{username: uid, displayName: $(this).val()},
						function (result) {
							if (result && result.status==='success'){
								img.parent().parent().find('div.avatardiv').avatar(result.data.username, 32);
							}
						}
					);
					input.blur();
				} else {
					input.blur();
				}
			}
		});
		input.blur(function () {
			var input = $(this),
				displayName = input.val();
			input.closest('tr').attr('data-displayName', displayName);
			input.replaceWith('<span>' + escapeHTML(displayName) + '</span>');
			img.css('display', '');
		});
	});
	$('table').on('click', 'td.displayName', function (event) {
		$(this).children('img').click();
	});

	$('select.quota, select.quota-user').singleSelect().on('change', function () {
		var select = $(this);
		var uid = $(this).parent().parent().attr('data-uid');
		var quota = $(this).val();
		setQuota(uid, quota, function(returnedQuota){
			if (quota !== returnedQuota) {
				select.find(':selected').text(returnedQuota);
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
		var groups = $('#newusergroups').prev().children('div').data('settings').checked;
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
					if($('tr[data-uid="' + username + '"]').length === 0) {
						UserList.add(username, username, result.data.groups, null, 'default', result.data.storageLocation, 0, true);
					}
				}
			}
		);
	});
	// Implements User Search
	filter = new UserManagementFilter($('#usersearchform input'), UserList);
});
