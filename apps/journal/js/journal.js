String.prototype.unEscape = function(){
	str = this;
	return str.replace(/\\"/g, '"');
};
String.prototype.stripTags = function(){
	tags = this;
	stripped = tags.replace(/<(.|\n)*?>/g, '');
	return stripped;
};
String.prototype.zeroPad = function(digits) {
	n = this.toString();
	while (n.length < digits) {
		n = '0' + n;
	}
	return n;
}

OC.Journal = {
	init:function() {
		this.setEnabled(false);
		// Fetch journal entries. If it's a direct link 'id' will be loaded.
		OC.Journal.Journals.update(id);
	},
	categoriesChanged:function(newcategories) { // Categories added/deleted.
		categories = $.map(newcategories, function(v) {return v;});
		$('#categories').multiple_autocomplete('option', 'source', categories);
		var categorylist = $('#categories_value').find('input');
		$.getJSON(OC.filePath('journal', 'ajax', 'categories/categoriesfor.php'),{'id':Contacts.UI.Card.id},function(jsondata){
			if(jsondata.status == 'success'){
				$('#categories_value').data('checksum', jsondata.data.checksum);
				categorylist.val(jsondata.data.value);
			} else {
				OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
			}
		});
	},
	propertyContainerFor:function(obj) {
		if($(obj).hasClass('propertycontainer')) {
			return $(obj);
		}
		return $(obj).parents('.propertycontainer').first();
	},
	required:function(event){ // eventhandler for required elements
			// FIXME: This doesn't seem to work.
			console.log('blur on required');
			var obj = $(event.target);
			$(obj).addClass('required');
			if($(this).val().trim() == '') {
				$('<strong>This field is required!</strong>').appendTo($(obj));
				return;
			} else {
				$(obj).removeClass('required');
				$(obj).off('blur', OC.Journal.required);
			}
	},
	setEnabled:function(state) {
		if(state == undefined) { state = true; }
		console.log('OC.Journal.setEnabled: ' + state);
		if(state) {
			$('#description').rte('setEnabled', true);
			if($('#description').rte('mode') == 'html') {
				$('#editortoolbar li').show();
			}
			$('#togglemode').show();
			$('#summary').addClass('editable');
			$('.property,#also_time').each(function () {
				$(this).attr('disabled', false);
			});
		} else {
			$('#description').rte('setEnabled', false);
			$('#editortoolbar .richtext, #togglemode').hide();
			$('#summary').removeClass('editable');
			$('.property,#also_time').each(function () {
				$(this).attr('disabled', true);
			});
		}
	},
	toggleMode:function() {
		console.log('togglemode');
		$('#description').rte('toggleMode');
		$('#editortoolbar li.richtext').toggle();
	},
	Entry:{
		id:'',
		data:undefined,
		add:function() {
			// TODO: wrap a DIV around the summary field with a suggestion(?) to fill out this field first. See OC.Journal.required
			// Remember to reenable all controls.
			$('#leftcontent lidata-id="'+this.id+'"').removeClass('active');
			this.id = 'new';
			this.data = undefined;
			$('.property').each(function () {
				switch($(this).get(0).nodeName) {
					case 'DIV':
						$(this).html('');
						break;
					case 'INPUT':
					case 'TEXTAREA':
						$(this).val('');
						break;
					default:
						console.log('OC.Journal.Entry.add. Forgot: ' + $(this).get(0).nodeName);
						break;
				}
			});
			$('#description').rte('setEnabled', false);
			$('#editortoolbar li.richtext').hide();
			$('#editable').attr('checked', true);
			OC.Journal.setEnabled(true);
		},
		createEntry:function(data) {
			var date = new Date(parseInt(data.dtstart)*1000);
			var timestring = (data.only_date?'':' ' + date.toLocaleTimeString());
			return $('<li data-id="'+data.id+'"><a href="'+OC.linkTo('journal', 'index.php')+'&id='+data.id+'">'+data.summary.unEscape()+'</a><br /><em>'+date.toDateString()+timestring+'<em></li>').data('entry', data);
		},
		loadEntry:function(id, data) {
			//$(document).off('change', '.property');
			console.log('loadEntry: ' + id + ': ' + data.summary);
			this.id = id;
			this.data = data;
			$('#entry').data('id', id);
			console.log('summary: ' + data.summary.unEscape());
			$('#summary').val(data.summary.unEscape());
			$('#organizer').val(data.organizer.value.split(':')[1]);
			var format = data.description.format;
			console.log('format: '+format);
			$('#description').rte(format, data.description.value.unEscape());
			$('#description').rte('mode', format);
			//$('#description').expandingTextarea('resize');
			(format=='html'&&$('#editable').get(0).checked?$('#editortoolbar li.richtext').show():$('#editortoolbar li.richtext').hide());
			$('#location').val(data.location);
			$('#categories').val(data.categories.join(','));
			$('#categories').multiple_autocomplete('option', 'source', categories);
			console.log('Trying to parse: '+data.dtstart);
			var date = new Date(parseInt(data.dtstart)*1000);
			//$('#dtstartdate').val(date.getDate()+'-'+date.getMonth()+'-'+date.getFullYear()); //
			$('#dtstartdate').datepicker('setDate', date);
			if(data.only_date) {
				$('#dtstarttime').hide();
				$('#also_time').attr('checked', false);
				//$('#also_time').get(0).checked = false;
			} else {
				// timepicker('setTime', ...) triggers a 'change' event, so you have to jump through hoops here ;-)
				$('#dtstarttime').val(date.getHours().toString().zeroPad(2)+':'+date.getMinutes().toString().zeroPad(2));
				$('#dtstarttime').show();
				$('#also_time').attr('checked', true);
				//$('#also_time').get(0).checked = true;
			}
			console.log('dtstart: '+date);
		},
		saveproperty:function(obj) {
			if(!this.id) { // we are adding an entry and want a response back from the server.
				this.id = 'new';
				console.log('OC.Journal.Entry.saveproperty: We need to add a new one.');
				//return;
			}
			var container = OC.Journal.propertyContainerFor(obj);
			var params = {'id':this.id};
			params['type'] = container.data('type');
			params['parameters'] = {};
			switch(params['type']) {
				case 'ORGANIZER':
				case 'LOCATION':
				case 'CATEGORIES':
					params['value'] = $(obj).val();
					break;
				case 'SUMMARY':
					if(this.id == 'new' && $(obj).val().trim() == '') {
						$(obj).focus();
						$(obj).addClass('required');
						$(obj).on('blur', OC.Journal.required);
						return;
					}
					params['value'] = $(obj).val();
					break;
				case 'DESCRIPTION':
					// Check if we get the description from the textarea or the contenteditable.
					var format = ($(obj).get(0).nodeName == 'DIV' ? 'html' : 'text'); // FIXME: should check rte instead.
					var value = $('#description').rte(format); // calls either the 'text' or 'html' method of the rte.
					//var value = ($(obj).get(0).nodeName == 'DIV' ? $(obj).html() : $(obj).text());
					console.log('nodeName: ' + $(obj).get(0).nodeName);
					params['value'] = value;
					params['parameters']['FORMAT'] = format.toUpperCase();
					break;
				case 'DTSTART':
					var date = $('#dtstartdate').val();
					var time = $('#dtstarttime').val();
					var datetime = new Date(parseInt(date.substring(6, 10)), parseInt(date.substring(3, 5)), parseInt(date.substring(0, 2)) , parseInt(time.substring(0, 2)), parseInt(time.substring(3, 5)), 0, 0);
					params['value'] = datetime.getTime()/1000;
					break;
				default:
					$.extend(1, $(obj).serializeArray(), params);
					break;
			}
			self = this;
			$.post(OC.filePath('journal', 'ajax', 'saveproperty.php'), params, function(jsondata) {
				if(jsondata.status == 'success') {
					if(self.id == 'new') {
						self.loadEntry(jsondata.data.id, jsondata.data);
					} else {
						$('#leftcontent li[data-id="'+self.id+'"]').remove();
					}
					var item = self.createEntry(jsondata.data);
					$('#leftcontent').append(item);
					OC.Journal.Journals.doSort();
					OC.Journal.Journals.scrollTo(self.id);
					// add error checking
					console.log('successful save');
				} else {
					OC.dialogs.alert(jsondata.data.message.text, t('contacts', 'Error'));
				}
			});
		},
		doExport:function() {
			document.location.href = OC.linkTo('calendar', 'export.php') + '?eventid=' + this.id;
		},
		doDelete:function() {
			// TODO: Do something when there are no more entries.
			if(this.id == 'new') { return; }
			$('#delete').tipsy('hide');
			self = this;
			OC.dialogs.confirm(t('contacts', 'Are you sure you want to delete this entry?'), t('journal', 'Warning'), function(answer) {
				if(answer == true) {
					$.post(OC.filePath('journal', 'ajax', 'delete.php'), {'id': self.id}, function(jsondata) {
						if(jsondata.status == 'success') {
							var curlistitem = $('#leftcontent li[data-id="'+self.id+'"]');
							var newlistitem = curlistitem.prev('li');
							if(!$(newlistitem).is('li')) {
								newlistitem = curlistitem.next('li');
							}
							if(!$(newlistitem).is('li')) {
								alert('No more entries. Do something!!!');
							}
							$(newlistitem).addClass('active');
							console.log('newlistitem: ' + newlistitem.toString());
							curlistitem.remove();
							var data = newlistitem.data('entry');
							self.loadEntry(data.id, data);
							console.log('successful delete');
						} else {
							OC.dialogs.alert(jsondata.data.message.text, t('contacts', 'Error'));
						}
					});
				}
			});
		},
	},
	Journals:{
		sortmethod:undefined,
		doSort:function(method) {
			if(method) {
				this.sortmethod = method;
			} else {
				 method = this.sortmethod;
			}
			// Thanks to http://www.java2s.com/Tutorial/JavaScript/0220__Array/Usinganalphabeticalsortmethodonstrings.html
			// and http://stackoverflow.com/questions/4258974/sort-list-based-on-data-attribute-using-jquery-metadata-plugin#4259074
			// and http://stackoverflow.com/questions/8882418/jquery-sorting-lib-that-supports-multilanguage-sorting
			compareDateTimeAsc = function(a, b){
				return (parseInt(a.dtstart) > parseInt(b.dtstart)?-1:1);
			}
			compareDateTimeDesc = function(a, b){
				return (parseInt(b.dtstart) < parseInt(a.dtstart)?-1:1);
			}
			compareSummaryAsc = function(a, b){
				return b.summary.toLowerCase().localeCompare(a.summary.toLowerCase());
			}
			compareSummaryDesc = function(a, b){
				return a.summary.toLowerCase().localeCompare(b.summary.toLowerCase());
			}
			var func;
			switch(method) {
				case 'dtasc':
					func = compareDateTimeAsc;
					break;
				case 'dtdesc':
					func = compareDateTimeDesc;
					break;
				case 'sumasc':
					func = compareSummaryAsc;
					break;
				case 'sumdesc':
					func = compareSummaryDesc;
					break;
				default:
					var func = compareDateTimeDesc;
					break;
			}

			var arr = []
			// loop through each list item and get the metadata
			$('#leftcontent li').each(function () {  
				var meta = $(this).data('entry');
				meta.elem = $(this);
				arr.push(meta);
			});
			arr.sort(func);

			//Foreach item append it to the container. The first i arr will then end up in the top
			$.each(arr, function(index, item){
				item.elem.appendTo(item.elem.parent());
			});
		},
		update:function(id) {
			console.log('update: ' + id);
			self = this;
			$('#leftcontent').addClass('loading');
			$.getJSON(OC.filePath('journal', 'ajax', 'entries.php'), function(jsondata) {
				if(jsondata.status == 'success') {
					var entries = $('#leftcontent').empty();
					$(jsondata.data.entries).each(function(i, entry) {
						entries.append(OC.Journal.Entry.createEntry(entry));
					});
					$('#leftcontent').removeClass('loading');
					self.doSort('dtasc');
					console.log('Count: ' + $('#leftcontent li').length);
					if($('#leftcontent li').length > 0 ){
						var firstitem;
						if(id) {
							firstitem = $('#leftcontent li[data-id="'+id+'"]');
						} else {
							firstitem = $('#leftcontent li').first();
							id = firstitem.data('entry').id;
						}
						firstitem.addClass('active');
						self.scrollTo(id);
						OC.Journal.Entry.loadEntry(firstitem.data('id'), firstitem.data('entry'));
					}
				} else {
					OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
				}
			});
		},
		scrollTo:function(id){
			var item = $('#leftcontent li[data-id="'+id+'"]');
			if(item) {
				try {
					$('#leftcontent').animate({scrollTop: $('#leftcontent li[data-id="'+id+'"]').offset().top-70}, 'slow','swing');
				} catch(e) {}
			}
		}
	}
};

$(document).ready(function(){
	OCCategories.changed = OC.Journal.categoriesChanged;
	OCCategories.app = 'calendar';

	// Initialize controls.
	$('#categories').multiple_autocomplete({source: categories});
	//$('#categories').multiple_autocomplete('option', 'source', categories);
	$('#dtstartdate').datepicker({dateFormat: 'dd-mm-yy'});
	$('#dtstarttime').timepicker({timeFormat: 'hh:mm', showPeriodLabels:false});
	$('#description').rte({classes: ['property','content']});
	$('.tip').tipsy();
	
	OC.Journal.init();
	
	// Show the input with a direcy link the journal entry, binds an event to close
	// it on blur and removes the binding again afterwards.
	$('#showlink').on('click', function(event){
		console.log('showlink');
		$('#link').toggle('slow').val(totalurl+'&id='+OC.Journal.Entry.id).focus().
			on('blur',function(event) {$(this).hide()}).off('blur', $(this));
		return false;
	});

	$('#rightcontent').on('change', '.property', function(event){
		OC.Journal.Entry.saveproperty(this);
	});

	$('#controls').on('click', '#add', function(event){
		OC.Journal.Entry.add();
	});

	$('#metadata').on('change', '#also_time', function(event){
		$('#dtstarttime').toggle().trigger('change');
	});
	
	$('#metadata').on('click', '#export', function(event){
		OC.Journal.Entry.doExport();
	});

	$('#metadata').on('click', '#editcategories', function(event){
		OCCategories.edit();
	});

	$('#metadata').on('click', '#delete', function(event){
		OC.Journal.Entry.doDelete();
	});

	$('#controls').on('change', '#entrysort', function(event){
		OC.Journal.Journals.doSort($(this).val());
	});

	// Proxy click.
	$('#leftcontent').on('keydown', '#leftcontent', function(event){
		if(event.which == 13) {
			$('#leftcontent').click(event);
		}
	});
	// Journal entry clicked
	$(document).on('click', '#leftcontent', function(event){
		var $tgt = $(event.target);
		var item = $tgt.is('li')?$($tgt):($tgt).parents('li').first();
		var id = item.data('id');
		item.addClass('active');
		var oldid = $('#entry').data('id');
		console.log('oldid: ' + oldid);
		if(oldid != 0){
			$('#leftcontent li[data-id="'+oldid+'"]').removeClass('active');
		}
		OC.Journal.Entry.loadEntry(id, item.data('entry'));
		return false;
	});
	// Editor command.
	$('.rte-toolbar button').on('click', function(event){
		console.log('cmd: ' + $(this).data('cmd'));
		$('#description').rte('formatText', $(this).data('cmd'));
		event.preventDefault();
		return false;
	});
	// Toggle text/html editing mode.
	$('#togglemode').on('click', function(event){
		OC.Journal.toggleMode(true);
		return false;
	});
	$('#editable').on('change', function(event){
		OC.Journal.setEnabled($(this).get(0).checked);
	});
	
});
