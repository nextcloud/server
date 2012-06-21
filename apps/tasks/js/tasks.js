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
		var actions = $('#task_actions_template');
		var summary_container = $('<p class="summary">')
				.attr('title', task.description)
				;
		OC.Tasks.setSummary(summary_container, task);
		var task_container = $('<div>')
			.addClass('task')
			.data('task', task)
			.data('show_count', 0)
			.attr('data-id', task.id)
			.append(summary_container)
			.append(actions.clone().removeAttr('id'))
			;
		task_container.find('.summary a').click(OC.Tasks.summaryClickHandler);
		var checkbox = $('<input type="checkbox">')
			.click(OC.Tasks.complete_task);
		if (task.completed) {
			checkbox.attr('checked', 'checked');
			task_container.addClass('done');
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
		var $categories = $('<div>')
				.addClass('categories')
				.appendTo(task_container);
		$(task.categories).each(function(i, category){
				$categories.append($('<a>')
					.addClass('tag')
					.text(category)
				);
		});
		task_container.find('.task_more').click(OC.Tasks.moreClickHandler);
		task_container.find('.task_less').click(OC.Tasks.lessClickHandler);
		var description = $('<textarea>')
			.addClass('description')
			.blur(function(){
				var task = $(this).closest('.task').data('task');
				var description = $(this).val();
				$.post(OC.filePath('tasks', 'ajax', 'update_property.php'), {id:task.id, type:'description', description:description}, function(jsondata){
					if(jsondata.status == 'success') {
						task.description = description;
					}
				});
			})
			.text(task.description);
		var due = $('<span>')
			.addClass('due')
			.append(t('tasks', 'Due'));
		due
			.append($('<input type="date">')
					.addClass('date')
					.datepicker({
						dateFormat: 'dd-mm-yy',
						onClose: OC.Tasks.dueUpdateHandler
					}),
				$('<input type="time">')
					.addClass('time')
					.timepicker({
						showPeriodLabels:false,
						onClose: OC.Tasks.dueUpdateHandler
					})
			);
		if (task.due){
			var date = new Date(parseInt(task.due)*1000);
			due.find('.date').datepicker('setDate', date);
			if (!task.due_date_only) {
				due.find('.time').timepicker('setTime', date.getHours()+':'+date.getMinutes());
			}
		}
		var delete_action = task_container.find('.task_delete').click(OC.Tasks.deleteClickHandler);
		$('<div>')
			.addClass('more')
			.append(delete_action)
			.append(description)
			.append(due)
			.appendTo(task_container);
		$('<input placeholder="'+t('tasks', 'List')+'">')
			.addClass('categories')
			.multiple_autocomplete({source: categories})
			.val(task.categories)
			.blur(function(){
				var task = $(this).closest('.task').data('task');
				var categories = $(this).val();
				$.post(OC.filePath('tasks', 'ajax', 'update_property.php'), {id:task.id, type:'categories', categories:categories}, function(jsondata){
					if(jsondata.status == 'success') {
						task.categories = categories.split(',');
						$categories.empty();
						$(task.categories).each(function(i, category){
							$categories.append($('<a>')
								.addClass('tag')
								.text(category)
								);
							});
					}
				});
			})
			.appendTo(task_container);
		$('<input placeholder="'+t('tasks', 'Location')+'">')
			.addClass('location')
			.val(task.location)
			.blur(function(){
				var task = $(this).closest('.task').data('task');
				var location = $(this).val();
				$.post(OC.filePath('tasks', 'ajax', 'update_property.php'), {id:task.id, type:'location', location:location}, function(jsondata){
					if(jsondata.status == 'success') {
						task.location = location;
						task_container.find('.location').text(location);
					}
				});
			})
			.appendTo(task_container);
		return task_container;
	},
	filter:function(tag, find_filter) {
		var tag_text = $(tag).text();
		var filter = !$(tag).hasClass('active');
		OC.Tasks.filterUpdate(filter, function(task_container){
			var found = 0;
			task_container.find(find_filter).each(function(){
				if ($(this).text() == tag_text) {
					$(this).toggleClass('active');
					found = 1;
				}
			});
			return found;
		});
	},
	filterUpdate:function(filter, find_filter) {
		var show_count = $('#tasks_list').data('show_count');
		show_count += filter ? +1 : -1;
		$('#tasks_list').data('show_count', show_count);
		$('#tasks_lists .task, #tasks_list .task').each(function(i, task_container){
			task_container = $(task_container);
			var task = task_container.data('task');
			var found = find_filter(task_container);
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
		var tasks = $('#tasks_list .task').not('.clone');
		tasks.sort(sort);
		var current = null;
		tasks.detach();
		var $tasks = $('#tasks_list').empty();
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
	},
	setSummary:function(summary_container, task){
		var summary = $('<a href="index.php?id='+task.id+'">')
			.text(task.summary)
			.click(OC.Tasks.summaryClickHandler);
		summary_container.html(summary);
	},
	summaryClickHandler:function(event){
		event.preventDefault();
		//event.stopPropagation();
		var task = $(this).closest('.task').data('task');
		var summary_container = $(this).parent();
		var input = $('<input>').val($(this).text()).blur(function(){
			var old_summary = task.summary;
			task.summary = $(this).val();
			OC.Tasks.setSummary(summary_container, task);
			$.post(OC.filePath('tasks', 'ajax', 'update_property.php'), {id:task.id, type:'summary', summary:task.summary}, function(jsondata){
				if(jsondata.status != 'success') {
					task.summary = old_summary;
					OC.Tasks.setSummary(summary_container, task);
				}
			});
		});
		summary_container.empty().append(input);
		input.focus();
		return false;
	},
	dueUpdateHandler:function(){
		var task = $(this).closest('.task').data('task');
		var old_due = task.due;
		var $date = $(this).parent().children('.date');
		var $time = $(this).parent().children('.time');
		var date = $date.datepicker('getDate');
		var time = $time.val().split(':');
		var due, date_only = false;
		if (!date){
			due = false;
		} else {
			if (time.length==2){
				date.setHours(time[0]);
				date.setMinutes(time[1]);
			}
			else {
				date_only = true;
			}
			due = date.getTime()/1000;
		}
		$.post(OC.filePath('tasks', 'ajax', 'update_property.php'), {id:task.id, type:'due', due:due, date:date_only?1:0}, function(jsondata){
			if(jsondata.status != 'success') {
				task.due = old_due;
			}
		});
	},
	moreClickHandler:function(event){
		var $task = $(this).closest('.task'),
			task = $task.data('task');
		$task.find('.more').show();
		$task.find('.task_more').hide();
		$task.find('.task_less').show();
		$task.find('div.categories').hide();
		$task.find('input.categories').show();
		$task.find('div.location').hide();
		$task.find('input.location').show();
	},
	lessClickHandler:function(event){
		var $task = $(this).closest('.task'),
			task = $task.data('task');
		$task.find('.more').hide();
		$task.find('.task_more').show();
		$task.find('.task_less').hide();
		$task.find('div.categories').show();
		$task.find('input.categories').hide();
		$task.find('div.location').show();
		$task.find('input.location').hide();
	},
	deleteClickHandler:function(event){
		var $task = $(this).closest('.task'),
			task = $task.data('task');
		$.post(OC.filePath('tasks', 'ajax', 'delete.php'),{'id':task.id},function(jsondata){
			if(jsondata.status == 'success'){
				$task.remove();
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	},
	complete_task:function() {
		var $task = $(this).closest('.task'),
			task = $task.data('task'),
			checked = $(this).is(':checked');
		$.post(OC.filePath('tasks', 'ajax', 'update_property.php'), {id:task.id, type:'complete', checked:checked?1:0}, function(jsondata){
			if(jsondata.status == 'success') {
				task = jsondata.data;
				$task.data('task', task)
				if (task.completed) {
					$task.addClass('done');
				}
				else {
					$task.removeClass('done');
				}
			}
			else{
				alert(jsondata.data.message);
			}
		}, 'json');
	},
	categoriesChanged:function(newcategories){
		categories = $.map(newcategories, function(v) {return v;});
		console.log('Task categories changed to: ' + categories);
		$('input.categories').multiple_autocomplete('option', 'source', categories);
	},
	List: {
		create_list_div:function(category){
			return $('<div>').text(category)
				.click(function(){
					OC.Tasks.filter(this, 'div.categories .tag');
					$(this).toggleClass('active');
				});
		}
	}
};

$(document).ready(function(){
	$(window).resize(function () {
		fillHeight($('#tasks_lists'));
		fillWindow($('#tasks_list'));
	});
	$(window).trigger('resize');

	/*-------------------------------------------------------------------------
	 * Actions for startup
	 *-----------------------------------------------------------------------*/
	$.getJSON(OC.filePath('tasks', 'ajax', 'gettasks.php'), function(jsondata) {
		var tasks = $('#tasks_list').empty().data('show_count', 0);
		$(jsondata).each(function(i, task) {
			tasks.append(OC.Tasks.create_task_div(task));
		});
		if( $('#tasks_list div').length > 0 ){
			$('#tasks_list div').first().addClass('active');
		}
		$(categories).each(function(i, category) {
			$('#tasks_lists .all').after(OC.Tasks.List.create_list_div(category));
		});
		$('#tasks_lists .all').click(function(){
			$('#tasks_lists .active').click();
		});
		$('#tasks_lists .done').click(function(){
			var filter = !$(this).hasClass('active');
			OC.Tasks.filterUpdate(filter, function(task_container){
				return task_container.hasClass('done');
			});
			$(this).toggleClass('active');
		});
		OCCategories.changed = OC.Tasks.categoriesChanged;
		OCCategories.app = 'calendar';
	});

	/*-------------------------------------------------------------------------
	 * Event handlers
	 *-----------------------------------------------------------------------*/
	$('#tasks_list div.categories .tag').live('click',function(){
		OC.Tasks.filter(this, 'div.categories .tag');
		var tag_text = $(this).text();
		$('#tasks_lists div:not(".all"):not(".done")').each(function(){
			if ($(this).text() == tag_text) {
				$(this).toggleClass('active');
			}
		});
	});

	$('#tasks_list .priority.tag').live('click',function(){
		OC.Tasks.filter(this, '.priority.tag');
	});

	$('#tasks_list .location.tag').live('click',function(){
		OC.Tasks.filter(this, '.location.tag');
	});

	$('#tasks_order_category').click(function(){
		var tasks = $('#tasks_list .task').not('.clone');
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
		var $tasks = $('#tasks_list').empty();
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

	$('#tasks_addtask').click(function(){
		var input = $('#tasks_newtask').val();
		$.post(OC.filePath('tasks', 'ajax', 'addtask.php'),{text:input},function(jsondata){
			if(jsondata.status == 'success'){
				$('#tasks_list').append(OC.Tasks.create_task_div(jsondata.task));
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
				$('#tasks_list').append(OC.Tasks.create_task_div(jsondata.data.task));
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
