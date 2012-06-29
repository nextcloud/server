(function( $ ){
	var multiSelectId=-1;
	$.fn.multiSelect=function(options){
		multiSelectId++;
		var settings = {
			'createCallback':false,
			'createText':false,
			'title':this.attr('title'),
			'checked':[],
			'oncheck':false,
			'onuncheck':false,
			'minWidth': 'default;',
		};
		$.extend(settings,options);
		$.each(this.children(),function(i,option){
			if($(option).attr('selected') && settings.checked.indexOf($(option).val())==-1){
				settings.checked.push($(option).val());
			}
		});
		var button=$('<div class="multiselect button"><span>'+settings.title+'</span><span>▾</span></div>');
		var span=$('<span/>');
		span.append(button);
		button.data('id',multiSelectId);
		button.selectedItems=[];
		this.hide();
		this.before(span);
		if(settings.minWidth=='default'){
			settings.minWidth=button.width();
		}
		button.css('min-width',settings.minWidth);
		settings.minOuterWidth=button.outerWidth()-2;
		button.data('settings',settings);
		if(settings.checked.length>0){
			button.children('span').first().text(settings.checked.join(', '));
		}

		button.click(function(event){
			
			var button=$(this);
			if(button.parent().children('ul').length>0){
				button.parent().children('ul').slideUp(400,function(){
					button.parent().children('ul').remove();
					button.removeClass('active');
				});
				return;
			}
			var lists=$('ul.multiselectoptions');
			lists.slideUp(400,function(){
				lists.remove();
				$('div.multiselect').removeClass('active');
				button.addClass('active');
			});
			button.addClass('active');
			event.stopPropagation();
			var options=$(this).parent().next().children();
			var list=$('<ul class="multiselectoptions"/>').hide().appendTo($(this).parent());
			function createItem(element,checked){
				element=$(element);
				var item=element.val();
				var id='ms'+multiSelectId+'-option-'+item;
				var input=$('<input type="checkbox"/>');
				input.attr('id',id);
				var label=$('<label/>');
				label.attr('for',id);
				label.text(item);
				if(settings.checked.indexOf(item)!=-1 || checked){
					input.attr('checked',true);
				}
				if(checked){
					settings.checked.push(item);
				}
				input.change(function(){
					var groupname=$(this).next().text();
					if($(this).is(':checked')){
						element.attr('selected','selected');
						if(settings.oncheck){
							if(settings.oncheck(groupname)===false){
								$(this).attr('checked', false);
								return;
							}
						}
						settings.checked.push(groupname);
					}else{
						var index=settings.checked.indexOf(groupname);
						element.attr('selected',null);
						if(settings.onuncheck){
							if(settings.onuncheck(groupname)===false){
								$(this).attr('checked',true);
								return;
							}
						}
						settings.checked.splice(index,1);
					}
					var oldWidth=button.width();
					if(settings.checked.length>0){
						button.children('span').first().text(settings.checked.join(', '));
					}else{
						button.children('span').first().text(settings.title);
					}
					var newOuterWidth=Math.max((button.outerWidth()-2),settings.minOuterWidth)+'px';
					var newWidth=Math.max(button.width(),settings.minWidth);
					var pos=button.position();
					button.css('height',button.height());
					button.css('white-space','nowrap');
					button.css('width',oldWidth);
					button.animate({'width':newWidth},undefined,undefined,function(){
						button.css('width','');
					});
					list.animate({'width':newOuterWidth,'left':pos.left+3});
				});
				var li=$('<li></li>');
				li.append(input).append(label);
				return li;
			}
			$.each(options,function(index,item){
				list.append(createItem(item));
			});
			button.parent().data('preventHide',false);
			if(settings.createText){
				var li=$('<li>+ <em>'+settings.createText+'<em></li>');
				li.click(function(event){
					li.empty();
					var input=$('<input class="new">');
					li.append(input);
					input.focus();
					input.css('width',button.width());
					button.parent().data('preventHide',true);
					input.keypress(function(event) {
						if(event.keyCode == 13) {
							event.preventDefault();
							event.stopPropagation();
							var value = $(this).val();
							var exists = false;
							$.each(options,function(index, item) {
								if ($(item).val() == value) {
									exists = true;
									return false;
								}
							});
							if (exists) {
								return false;
							}
							var li=$(this).parent();
							$(this).remove();
							li.text('+ '+settings.createText);
							li.before(createItem(this));
							var select=button.parent().next();
							var option=$('<option selected="selected"/>');
							option.attr('value',value);
							option.text($(this).val());
							select.append(option);
							li.prev().children('input').trigger('click');
							button.parent().data('preventHide',false);
							if(settings.createCallback){
								settings.createCallback($(this).val());
							}
						}
					});
					input.blur(function(){
						event.preventDefault();
						event.stopPropagation();
						$(this).remove();
						li.text('+ '+settings.createText);
						setTimeout(function(){
							button.parent().data('preventHide',false);
						},100);
					});
				});
				list.append(li);
			}
			var pos=button.position();
			list.css('top',pos.top+button.outerHeight()-5);
			list.css('left',pos.left+3);
			list.css('width',(button.outerWidth()-2)+'px');
			list.slideDown();
			list.click(function(event){
				event.stopPropagation();
			});
		});
		$(window).click(function(){
			if(!button.parent().data('preventHide')){
				button.parent().children('ul').slideUp(400,function(){
					button.parent().children('ul').remove();
					button.removeClass('active');
				});
			}
		});
		
		return span;
	};
})( jQuery );