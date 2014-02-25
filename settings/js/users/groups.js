/**
 * Copyright (c) 2014, Raghu Nayyar <beingminimal@gmail.com>
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

	delete_group: function (gid) {
		if(GroupList.deleteGid !=='undefined') {
			GroupList.finishDelete(null);
		}

		//Set the undo flag
		GroupList.deleteCanceled = false;

		//Provide an option to undo
		$('#notification').data('deletegroup', true);
		OC.Notification.showHtml(t('settings', 'deleted') + ' ' + escapeHTML(gid) + '<span class="undo">' + t('settings', 'undo') + '</span>');
	},

	elementBelongsToAddGroup: function(el) {
		return !(el !== $('#newgroup-form').get(0)
				&& $('#newgroup-form').find($(el)).length === 0);
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

	finishDelete: function (ready) {
		if (!GroupList.deleteCanceled && GroupList.deleteGid) {
			$.ajax({
				type: 'POST',
				url: OC.filePath('settings', 'ajax', 'removegroup.php'),
				async: false,
				data: { groupname: GroupList.deleteGid },
				success: function (result) {
					if (result.status === 'success') {
						// Remove undo option, & remove user from table
						OC.Notification.hide();
						$('li').filterAttr('data-gid', GroupList.deleteGid).remove();
						GroupList.deleteCanceled = true;
						if (ready) {
							ready();
						}
					} else {
						OC.dialogs.alert(result.data.message, t('settings', 'Unable to remove group'));
					}
				}
			});
		}

	},

}

$(document).ready( function () {
	$('ul').on('click', 'span.utils>a', function (event) {
		var li = $(this).parent().parent();
		var gid = $(li).attr('data-gid');
		$(li).hide();
		// Call function for handling delete/undo on Groups
		GroupList.delete_group(gid);
	});

	// Display or hide of Create Group List Element
	$('#newgroup-form').hide();
	$('#newgroup-init').on('click', function (e) {
		GroupList.toggleAddGroup(e);
	});

	$(document).on('click keydown keyup', function(event) {
		if(!GroupList.isAddGroupButtonVisible()
			&& !GroupList.elementBelongsToAddGroup(event.target)) {
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
		// Call function for handling delete/undo on Groups
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
		e.stopPropagation();
		$('#app-settings').removeClass('open');
		$('#app-settings').toggleClass('open');
		$(document).click(function() {
			$('#app-settings').removeClass('open');
    	});
	});
});