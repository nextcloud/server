News={
	DropDownMenu: {
		fade:function(menu){
			var list = $(menu).toggle();
			return false;
		},
		dropdown:function(button){
			var list = $(button).parent().find('ul#dropdownmenu');
			if (list.css('display') == 'none')
				list.slideDown('fast').show();
			else
				list.slideUp('fast');

			return false;
		},
		selectItem:function(item, folderid){
			var parent = $(item).parent().parent();
			parent.find('#dropdownBtn').text($(item).text());
			parent.find(':input[name="folderid"]').val(folderid);
			parent.find('ul#dropdownmenu').slideUp('fast');
		}
	},
	UI: {
		overview:function(dialogtype, dialogfile){
		    	if($(dialogtype).dialog('isOpen') == true){
				$(dialogtype).dialog('moveToTop');
			}else{
				$('#dialog_holder').load(OC.filePath('news', 'ajax', dialogfile), function(jsondata){
					if(jsondata.status != 'error'){
						$(dialogtype).dialog({
							dialogClass:'dialog',
							minWidth: 600,
							close: function(event, ui) {
								$(this).dialog('destroy').remove();
							}
						}).css('overflow','visible');
					} else {
						alert(jsondata.data.message);
					}
				});
			}
			return false;
		}
	},
	Folder: {
		submit:function(button){
			var displayname = $("#folder_add_name").val().trim();

			if(displayname.length == 0) {
				OC.dialogs.alert(t('news', 'Displayname cannot be empty.'), t('news', 'Error'));
				return false;
			}

			$(button).attr("disabled", true);
			//translation here!!!
			$(button).prop('value', 'Adding...');

			var folderid = $('#inputfolderid:input[name="folderid"]').val();

			var url;
			url = OC.filePath('news', 'ajax', 'createfolder.php');

			$.post(url, { name: displayname, parentid: folderid },
				function(jsondata){
					if(jsondata.status == 'success'){
						//$(button).closest('tr').prev().html(jsondata.page).show().next().remove();
						OC.dialogs.alert(jsondata.data.message, t('news', 'Success!'));
					} else {
						OC.dialogs.alert(jsondata.data.message, t('news', 'Error'));
					}
					$("#folder_add_name").val('');
					$(button).attr("disabled", false);
					//translation here!!!
					$(button).prop('value', 'Add folder');
			});
		},
		'delete':function(folderid) {
			$('#feeds_delete').tipsy('hide');
			OC.dialogs.confirm(t('news', 'Are you sure you want to delete this folder and all its feeds?'), t('news', 'Warning'), function(answer) {
				if(answer == true) {
					$.post(OC.filePath('news', 'ajax', 'deletefolder.php'),{'folderid':folderid},function(jsondata){
						if(jsondata.status == 'success'){
							alert('removed!');
						}
						else{
							OC.dialogs.alert(jsondata.data.message, t('news', 'Error'));
						}
					});
				}
			});
			return false;
		}
	},
	Feed: {
		id:'',
		submit:function(button){
			var feedurl = $("#feed_add_url").val().trim();

			if(feedurl.length == 0) {
				OC.dialogs.alert(t('news', 'URL cannot be empty.'), t('news', 'Error'));
				return false;
			}

			$(button).attr("disabled", true);
			//translation here!!!
			$(button).prop('value', 'Adding...');

			var folderid = $('#inputfolderid:input[name="folderid"]').val();

			var url;
			url = OC.filePath('news', 'ajax', 'createfeed.php');

			$.post(url, { feedurl: feedurl, folderid: folderid },
				function(jsondata){
					if(jsondata.status == 'success'){
						OC.dialogs.alert(jsondata.data.message, t('news', 'Success!'));
					} else {
						OC.dialogs.alert(jsondata.data.message, t('news', 'Error'));
					}
				$("#feed_add_url").val('');
				$(button).attr("disabled", false);
				//translation here!!!
				$(button).prop('value', 'Add feed');
			});
		},
		'delete':function(feedid) {
			$('#feeds_delete').tipsy('hide');
			OC.dialogs.confirm(t('news', 'Are you sure you want to delete this feed?'), t('news', 'Warning'), function(answer) {
				if(answer == true) {
					$.post(OC.filePath('news', 'ajax', 'deletefeed.php'),{'feedid':feedid},function(jsondata){
						if(jsondata.status == 'success'){
							$('#leftcontent [data-id="'+jsondata.data.feedid+'"]').remove();
							//change the right view too (maybe a message to subscribe, like in Google Reader?)
						}
						else{
							OC.dialogs.alert(jsondata.data.message, t('news', 'Error'));
						}
					});
				}
			});
			return false;
		},
		markItem:function(itemid) {
			$.post(OC.filePath('news', 'ajax', 'markitem.php'),{'itemid':itemid},function(jsondata){
				if(jsondata.status == 'success'){
					var $currentitem = $('#rightcontent [data-id="'+jsondata.data.itemid+'"]');
					$currentitem.removeClass('title_unread');
					$currentitem.addClass('title_read');
					//set a timeout for this
				}
				else{
					OC.dialogs.alert(jsondata.data.message, t('news', 'Error'));
				}
			});
		}
	}
}

$(document).ready(function(){

	$('#addfeed').click(function() {
		News.UI.overview('#addfeed_dialog','feeddialog.php');
		$(this).parent().toggle();
	});
	$('#addfolder').click(function() {
		News.UI.overview('#addfolder_dialog','folderdialog.php');
		$(this).parent().toggle();
	});

	$('.collapsable').click(function(){
		$(this).parent().children().toggle();
		$(this).toggle();
	});

	$('.accordion .title_unread').click(function() {
		$(this).next().toggle();
		return false;
	}).next().hide();

	$('.accordion .title_read').click(function() {
		$(this).next().toggle();
		return false;
	}).next().hide();

        var list = $('.collapsable,.feeds_list').hover(function() {
                var elem = $(this).find('#feeds_delete,#feeds_edit');
                if(elem.css('display') == 'none')
                        elem.css('display', 'inline');
                else
                        elem.css('display', 'none');

                return false;
        });
        list.find('#feeds_delete').hide();
        list.find('#feeds_edit').hide();
});
