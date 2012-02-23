OC.Tasks = {
	filter:function(tag, find_filter) {
		var tag_text = $(tag).text();
		var filter = !$(tag).hasClass('active');
		var show_count = $('#tasks').data('show_count');
		show_count += filter ? +1 : -1;
		$('#tasks').data('show_count', show_count);
		$('#tasks .task').each(function(i, task_container){
			task_container = $(task_container);
			var task = task_container.data('task');
			var found = 0;
			task_container.find(find_filter).each(function(){
				if ($(this).text() == tag_text) {
					$(this).toggleClass('active');
					found = 1;
				}
			});
			var hide_count = task_container.data('show_count');
			if (!filter) {
				hide_count-=found;
			}
			else {
				hide_count+=found;
			}
			if (hide_count == show_count) {
				task_container.show();
			}
			else {
				task_container.hide();
			}
			task_container.data('show_count', hide_count);
		});
	}
};

$(document).ready(function(){
	/*-------------------------------------------------------------------------
	 * Actions for startup
	 *-----------------------------------------------------------------------*/
	$.getJSON(OC.filePath('tasks', 'ajax', 'gettasks.php'), function(jsondata) {
		var tasks = $('#tasks').empty().data('show_count', 0);
		var actions = $('#task_actions_template');
		$(jsondata).each(function(i, task) {
			var task_container = $('<div>').appendTo(tasks)
				.addClass('task')
				.data('task', task)
				.data('show_count', 0)
				.attr('data-id', task.id)
				.append($('<p>')
					.html('<a href="index.php?id='+task.id+'">'+task.summary+'</a>')
					.addClass('summary')
					)
				//.append(actions.clone().removeAttr('id'))
				;
			var checkbox = $('<input type="checkbox">');
			if (task.completed) {
				checkbox.attr('checked', 'checked');
			}
			$('<div>')
				.addClass('completed')
				.append(checkbox)
				.prependTo(task_container);
			var priority = task.priority;
			$('<div>')
				.addClass('tag')
				.addClass('priority')
				.addClass('priority-'+(priority?priority:'n'))
				.text(priority)
				.prependTo(task_container);
			if (task.location) {
				$('<div>')
					.addClass('tag')
					.addClass('location')
					.text(task.location)
					.appendTo(task_container);
			}
			if (task.categories.length > 0) {
				var categories = $('<div>')
						.addClass('categories')
						.appendTo(task_container);
				$(task.categories).each(function(i, category){
						categories.append($('<a>')
							.addClass('tag')
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
	$('#tasks div.task .summary').live('click',function(){
		var id = $(this).parent('div.task').data('id');
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

	$('#tasks div.categories .tag').live('click',function(){
		OC.Tasks.filter(this, 'div.categories .tag');
	});

	$('#tasks .priority.tag').live('click',function(){
		OC.Tasks.filter(this, '.priority.tag');
	});

	$('#tasks .location.tag').live('click',function(){
		OC.Tasks.filter(this, '.location.tag');
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
