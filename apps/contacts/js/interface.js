$(document).ready(function(){
	/* $('.contacts_addressbooksexpander').click(function(){
		$('.contacts_addressbooksdetails').toggle();
		return false;
	});*/

	$('#contacts_contacts li').live('click',function(){
		var id = $(this).attr('x-id');
		$.getJSON('ajax/getdetails.php',{'id':id},function(jsondata){
			if(jsondata.status == 'success'){
				$('#contacts_details').attr('x-id',jsondata.data.id);
				$('#contacts_details').html(jsondata.data.page);
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	});

	$('#contacts_deletecard').live('click',function(){
		var id = $('#contacts_details').attr('x-id');
		$.getJSON('ajax/deletecard.php',{'id':id},function(jsondata){
			if(jsondata.status == 'success'){
				$('#contacts_contacts [x-id="'+jsondata.data.id+'"]').remove();
				$('#contacts_details').attr('x-id','');
				$('#contacts_details').html('');
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	});

	$('#contacts_addproperty').live('click',function(){
		var id = $('#contacts_details').attr('x-id');
		$.getJSON('ajax/showaddproperty.php',{'id':id},function(jsondata){
			if(jsondata.status == 'success'){
				$('#contacts_details').append(jsondata.data.page);
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
			$('#contacts_addresspart').clone().insertBefore($('#contacts_addpropertyform input[type="submit"]'));
		}
		else if($(this).val() == 'TEL'){
			$('#contacts_phonepart').clone().insertBefore($('#contacts_addpropertyform input[type="submit"]'));
		}
		else if($(this).val() == 'NOTE'){
			$('#contacts_fieldpart').clone().insertBefore($('#contacts_addpropertyform input[type="submit"]'));
		}
		else{
			$('#contacts_generic').clone().insertBefore($('#contacts_addpropertyform input[type="submit"]'));
		}
	});

	$('#contacts_addpropertyform input[type="submit"]').live('click',function(){
		$.post('ajax/addproperty.php',$('#contacts_addpropertyform').serialize(),function(jsondata){
			if(jsondata.status == 'success'){
				$('#contacts_details').append(jsondata.data.page);
				$('#contacts_addpropertyform').remove();
			}
			else{
				alert(jsondata.data.message);
			}
		}, 'json');
		return false;
	});
	
	$('#contacts_newcontact').click(function(){
		$.getJSON('ajax/showaddcard.php',{},function(jsondata){
			if(jsondata.status == 'success'){
				$('#contacts_details').attr('x-id','');
				$('#contacts_details').html(jsondata.data.page);
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
				$('#contacts_details').attr('x-id',jsondata.data.id);
				$('#contacts_details').html(jsondata.data.page);
			}
			else{
				alert(jsondata.data.message);
			}
		}, 'json');
		return false;
	});

	$('.contacts_property [x-use="edit"]').live('click',function(){
		var id = $('#contacts_details').attr('x-id');
		var checksum = $(this).parent().parent().attr('x-checksum');
		var line = $(this).parent().parent().attr('x-line');
		$.getJSON('ajax/showsetproperty.php',{'id': id, 'checksum': checksum, 'line': line },function(jsondata){
			if(jsondata.status == 'success'){
				$('.contacts_property[x-line="'+line+'"][x-checksum="'+checksum+'"] .contacts_propertyvalue').html(jsondata.data.page);
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	});

	$('#contacts_setpropertyform input[type="submit"]').live('click',function(){
		$.post('ajax/setproperty.php',$('#contacts_setpropertyform').serialize(),function(jsondata){
			if(jsondata.status == 'success'){
				$('.contacts_property[x-line="'+jsondata.data.line+'"][x-checksum="'+jsondata.data.oldchecksum+'"]').replaceWith(jsondata.data.page);
			}
			else{
				alert(jsondata.data.message);
			}
		},'json');
		return false;
	});

	$('.contacts_property [x-use="delete"]').live('click',function(){
		var id = $('#contacts_details').attr('x-id');
		var checksum = $(this).parent().parent().attr('x-checksum');
		var line = $(this).parent().parent().attr('x-line');
		$.getJSON('ajax/deleteproperty.php',{'id': id, 'checksum': checksum, 'line': line },function(jsondata){
			if(jsondata.status == 'success'){
				$('.contacts_property[x-line="'+line+'"][x-checksum="'+checksum+'"]').remove();
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	});


	$('.contacts_property').live('mouseenter',function(){
		$(this).find('span').show();
	});
	
	$('.contacts_property').live('mouseleave',function(){
		$(this).find('span').hide();
	});
});
