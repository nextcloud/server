/**
 * Copyright (c) 2014, Raghu Nayyar <beingminimal@gmail.com>
 * Copyright (c) 2014, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

 var GroupList = {
	addGroup: function(gid) {
		var li = $('li[data-gid]').last().clone();
		var ul = $('li[data-gid]').first().parent();
		li.attr('data-gid', gid);
		li.attr('data-usercount', 0);
		li.find('a span').first().text(gid);
		li.find('span[class=usercount]').first().text('');

		$(li).appendTo(ul);

		GroupList.sortGroups(0);
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
					}
					GroupList.toggleAddGroup();
				}
			}
		)
	},

	elementBelongsToAddGroup: function(el) {
		return !(el !== $('#newgroup-form').get(0)
				&& $('#newgroup-form').find($(el)).length === 0);
	},

	hasAddGroupNameText: function() {
		name = $('#newgroupname').val();
		if($.trim(name) === '') {
			return false;
		}
		return true;
	},

	showGroup: function (gid) {
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
				t('settings', 'A valid groupname must be provided'),
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
	initDeleteHandling: function() {
		//set up handler
		GroupDeleteHandler = new DeleteHandler('removegroup.php', 'groupname',
											  GroupList.hide, GroupList.remove);

		//configure undo
		OC.Notification.hide();
		msg = t('settings', 'deleted') + ' %oid <span class="undo">' +
			  t('settings', 'undo') + '</span>';
		GroupDeleteHandler.setNotification(OC.Notification, 'deletegroup', msg,
										  GroupList.show);

		//when to mark user for delete
		$('ul').on('click', 'span.utils>a', function (event) {
			// Call function for handling delete/undo
			gid = $(this).parent().parent().attr('data-gid');
			GroupDeleteHandler.mark(gid);
		});

		console.log('init del groups');
		//delete a marked user when leaving the page
		$(window).on('beforeunload', function () {
			GroupDeleteHandler.delete();
		});
	},
}

$(document).ready( function () {
	GroupList.initDeleteHandling();

	// Display or hide of Create Group List Element
	$('#newgroup-form').hide();
	$('#newgroup-init').on('click', function (e) {
		GroupList.toggleAddGroup(e);
	});

	$(document).on('click keydown keyup', function(event) {
		if(!GroupList.isAddGroupButtonVisible()
			&& !GroupList.elementBelongsToAddGroup(event.target)
		    && !GroupList.hasAddGroupNameText()) {
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
	$('ul').on('click', 'li[data-gid]', function (event) {
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
	$('#app-navigation').find('.settings-button').on('click', function (e) {
		$('.settings-button').addClass('opened');
		var settings = $('#app-settings');
		e.stopPropagation();
		settings.animate({height: "100px"});
		$('#app-settings-content').css('display', 'block');
		$(document).click(function (e) {
			if (!settings.is(e.target) && settings.has(e.target).length === 0) {
				settings.animate({height: "45px"});
			}
		});
	});
});