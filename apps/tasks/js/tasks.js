OC.Tasks = {
	bool_string_cmp:function(a, b) {
		if (a === b) {
			return 0;
		}
		if (a === false) {
			return -1;
		}
		if (b === false) {
			return 1;
		}
		return a.localeCompare(b);
	},
	create_task_div:function(task) {
		var task_container = $('<div>')
			.addClass('task')
			.data('task', task)
			.data('show_count', 0)
			.attr('data-id', task.id)
			.append($('<p>')
				.html('<a href="index.php?id='+task.id+'">'+task.summary+'</a>')
				.addClass('summary')
				.attr('title', task.description)
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
		return task_container;
	},
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
	},
	order:function(sort, get_property, empty_label) {
		var tasks = $('#tasks .task').not('.clone');
		tasks.sort(sort);
		var current = null;
		tasks.detach();
		var $tasks = $('#tasks').empty();
		var container = $tasks;
		tasks.each(function(){
			if (get_property) {
				var label = get_property($(this).data('task'));
				if(label != current) {
					current = label;
					container = $('<div>').appendTo($tasks);
					if (label == '' && empty_label) {
						label = empty_label;
					}
					$('<h1>').text(label).appendTo(container);
				}
			}
			container.append(this);
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
			tasks.append(OC.Tasks.create_task_div(task));
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

	$('#tasks_order_category').click(function(){
		var tasks = $('#tasks .task').not('.clone');
		var collection = {};
		tasks.each(function(i, task) {
			var categories = $(task).data('task').categories;
			$(categories).each(function() {
				if (!collection.hasOwnProperty(this)) {
					collection[this] = [];
				}
				collection[this].push(task);
				if (categories.length > 1) {
					task = $(task).clone(true).addClass('clone').get(0);
				}
			});
			if (categories.length == 0) {
				if (!collection.hasOwnProperty('')) {
					collection[''] = [];
				}
				collection[''].push(task);
			}
		});
		var labels = [];
		for (var label in collection) {
			labels.push(label);
		}
		labels.sort();
		tasks.detach();
		var $tasks = $('#tasks').empty();
		for (var index in labels) {
			var label = labels[index];
			var container = $('<div>').appendTo($tasks);
			if (label == '') {
				label = t('tasks', 'No category');
			}
			$('<h1>').text(label).appendTo(container);
			container.append(collection[labels[index]]);
		}
	});

	$('#tasks_order_due').click(function(){
		OC.Tasks.order(function(a, b){
			a = $(a).data('task').due;
			b = $(b).data('task').due;
			return OC.Tasks.bool_string_cmp(a, b);
		});
	});

	$('#tasks_order_complete').click(function(){
		OC.Tasks.order(function(a, b){
			return ($(a).data('task').complete - $(b).data('task').complete) ||
				OC.Tasks.bool_string_cmp($(a).data('task').completed, $(b).data('task').completed);
		});
	});

	$('#tasks_order_location').click(function(){
		OC.Tasks.order(function(a, b){
			a = $(a).data('task').location;
			b = $(b).data('task').location;
			return OC.Tasks.bool_string_cmp(a, b);
		});
	});

	$('#tasks_order_prio').click(function(){
		OC.Tasks.order(function(a, b){
			return $(a).data('task').priority
			     - $(b).data('task').priority;
		});
	});

	$('#tasks_order_label').click(function(){
		OC.Tasks.order(function(a, b){
			return $(a).data('task').summary.localeCompare(
			       $(b).data('task').summary);
		});
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
				$('#tasks').append(OC.Tasks.create_task_div(jsondata.data.task));
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
				$('#task_details #categories').multiple_autocomplete({source: categories});
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
				var id = jsondata.data.id;
				$('#task_details').data('id',id);
				$('#task_details').html(jsondata.data.page);
				var task = jsondata.data.task;
				$('#tasks .task[data-id='+id+']')
					.data('task', task)
					.html(OC.Tasks.create_task_div(task).html());
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

	OCCategories.app = 'calendar';
});
