$(document).ready(function(){
	/*-------------------------------------------------------------------------
	 * Actions for startup
	 *-----------------------------------------------------------------------*/
	$.getJSON(OC.filePath('tasks', 'ajax', 'gettasks.php'), function(jsondata) {
		var tasks = $('#tasks').empty();
		var actions = $('#task_actions_template');
		$(jsondata).each(function(i, task) {
			var task_container = $('<div>').appendTo(tasks)
				.addClass('task')
				.data('task', task)
				.attr('data-id', task.id)
				.append($('<p>')
					.html('<a href="index.php?id='+task.id+'">'+task.summary+'</a>')
					.addClass('summary')
					)
				.append(actions.clone().removeAttr('id'))
				;
			if (task.categories.length > 0) {
				var categories = $('<div>')
						.addClass('categories')
						.appendTo(task_container);
				$(task.categories).each(function(i, category){
						categories.append($('<a>')
							.text(category)
						);
				});
			}
		});
		if( $('#tasks div').length > 0 ){
			$('#tasks div').first().addClass('active');
		}

	});

	fillHeight($('#tasks'));
	fillWindow($('#task_details'));

	/*-------------------------------------------------------------------------
	 * Event handlers
	 *-----------------------------------------------------------------------*/
	$('#tasks div').live('click',function(){
		var id = $(this).data('id');
		var oldid = $('#task_details').data('id');
		if(oldid != 0){
			$('#tasks li[data-id="'+oldid+'"]').removeClass('active');
		}
		$.getJSON('ajax/getdetails.php',{'id':id},function(jsondata){
			if(jsondata.status == 'success'){
				$('#task_details').data('id',jsondata.data.id);
				$('#task_details').html(jsondata.data.page);
				$('#tasks li[data-id="'+jsondata.data.id+'"]').addClass('active');
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	});

	$('#tasks_delete').live('click',function(){
		var id = $('#task_details').data('id');
		$.getJSON('ajax/delete.php',{'id':id},function(jsondata){
			if(jsondata.status == 'success'){
				$('#tasks [data-id="'+jsondata.data.id+'"]').remove();
				$('#task_details').data('id','');
				$('#task_details').html('');
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	});

	$('#tasks_newtask').click(function(){
		$.getJSON('ajax/addtaskform.php',{},function(jsondata){
			if(jsondata.status == 'success'){
				$('#task_details').data('id','');
				$('#task_details').html(jsondata.data.page);
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	});

	$('#tasks_addtaskform input[type="submit"]').live('click',function(){
		$.post('ajax/addtask.php',$('#tasks_addtaskform').serialize(),function(jsondata){
			if(jsondata.status == 'success'){
				$('#task_details').data('id',jsondata.data.id);
				$('#task_details').html(jsondata.data.page);
			}
			else{
				alert(jsondata.data.message);
			}
		}, 'json');
		return false;
	});

	$('#tasks_edit').live('click',function(){
		var id = $('#task_details').data('id');
		$.getJSON('ajax/edittaskform.php',{'id':id},function(jsondata){
			if(jsondata.status == 'success'){
				$('#task_details').html(jsondata.data.page);
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	});

	$('#tasks_edittaskform #percent_complete').live('change',function(event){
		if ($(event.target).val() == 100){
			$('#tasks_edittaskform #complete').show();
		}else{
			$('#tasks_edittaskform #complete').hide();
		}
	});

	$('#tasks_edittaskform input[type="submit"]').live('click',function(){
		$.post('ajax/edittask.php',$('#tasks_edittaskform').serialize(),function(jsondata){
			$('.error_msg').remove();
			$('.error').removeClass('error');
			if(jsondata.status == 'success'){
				$('#task_details').data('id',jsondata.data.id);
				$('#task_details').html(jsondata.data.page);
			}
			else{
				var errors = jsondata.data.errors;
				for (k in errors){
					$('#'+k).addClass('error')
						.after('<span class="error_msg">'+errors[k]+'</span>');
				}
				$('.error_msg').effect('highlight', {}, 3000);
				$('.error').effect('highlight', {}, 3000);
			}
		}, 'json');
		return false;
	});
});
