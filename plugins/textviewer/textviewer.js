OC_TextViewer=new Object();

OC_TextViewer.type='';
OC_TextViewer.types={
	'ac3':'shBrushAS3.js',
	'applescript':'shBrushAppleScript.js',
	'bash':'shBrushBash.js',
	'sh':'shBrushBash.js',
	'csharp':'shBrushCSharp.js',
	'coldfusion':'shBrushColdFusion.js',
	'cpp':'shBrushCpp.js',
	'css':'shBrushCss.js',
	'delphi':'shBrushDelphi.js',
	'diff':'shBrushDiff.js',
	'erlang':'shBrushErlang.js',
	'groovy':'shBrushGroovy.js',
	'javascript':'shBrushJScript.js',
	'js':'shBrushJScript.js',
	'java':'shBrushJava.js',
	'javafx':'shBrushJavaFX.js',
	'perl':'shBrushPerl.js',
	'php':'shBrushPhp.js',
	'plain':'shBrushPlain.js',
	'powershell':'shBrushPowerShell.js',
	'python':'shBrushPython.js',
	'py':'shBrushPython.js',
	'ruby':'shBrushRuby.js',
	'sass':'shBrushSass.js',
	'scala':'shBrushScala.js',
	'sql':'shBrushSql.js',
	'vb':'shBrushVb.js',
	'xml':'shBrushXml.js',
}

OC_TextViewer.loader=new OCXMLLoader();
OC_TextViewer.showText=function(dir,file){
	var type;
	var parts=file.split('.');
	var ext=parts[parts.length-1];
	if(OC_TextViewer.types[ext]){
		type=ext;
	}else{
		type='plain';
	}
	OC_TextViewer.type=type;
	OC_TextViewer.loadHighlighter();
	var path=WEBROOT+'/files/open_file.php?dir='+encodeURIComponent(dir)+'&file='+encodeURIComponent(file);
	var div=document.createElement('div');
	div.setAttribute('id','textframe');
	div.setAttribute('class','center');
	div.addEvent('onclick',OC_TextViewer.hideText)
	OC_TextViewer.textFrame=document.createElement('div');
	OC_TextViewer.textFrame.addEvent('onclick',function(e){
		if(window.event = true){
			window.event.cancelBubble = true;
		}
		if(e.stopPropagation){
			e.stopPropagation();
		}
	});
	OC_TextViewer.textFrame.pre=document.createElement('pre');
	div.appendChild(OC_TextViewer.textFrame);
	OC_TextViewer.textFrame.appendChild(OC_TextViewer.textFrame.pre);
	body=document.getElementsByTagName('body').item(0);
	body.appendChild(div);
	OC_TextViewer.loader.setCallBack(OC_TextViewer.showTexCallback);
	OC_TextViewer.loader.load(path);
}

OC_TextViewer.showTexCallback=function(req){
	var text=req.responseText;
	OC_TextViewer.textFrame.pre.innerHTML=OC_TextViewer.prepareText(text);
	OC_TextViewer.textFrame.pre.setAttribute('class','brush: '+OC_TextViewer.type+';');
	SyntaxHighlighter.highlight(null,OC_TextViewer.textFrame.pre);
}

OC_TextViewer.hideText=function(){
	var div=document.getElementById('textframe');
	div.parentNode.removeChild(div);
} 

OC_TextViewer.prepareText=function(text){
	text=text.replace(/>/g,"&gt;");
	text=text.replace(/</g,"&lt;");
	return text;
}

OC_TextViewer.loadedTypes=new Array();
OC_TextViewer.loadHighlighter=function(){
	OC_TextViewer.type=(OC_TextViewer.types[OC_TextViewer.type])?OC_TextViewer.type:'plain';
	if(!OC_TextViewer.loadedTypes[OC_TextViewer.type]){
		loadScript('plugins/textviewer/syntaxhighlighter/scripts/'+OC_TextViewer.types[OC_TextViewer.type])
		OC_TextViewer.loadedTypes[OC_TextViewer.type]=true;
		SyntaxHighlighter.vars.discoveredBrushes=null; //force the highlighter to refresh it's cache
	}
}

if(!OC_FILES.fileActions.text){
	OC_FILES.fileActions.text=new Object()
}
OC_FILES.fileActions.text.show=function(){
	OC_TextViewer.showText(this.dir,this.file);
}

OC_FILES.fileActions.text['default']=OC_FILES.fileActions.text.show;

