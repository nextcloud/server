TextViewer=new Object();

TextViewer.type='';
TextViewer.types={
	'ac3':'shBrushAS3',
	'applescript':'shBrushAppleScript',
	'bash':'shBrushBash',
	'sh':'shBrushBash',
	'csharp':'shBrushCSharp',
	'coldfusion':'shBrushColdFusion',
	'cpp':'shBrushCpp',
	'css':'shBrushCss',
	'delphi':'shBrushDelphi',
	'diff':'shBrushDiff',
	'erlang':'shBrushErlang',
	'groovy':'shBrushGroovy',
	'javascript':'shBrushJScript',
	'js':'shBrushJScript',
	'java':'shBrushJava',
	'javafx':'shBrushJavaFX',
	'perl':'shBrushPerl',
	'php':'shBrushPhp',
	'plain':'shBrushPlain',
	'powershell':'shBrushPowerShell',
	'python':'shBrushPython',
	'py':'shBrushPython',
	'ruby':'shBrushRuby',
	'sass':'shBrushSass',
	'scala':'shBrushScala',
	'sql':'shBrushSql',
	'vb':'shBrushVb',
	'xml':'shBrushXml',
}

TextViewer.load=function(ready){
	if(!TextViewer.load.done){
		OC.addStyle('files_textviewer','syntaxhighlighter/shCoreDefault');
		OC.addStyle('files_textviewer','syntaxhighlighter/shCore');
		OC.addStyle('files_textviewer','syntaxhighlighter/shThemeDefault');
		OC.addStyle('files_textviewer','style');
		OC.addScript('files_textviewer','syntaxhighlighter/shCore',function(){
			if(ready){
				ready();
			}
		});
	}else if(ready){
		ready();
	}
}
TextViewer.load.done=false;

TextViewer.showText=function(dir,file){
	var type;
	var parts=file.split('.');
	var ext=parts[parts.length-1];
	if(TextViewer.types[ext]){
		type=ext;
	}else{
		type='plain';
	}
	TextViewer.type=type;
	var div=$('<div id="textframe" class="center"></div>');
	div.click(TextViewer.hideText);
	TextViewer.textFrame=$('<div></div>');
	TextViewer.textFrame.click(function(event){
		event.stopPropagation();
	});
	TextViewer.textFrame.pre=$('<pre></pre>');
	div.append(TextViewer.textFrame);
	TextViewer.textFrame.append(TextViewer.textFrame.pre);
	$('body').append(div);
	TextViewer.loadHighlighter(function(){
		$.ajax({
			url: OC.filePath('files','ajax','download.php')+'?files='+encodeURIComponent(file)+'&dir='+encodeURIComponent(dir),
			complete: function(text){
				TextViewer.textFrame.pre.text(text.responseText);
				TextViewer.textFrame.pre.attr('class','brush: '+TextViewer.type+';');
				SyntaxHighlighter.highlight(null,TextViewer.textFrame.pre[0]);
			}
		});
	});
}

TextViewer.hideText=function(){
	$('#textframe').remove();
} 

TextViewer.loadedTypes=new Array();
TextViewer.loadHighlighter=function(ready){
	TextViewer.load(function(){
		TextViewer.type=(TextViewer.types[TextViewer.type])?TextViewer.type:'plain';
		if(!TextViewer.loadedTypes[TextViewer.type]){
			OC.addScript('files_textviewer','syntaxhighlighter/'+TextViewer.types[TextViewer.type],function(){
				TextViewer.loadedTypes[TextViewer.type]=true;
				SyntaxHighlighter.vars.discoveredBrushes=null; //force the highlighter to refresh it's cache
				if(ready){
					ready();
				}
			});
		}else{
			if(ready){
				ready();
			};
		}
	});
}

$(document).ready(function() {
	if(typeof FileActions!=='undefined'){
		FileActions.register('text','View','',function(filename){
			TextViewer.showText($('#dir').val(),filename);
		});
		FileActions.setDefault('text','View');
		FileActions.register('application/xml','View','',function(filename){
			TextViewer.showText($('#dir').val(),filename);
		});
		FileActions.setDefault('application/xml','View');
	}
});
