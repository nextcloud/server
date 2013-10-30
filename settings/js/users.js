/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

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

var UserList = {
	useUndo: true,
	availableGroups: [],
	offset: 30, //The first 30 users are there. No prob, if less in total.
				//hardcoded in settings/users.php

	usersToLoad: 10, //So many users will be loaded when user scrolls down

	/**
	 * @brief Initiate user deletion process in UI
	 * @param string uid the user ID to be deleted
	 *
	 * Does not actually delete the user; it sets them for
	 * deletion when the current page is unloaded, at which point
	 * finishDelete() completes the process. This allows for 'undo'.
	 */
	do_delete: function (uid) {
		if (typeof UserList.deleteUid !== 'undefined') {
			//Already a user in the undo queue
			UserList.finishDelete(null);
		}
		UserList.deleteUid = uid;

		// Set undo flag
		UserList.deleteCanceled = false;

		// Provide user with option to undo
		$('#notification').data('deleteuser', true);
		OC.Notification.showHtml(t('users', 'deleted') + ' ' + escapeHTML(uid) + '<span class="undo">' + t('users', 'undo') + '</span>');
	},

	/**
	 * @brief Delete a user via ajax
	 * @param bool ready whether to use ready() upon completion
	 *
	 * Executes deletion via ajax of user identified by property deleteUid
	 * if 'undo' has not been used.  Completes the user deletion procedure
	 * and reflects success in UI.
	 */
	finishDelete: function (ready) {

		// Check deletion has not been undone
		if (!UserList.deleteCanceled && UserList.deleteUid) {

			// Delete user via ajax
			$.ajax({
				type: 'POST',
				url: OC.filePath('settings', 'ajax', 'removeuser.php'),
				async: false,
				data: { username: UserList.deleteUid },
				success: function (result) {
					if (result.status === 'success') {
						// Remove undo option, & remove user from table
						OC.Notification.hide();
						$('tr').filterAttr('data-uid', UserList.deleteUid).remove();
						UserList.deleteCanceled = true;
						if (ready) {
							ready();
						}
					} else {
						OC.dialogs.alert(result.data.message, t('settings', 'Unable to remove user'));
					}
				}
			});
		}
	},

	add: function (username, displayname, groups, subadmin, quota, sort) {
		var tr = $('tbody tr').first().clone();
		if (tr.find('div.avatardiv')){
			$('div.avatardiv', tr).avatar(username, 32);
		}
		tr.attr('data-uid', username);
		tr.attr('data-displayName', displayname);
		tr.find('td.name').text(username);
		tr.find('td.displayName > span').text(displayname);
		var groupsSelect = $('<select multiple="multiple" class="groupsselect" data-placehoder="Groups" title="' + t('settings', 'Groups') + '"></select>')
			.attr('data-username', username)
			.data('user-groups', groups);
		tr.find('td.groups').empty();
		if (tr.find('td.subadmins').length > 0) {
			var subadminSelect = $('<select multiple="multiple" class="subadminsselect" data-placehoder="subadmins" title="' + t('settings', 'Group Admin') + '">')
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
		tr.find('td.groups').append(groupsSelect);
		UserList.applyMultiplySelect(groupsSelect);
		if (tr.find('td.subadmins').length > 0) {
			tr.find('td.subadmins').append(subadminSelect);
			UserList.applyMultiplySelect(subadminSelect);
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
		$(tr).appendTo('tbody');
		if (sort) {
			UserList.doSort();
		}

		quotaSelect.singleSelect();
		quotaSelect.on('change', function () {
			var uid = $(this).parent().parent().attr('data-uid');
			var quota = $(this).val();
			setQuota(uid, quota, function(returnedQuota){
				if (quota !== returnedQuota) {
					$(quotaSelect).find(':selected').text(returnedQuota);
				}
			});
		});
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
	update: function () {
		if (UserList.updating) {
			return;
		}
		UserList.updating = true;
		$.get(OC.Router.generate('settings_ajax_userlist', { offset: UserList.offset, limit: UserList.usersToLoad }), function (result) {
			if (result.status === 'success') {
				//The offset does not mirror the amount of users available,
				//because it is backend-dependent. For correct retrieval,
				//always the limit(requested amount of users) needs to be added.
				UserList.offset += UserList.usersToLoad;
				$.each(result.data, function (index, user) {
					if($('tr[data-uid="' + user.name + '"]').length > 0) {
						return true;
					}
					var tr = UserList.add(user.name, user.displayname, user.groups, user.subadmin, user.quota, false);
					if (index === 9) {
						$(tr).bind('inview', function (event, isInView, visiblePartX, visiblePartY) {
							$(this).unbind(event);
							UserList.update();
						});
					}
				});
				if (result.data.length > 0) {
					UserList.doSort();
				}
			}
			UserList.updating = false;
		});
	},

	applyMultiplySelect: function (element) {
		var checked = [];
		var user = element.attr('data-username');
		if ($(element).attr('class') === 'groupsselect') {
			if (element.data('userGroups')) {
				checked = element.data('userGroups');
			}
			if (user) {
				var checkHandeler = function (group) {
					if (user === OC.currentUser && group === 'admin') {
						return false;
					}
					if (!isadmin && checked.length === 1 && checked[0] === group) {
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
			};
			var label;
			if (isadmin) {
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
		if ($(element).attr('class') === 'subadminsselect') {
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
	}
};

$(document).ready(function () {

	UserList.doSort();
	UserList.availableGroups = $('#content table').data('groups');
	$('tbody tr:last').bind('inview', function (event, isInView, visiblePartX, visiblePartY) {
		OC.Router.registerLoadedCallback(function () {
			UserList.update();
		});
	});

	$('select[multiple]').each(function (index, element) {
		UserList.applyMultiplySelect($(element));
	});

	$('table').on('click', 'td.remove>a', function (event) {
		var row = $(this).parent().parent();
		var uid = $(row).attr('data-uid');
		$(row).hide();
		// Call function for handling delete/undo
		UserList.do_delete(uid);
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
						OC.Router.generate('settings_users_changepassword'),
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
			$(this).replaceWith(escapeHTML($(this).val()));
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
						UserList.add(username, username, result.data.groups, null, 'default', true);
					}
				}
			}
		);
	});
	// Handle undo notifications
	OC.Notification.hide();
	$('#notification').on('click', '.undo', function () {
		if ($('#notification').data('deleteuser')) {
			$('tbody tr').filterAttr('data-uid', UserList.deleteUid).show();
			UserList.deleteCanceled = true;
		}
		OC.Notification.hide();
	});
	UserList.useUndo = ('onbeforeunload' in window);
	$(window).bind('beforeunload', function () {
		UserList.finishDelete(null);
	});
});
