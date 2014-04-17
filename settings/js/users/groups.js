/**
 * Copyright (c) 2014, Raghu Nayyar <beingminimal@gmail.com>
 * Copyright (c) 2014, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

 var GroupList = {
	activeGID: '',

	addGroup: function(gid, usercount) {
		var li = $('li[data-gid]').last().clone();
		var ul = $('li[data-gid]').first().parent();
		li.attr('data-gid', gid);
		li.find('a span').first().text(gid);
		GroupList.setUserCount(li, usercount);

		$(li).appendTo(ul);

		GroupList.sortGroups(0);

		return li;
	},

	setUserCount: function(groupLiElement, usercount) {
		if(usercount === undefined || usercount === 0) {
			usercount = '';
		}
		groupLiElement.attr('data-usercount', usercount);
		groupLiElement.find('span[class=usercount]').first().text(usercount);
	},

	getCurrentGID: function() {
		return GroupList.activeGID;
	},

	sortGroups: function(usercount) {
		var lis = $('li[data-gid]').filterAttr('data-usercount', usercount.toString()).get();
		var ul = $(lis).first().parent();

		lis.sort(function(a, b) {
			return UserList.alphanum($(a).find('a span').text(), $(b).find('a span').text());
		});

		var items = [];
		$.each(lis, function(index, li) {
			items.push(li);
			if(items.length === 100) {
				$(ul).append(items);
				items = [];
			}
		});
		if(items.length > 0) {
			$(ul).append(items);
		}
	},

	createGroup: function(groupname) {
		$.post(
			OC.filePath('settings', 'ajax', 'creategroup.php'),
			{
				groupname : groupname
			},
			function (result) {
				if (result.status !== 'success') {
					OC.dialogs.alert(result.data.message,
						t('settings', 'Error creating group'));
				} else {
					if (result.data.groupname) {
						var addedGroups = result.data.groupname;
						UserList.availableGroups = $.unique($.merge(UserList.availableGroups, addedGroups));
						GroupList.addGroup(result.data.groupname);

						$('#newusergroups').children().first().attr('value', result.data.groupname);
						$('#newusergroups').children().first().text(result.data.groupname);

						$('.groupsselect').each( function (index, element) {
							$(element).children().first().attr('value', result.data.groupname);
							$(element).children().first().text(result.data.groupname);
						});

						$('.subadminsselect').each( function (index, element) {
							$(element).children().first().attr('value', result.data.groupname);
							$(element).children().first().text(result.data.groupname);
						});
					}
					GroupList.toggleAddGroup();
				}
			}
		);
	},

	update: function() {
		if (GroupList.updating) {
			return;
		}
		GroupList.updating = true;
		var pattern = filter.getPattern();
		var query = $.param({ pattern: pattern });
		$.get(OC.generateUrl('/settings/ajax/grouplist') + '?' + query, function (result) {
			var lis = [];
			if (result.status === 'success') {
				$.each(result.data, function (i, subset) {
					$.each(subset, function (index, group) {
						if($('li[data-gid="' + group.name + '"]').length > 0) {
							var li = $('li[data-gid="' + group.name + '"]');
							GroupList.setUserCount(li, group.usercount);
							return true;
						}
						var li = GroupList.addGroup(group.name, group.usercount);
						li.addClass('appear transparent');
						lis.push(li);
					});
				});
				if (result.data.length > 0) {
					GroupList.doSort();
				} else {
					GroupList.noMoreEntries = true;
				}
				setTimeout(function() {
					for (var i = 0; i < lis.length; i++) {
						lis[i].removeClass('transparent');
					}
				}, 0);
			}
			GroupList.updating = false;
		});
	},

	elementBelongsToAddGroup: function(el) {
		return !(el !== $('#newgroup-form').get(0) &&
				$('#newgroup-form').find($(el)).length === 0);
	},

	hasAddGroupNameText: function() {
		var name = $('#newgroupname').val();
		if($.trim(name) === '') {
			return false;
		}
		return true;
	},

	showGroup: function (gid) {
		GroupList.activeGID = gid;
		UserList.empty();
		UserList.update(gid);
		$('#app-navigation li').removeClass('active');
		if(gid !== undefined) {
			//TODO: treat Everyone properly
			$('#app-navigation li').filterAttr('data-gid', gid).addClass('active');
		}
	},

	isAddGroupButtonVisible: function() {
		return $('#newgroup-init').is(":visible");
	},

	toggleAddGroup: function(event) {
		if(GroupList.isAddGroupButtonVisible()) {
			event.stopPropagation();
			$('#newgroup-form').show();
			$('#newgroup-init').hide();
			$('#newgroupname').focus();
		} else {
			$('#newgroup-form').hide();
			$('#newgroup-init').show();
			$('#newgroupname').val('');
		}
	},

	isGroupNameValid: function(groupname) {
		if ($.trim(groupname) === '') {
			OC.dialogs.alert(
				t('settings', 'A valid group name must be provided'),
				t('settings', 'Error creating group'));
			return false;
		}
		return true;
	},

	hide: function(gid) {
		$('li[data-gid="' + gid + '"]').hide();
	},
	show: function(gid) {
		$('li[data-gid="' + gid + '"]').show();
	},
	remove: function(gid) {
		$('li').filterAttr('data-gid', gid).remove();
	},
	empty: function() {
		$('li:not([data-gid=""])').remove();
	},
	initDeleteHandling: function() {
		//set up handler
		GroupDeleteHandler = new DeleteHandler('removegroup.php', 'groupname',
											GroupList.hide, GroupList.remove);

		//configure undo
		OC.Notification.hide();
		var msg = t('settings', 'deleted') + ' %oid <span class="undo">' +
			t('settings', 'undo') + '</span>';
		GroupDeleteHandler.setNotification(OC.Notification, 'deletegroup', msg,
										GroupList.show);

		//when to mark user for delete
		$('ul').on('click', 'span.utils>a', function () {
			// Call function for handling delete/undo
			var gid = $(this).parent().parent().attr('data-gid');
			GroupDeleteHandler.mark(gid);
		});

		console.log('init del groups');
		//delete a marked user when leaving the page
		$(window).on('beforeunload', function () {
			GroupDeleteHandler.delete();
		});
	}
};

$(document).ready( function () {
	GroupList.initDeleteHandling();

	// Display or hide of Create Group List Element
	$('#newgroup-form').hide();
	$('#newgroup-init').on('click', function (e) {
		GroupList.toggleAddGroup(e);
	});

	$(document).on('click keydown keyup', function(event) {
		if(!GroupList.isAddGroupButtonVisible() &&
			!GroupList.elementBelongsToAddGroup(event.target) &&
			!GroupList.hasAddGroupNameText()) {
			GroupList.toggleAddGroup();
		}
		// Escape
		if(!GroupList.isAddGroupButtonVisible() && event.keyCode && event.keyCode === 27) {
			GroupList.toggleAddGroup();
		}
	});


	// Responsible for Creating Groups.
	$('#newgroup-form form').submit(function (event) {
		event.preventDefault();
		if(GroupList.isGroupNameValid($('#newgroupname').val())) {
			GroupList.createGroup($('#newgroupname').val());
		}
	});

	// click on group name
	// FIXME: also triggered when clicking on "remove"
	$('ul').on('click', 'li[data-gid]', function () {
		var li = $(this);
		var gid = $(li).attr('data-gid');
		GroupList.showGroup(gid);
	});

	// Implements Groupname editing.
	$('#app-navigation').on('click', 'img.rename', function (event) {
		event.stopPropagation();
		var img = $(this);
		var gid = img.parent().parent().attr('data-gid');
		var groupname = escapeHTML(img.parent().parent().attr('data-gid'));
		var input = $('<input type="text" value="' + groupname + '">');
		img.css('display', 'none');
		img.parent().children('span').replaceWith(input);
		input.focus();
		input.keypress(function (event) {
			if (event.keyCode === 13) {
				if ($(this).val().length > 0) {
					$.post(
						OC.filePath('settings', 'ajax', 'changegroupname.php'),
						{	groupname: gid,
							groupname: $(this).val()
						}
					);
					input.blur();
				} else {
					input.blur();
				}
			}
		});
		input.blur(function () {
			var input = $(this), groupname = input.val();
			input.closest('li').attr('data-gid', groupname);
			input.replaceWith('<span>' + escapeHTML(groupname) + '</span>');
			img.css('display', '');
		});
	});

	// Implements Quota Settings Toggle.
	$('#app-settings-header').on('click keydown',function(event) {
		if(wrongKey(event)) {
			return;
		}
		var bodyListener = function(e) {
			if($('#app-settings').find($(e.target)).length === 0) {
				$('#app-settings').switchClass('open', '');
			}
		};
		if($('#app-settings').hasClass('open')) {
			$('#app-settings').switchClass('open', '');
			$('body').unbind('click', bodyListener);
		} else {
			$('#app-settings').switchClass('', 'open');
			$('body').bind('click', bodyListener);
		}
	});
});

var wrongKey = function(event) {
	return ((event.type === 'keydown' || event.type === 'keypress') &&
		(event.keyCode !== 32 && event.keyCode !== 13));
};
