function ListView(element){
	this.element=element;
}

ListView.generateTable=function(collumns){
	var html='<table>';
	html+='<thead>';
	$.each(collumns,function(index,collumn){
		html+='<th>'+collumn+'</th>';
	});
	html+='<thead>';
	html+='</head>';
	html+='<tbody>';
	html+'<tr class="template">'
	$.each(collumns,function(index,collumn){
		html+='<th class="'+collumn.toLower()+'"</th>';
	});
	html+'</tr>'
	html+='</tbody>';
	html='</table>';
	return $(html);
}

ListView.prototype={
	rows:{},
	hoverElements:{},
	addRow:function(id,data,extraData){
		var tr=this.element.find('tr.template').clone();
		tr.removeClass('template');
		$.each(data,function(name,value){
			tr.children('td.'+name).text(value);
			tr.attr('data-'+name,value);
		});
		$.each(extraData,function(name,value){
			tr.attr('data-'+name,value);
		});
		this.rows[id]=data;
		tr.data('id',id);
		this.element.children('tbody').append(tr);
	},
	removeRow:function(id){
		this.rows[id].remove();
		delete this.rows[id];
	},
	hoverHandeler:function(tr){
		$.each(this.hoverElement,function(index,collumn){
			$.each(collumn,function(index,element){
				var html='<a href="#" title="'+element.title+'" class="hoverElement"/>';
				var element=$(html);
				element.append($('<img src="'+element.icon+'"/>'));
				element.click(element.callback);
				tr.children('td.'+collumn).append(element)
			});
		});
		if(this.deleteCallback){
			
		}
	},
	hoverHandelerOut:function(tr){
		tr.find('*.hoverElement').remove();
	},
	addHoverElement:function(collumn,icon,title,callback){
		if(!this.hoverElements[collumn]){
			this.hoverElements[collumn]=[];
		}
		this.hoverElements[row].push({icon:icon,callback:callback,title:title});
	},
	empty:function(){
		this.element.children('tr:not(.template)').remove();
	}
}