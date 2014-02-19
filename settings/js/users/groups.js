/**
 * Copyright (c) 2014, Raghu Nayyar <beingminimal@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

 var GroupList = {

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
	$('#newgroup').submit(function (event) {
		event.preventDefault();
		var groupname = $('#newgroupname').val();
		if ($.trim(groupname) === '') {
			OC.dialogs.alert(
				t('settings', 'A valid groupname must be provided'),
				t('settings', 'Error creating group'));
			return false;
		}
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
					}
					if (result.data.homeExists){
						OC.Notification.hide();
						OC.Notification.show(t('settings', 'Warning: Home directory for user "{group}" already exists', {group: result.data.groupname}));
						if (UserList.notificationTimeout){
							window.clearTimeout(UserList.notificationTimeout);
						}
						UserList.notificationTimeout = window.setTimeout(
							function(){
								OC.Notification.hide();
								UserList.notificationTimeout = null;
							}, 10000);
					}
				}

			}
		)
	});
	// Implements Groupname editing.
	$('#app-navigation').on('click', 'span.utils>img.rename', function (event) {
		event.stopPropagation();
		var img = $(this);
		var gid = img.parent().parent().attr('data-gid');
		var groupname = escapeHTML(img.parent().parent().attr('data-gid'));
		var input = $('<input type="text" value="' + groupname + '">');
		img.css('display', 'none');
		img.parent().parent().children('a').replaceWith(input);
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
			input.replaceWith('<a href="#">' + escapeHTML(groupname) + '</a>');
			img.css('display', '');
		});
	});

});