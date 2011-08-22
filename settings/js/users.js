/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

$(document).ready(function(){
	function applyMultiplySelect(element){
		var checked=[];
		var user=element.data('username')
		if(element.data('userGroups')){
			checked=element.data('userGroups').split(', ');
		}
		if(user){
			var checkHandeler=function(group){
				if(user==OC.currentUser && group=='admin'){
					return false;
				}
				$.post(
					OC.filePath('settings','ajax','togglegroups.php'),
					{
						username:user,
						group:group
					},
					function(){}
				);
			}
		}else{
			checkHandeler=false;
		}
		element.multiSelect({
			createText:'add group',
			 checked:checked,
			 oncheck:checkHandeler,
			 onuncheck:checkHandeler
		});
	}
	$('select[multiple]').each(function(index,element){
		applyMultiplySelect($(element));
	});
	
	$('td.remove>img').live('click',function(event){
		var uid=$(this).parent().parent().data('uid');
		$.post(
			OC.filePath('settings','ajax','removeuser.php'),
			{username:uid},
			function(result){
			
			}
		);
		$(this).parent().parent().remove();
	});
	
	$('td.password>img').live('click',function(event){
		event.stopPropagation();
		var img=$(this);
		var uid=img.parent().parent().data('uid');
		var input=$('<input type="password">');
		img.css('display','none');
		img.parent().children('span').replaceWith(input);
		input.focus();
		input.keypress(function(event) {
			if(event.keyCode == 13) {
				if($(this).val().length>0){
					$.post(
						OC.filePath('settings','ajax','changepassword.php'),
						{username:uid,password:$(this).val()},
						function(result){}
					);
					input.blur();
				}else{
					input.blur();
				}
			}
		});
		input.blur(function(){
			$(this).replaceWith($('<span>●●●●●●●</span>'));
			img.css('display','');
		});
	});
	$('td.password').live('click',function(event){
		$(this).children('img').click();
	});

	$('td.quota>img').live('click',function(event){
		event.stopPropagation();
		var img=$(this);
		var uid=img.parent().parent().data('uid');
		var input=$('<input>');
		var quota=img.parent().children('span').text();
		img
		if(quota=='None'){
			quota='';
		}
		input.val(quota);
		img.css('display','none');
		img.parent().children('span').replaceWith(input);
		input.focus();
		input.keypress(function(event) {
			if(event.keyCode == 13) {
				$(this).parent().attr('data-quota',$(this).val());
				if($(this).val().length>0){
					$.post(
						OC.filePath('settings','ajax','setquota.php'),
						   {username:uid,quota:$(this).val()},
						   function(result){}
					);
					input.blur();
				}else{
					input.blur();
				}
			}
		});
		input.blur(function(){
			var quota=$(this).parent().data('quota');
			$(this).replaceWith($('<span>'+quota+'</span>'));
			img.css('display','');
		});
	});
	$('td.quota').live('click',function(event){
		$(this).children('img').click();
	});
	
	$('#newuser').submit(function(event){
		event.preventDefault();
		var username=$('#newusername').val();
		var password=$('#newuserpassword').val();
		var groups=$('#newusergroups').prev().children('div').data('settings').checked;
		$.post(
			OC.filePath('settings','ajax','createuser.php'),
			{
				username:username,
				password:password,
				groups:groups,
			},
			function(result){
				
			}
		);
		var tr=$('#content table tbody tr').first().clone();
		tr.attr('data-uid',username);
		tr.find('td.name').text(username);
		var select=$('<select multiple="multiple" data-placehoder="Groups" title="Groups">');
		select.data('username',username);
		select.data('userGroups',groups.join(', '));
		tr.find('td.groups').empty();
		$.each($('#content table').data('groups').split(', '),function(i,group){
			select.append($('<option value="'+group+'">'+group+'</option>'));
		});
		tr.find('td.groups').append(select);
		if(tr.find('td.remove img').length==0){
			tr.find('td.remove').append($('<img alt="Delete" title="'+t('settings','Delete')+'" class="svg action" src="'+OC.imagePath('core','actions/delete')+'"/>'));
		}
		applyMultiplySelect(select);
		$('#content table tbody').last().after(tr);
	});
});
