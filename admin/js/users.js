$(document).ready(function(){
	$('select[multiple]').chosen();
	
	$('td.remove>img').live('click',function(event){
		var uid=$(this).parent().parent().data('uid');
		$.post(
			OC.filePath('admin','ajax','removeuser.php'),
			{username:uid},
			function(result){
			
			}
		);
		$(this).parent().parent().remove();
	});
	
	$('#newuser').submit(function(event){
		event.preventDefault();
		var username=$('#newusername').val();
		var password=$('#newuserpassword').val();
		var groups=$('#newusergroups').val();
		$.post(
			OC.filePath('admin','ajax','createuser.php'),
			{
				username:username,
				password:password,
				groups:groups,
			},
			function(result){
				
			}
		);
		var tr=$('#rightcontent tr').first().clone();
		tr.attr('data-uid',username);
		tr.find('td.name').text(username);
		tr.find('td.groups').text(groups.join(', '));
		$('#rightcontent tr').first().after(tr);
		if(groups.indexOf($('#leftcontent li.selected').text().trim())!=-1){
			tr.find('td.select input').attr('checked','checked');
		}
	});
	
	$('#newgroup').submit(function(event){
		event.preventDefault();
		var name=$('#newgroupname').val();
		$.post(
			OC.filePath('admin','ajax','creategroup.php'),
			{groupname:name},
			function(result){
			
			}
		);
		$('#newusergroups').append('<option value="'+name+'">'+name+'</option>');
		$('select[multiple]').trigger("liszt:updated");
		var li=$('#leftcontent li').first().next().clone();
		li.text(name);
		$('#leftcontent li').first().after(li);
	});
	
	$('#leftcontent li').live('click',function(event){
		$('#leftcontent li').removeClass('selected');
		$(this).addClass('selected');
		$('#rightcontent tr td.select input').show();
		$('#rightcontent tr td.select input').removeAttr('checked');
		var group=$(this).text().trim();
		var rows=$('#rightcontent tr').filter(function(i,tr){
			return ($(tr).children('td.groups').text().split(', ').indexOf(group)>-1);
		});
		rows.find('td.select input').attr('checked','checked');
	});
	$('#rightcontent tr td.select input').live('change',function(event){
		var group=$('#leftcontent li.selected').text().trim();
		var user=$(this).parent().parent().children('td.name').text().trim();
		if(group=='admin' && user==OC.currentUser){
			event.preventDefault();
			$(this).attr('checked','checked');
			return false;
		}
		if(group){
			$.post(
				OC.filePath('admin','ajax','togglegroups.php'),
				{
					username:user,
					group:group
				},
				function(result){
					
				}
			);
			var groups=$(this).parent().parent().children('td.groups').text().trim().split(', ');
			if(groups[0]=='') groups.pop();
			var index=groups.indexOf(group);
			if(index==-1){
				groups.push(group);
			}else{
				groups.splice(index,1);
			}
			$(this).parent().parent().children('td.groups').text(groups.join(', '));
		}
	});
});
