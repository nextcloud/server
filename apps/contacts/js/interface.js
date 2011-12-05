Contacts={
	space:' ',
	UI:{
		showCardDAVUrl:function(username, bookname){
			$('#carddav_url').val(totalurl + '/' + username + '/' + bookname);
			$('#carddav_url').show();
			$('#carddav_url_close').show();
		},
		Addressbooks:{
			overview:function(){
				if($('#chooseaddressbook_dialog').dialog('isOpen') == true){
					/*alert('Address books.moveToTop');*/
					$('#chooseaddressbook_dialog').dialog('moveToTop');
				}else{
					$('#dialog_holder').load(OC.filePath('contacts', 'ajax', 'chooseaddressbook.php'), function(){
						/*alert('Address books.load');*/
						$('#chooseaddressbook_dialog').dialog({
							width : 600,
							close : function(event, ui) {
								$(this).dialog('destroy').remove();
							}
						});
					});
				}
			},
			activation:function(checkbox, bookid)
			{
				/* TODO: 
				 * Add integer field 'active' to table 'contacts_addressbooks'. See apps/contacts/README.tanghus */
				$.post(OC.filePath('contacts', 'ajax', 'activation.php'), { bookid: bookid, active: checkbox.checked?1:0 },
				  function(data) {
					/*
					 * Arguments:
					 * data.status
					 * data.bookid
					 */
					if (data.status == 'success'){
						checkbox.checked = data.active == 1;
						alert('TODO: Update Contacts list.');
						/* TODO: Update Contacts list.
						if (data.active == 1){
							$('#calendar_holder').fullCalendar('addEventSource', data.eventSource);
						}else{
							$('#calendar_holder').fullCalendar('removeEventSource', data.eventSource.url);
						}
						*/
					}
				  });
			},
			newAddressbook:function(object){
				var tr = $(document.createElement('tr'))
					.load(OC.filePath('contacts', 'ajax', 'addbook.php'));
				$(object).closest('tr').after(tr).hide();
				/* TODO: Shouldn't there be some kinda error checking here? */
			},
			editAddressbook:function(object, bookid){
				var tr = $(document.createElement('tr'))
					.load(OC.filePath('contacts', 'ajax', 'editaddressbook.php') + "?bookid="+bookid);
				$(object).closest('tr').after(tr).hide();
			},
			deleteAddressbook:function(bookid){
				var check = confirm("Do you really want to delete this address book?");
				if(check == false){
					return false;
				}else{
					$.post(OC.filePath('contacts', 'ajax', 'deletebook.php'), { id: bookid},
					  function(data) {
						if (data.status == 'success'){
							/* alert('TODO: Update Contacts list.'); */
							/* TODO: Update Contacts list.
							var url = 'ajax/deletebook.php?id='+bookid;
							$('#calendar_holder').fullCalendar('removeEventSource', url);*/
							$('#chooseaddressbook_dialog').dialog('destroy').remove();
							Contacts.UI.Addressbooks.overview();
						} else {
							alert('Error: ' + data.message);
						}
					  });
				}
			},
			submit:function(button, bookid){
				var displayname = $("#displayname_"+bookid).val();
				var active = $("#edit_active_"+bookid+":checked").length;
				var description = $("#description_"+bookid).val();

				var url;
				if (bookid == 'new'){
					url = OC.filePath('contacts', 'ajax', 'createaddressbook.php');
				}else{
					url = OC.filePath('contacts', 'ajax', 'updateaddressbook.php');
				}
				$.post(url, { id: bookid, name: displayname, active: active, description: description },
					function(data){
						if(data.status == 'success'){
							$(button).closest('tr').prev().html(data.page).show().next().remove();
						}
					});
			},
			cancel:function(button, bookid){
				$(button).closest('tr').prev().show().next().remove();
			}
		}
	}
}

$(document).ready(function(){
	/*-------------------------------------------------------------------------
	 * Event handlers
	 *-----------------------------------------------------------------------*/
	$('#leftcontent li').live('click',function(){
		var id = $(this).data('id');
		var oldid = $('#rightcontent').data('id');
		if(oldid != 0){
			$('#leftcontent li[data-id="'+oldid+'"]').removeClass('active');
		}
		$.getJSON('ajax/getdetails.php',{'id':id},function(jsondata){
			if(jsondata.status == 'success'){
				$('#rightcontent').data('id',jsondata.data.id);
				$('#rightcontent').html(jsondata.data.page);
				$('#leftcontent li[data-id="'+jsondata.data.id+'"]').addClass('active');
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	});

	$('#contacts_deletecard').live('click',function(){
		var id = $('#rightcontent').data('id');
		$.getJSON('ajax/deletecard.php',{'id':id},function(jsondata){
			if(jsondata.status == 'success'){
				$('#leftcontent [data-id="'+jsondata.data.id+'"]').remove();
				$('#rightcontent').data('id','');
				$('#rightcontent').empty();
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	});

	$('#contacts_addproperty').live('click',function(){
		var id = $('#rightcontent').data('id');
		$.getJSON('ajax/showaddproperty.php',{'id':id},function(jsondata){
			if(jsondata.status == 'success'){
				$('#contacts_details_list').append(jsondata.data.page);
				$('#contacts_addproperty').hide();
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	});

	$('#contacts_addpropertyform [name="name"]').live('change',function(){
		$('#contacts_addpropertyform #contacts_addresspart').remove();
		$('#contacts_addpropertyform #contacts_phonepart').remove();
		$('#contacts_addpropertyform #contacts_fieldpart').remove();
		$('#contacts_addpropertyform #contacts_generic').remove();
		if($(this).val() == 'ADR'){
			$('#contacts_addresspart').clone().insertAfter($('#contacts_addpropertyform .contacts_property_name'));
		}
		else if($(this).val() == 'TEL'){
			$('#contacts_phonepart').clone().insertAfter($('#contacts_addpropertyform .contacts_property_name'));
		}
		else{
			$('#contacts_generic').clone().insertAfter($('#contacts_addpropertyform .contacts_property_name'));
		}
		$('#contacts_addpropertyform .contacts_property_data select').chosen();
	});

	$('#contacts_addpropertyform input[type="submit"]').live('click',function(){
		$.post('ajax/addproperty.php',$('#contacts_addpropertyform').serialize(),function(jsondata){
			if(jsondata.status == 'success'){
				$('#contacts_addpropertyform').before(jsondata.data.page);
			}
			else{
				alert(jsondata.data.message);
			}
		}, 'json');
		return false;
	});

	$('#chooseaddressbook').click(function(){
		Contacts.UI.Addressbooks.overview();
		/*
		$.getJSON('ajax/showaddcard.php',{},function(jsondata){
			if(jsondata.status == 'success'){
				$('#rightcontent').data('id','');
				$('#rightcontent').html(jsondata.data.page)
					.find('select').chosen();
			}
			else{
				alert(jsondata.data.message);
			}
		});
		*/
		return false;
	});
	
	$('#contacts_newcontact').click(function(){
		$.getJSON('ajax/showaddcard.php',{},function(jsondata){
			if(jsondata.status == 'success'){
				$('#rightcontent').data('id','');
				$('#rightcontent').html(jsondata.data.page)
					.find('select').chosen();
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	});

	$('#contacts_addcardform input[type="submit"]').live('click',function(){
		$.post('ajax/addcard.php',$('#contacts_addcardform').serialize(),function(jsondata){
			if(jsondata.status == 'success'){
				$('#rightcontent').data('id',jsondata.data.id);
				$('#rightcontent').html(jsondata.data.page);
				$('#leftcontent .active').removeClass('active');
				$('#leftcontent ul').append('<li data-id="'+jsondata.data.id+'" class="active"><a href="index.php?id='+jsondata.data.id+'">'+jsondata.data.name+'</a></li>');
			}
			else{
				alert(jsondata.data.message);
			}
		}, 'json');
		return false;
	});

	$('.contacts_property [data-use="edit"]').live('click',function(){
		var id = $('#rightcontent').data('id');
		var checksum = $(this).parents('li').first().data('checksum');
		$.getJSON('ajax/showsetproperty.php',{'id': id, 'checksum': checksum },function(jsondata){
			if(jsondata.status == 'success'){
				$('.contacts_property[data-checksum="'+checksum+'"]').html(jsondata.data.page)
					.find('select').chosen();
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	});

	$('#contacts_setpropertyform input[type="submit"]').live('click',function(){
		$.post('ajax/setproperty.php',$(this).parents('form').first().serialize(),function(jsondata){
			if(jsondata.status == 'success'){
				$('.contacts_property[data-checksum="'+jsondata.data.oldchecksum+'"]').replaceWith(jsondata.data.page);
			}
			else{
				alert(jsondata.data.message);
			}
		},'json');
		return false;
	});

	$('.contacts_property [data-use="delete"]').live('click',function(){
		var id = $('#rightcontent').data('id');
		var checksum = $(this).parents('li').first().data('checksum');
		$.getJSON('ajax/deleteproperty.php',{'id': id, 'checksum': checksum },function(jsondata){
			if(jsondata.status == 'success'){
				$('.contacts_property[data-checksum="'+checksum+'"]').remove();
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	});


	$('.contacts_property').live('mouseenter',function(){
		$(this).find('span[data-use]').show();
	});

	$('.contacts_property').live('mouseleave',function(){
		$(this).find('span[data-use]').hide();
	});

	$('#contacts_addcardform select').chosen();
});
