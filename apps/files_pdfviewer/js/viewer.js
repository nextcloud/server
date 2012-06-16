function hidePDFviewer() {
	showPDFviewer.shown = false;
	$('table').show();
	$('#controls').html(showPDFviewer.oldcode);
	$("#viewer").remove();
	$("#loading").remove()
	$("#editor").show();
	document.title = showPDFviewer.lastTitle;
	PDFView.active=false;
	$('iframe').remove();
}

function showPDFviewer(dir,filename){
	if(!showPDFviewer.shown){
		$("#editor").hide();
		var url = OC.filePath('files','ajax','download.php')+encodeURIComponent('?files='+encodeURIComponent(filename)+"&dir="+encodeURIComponent(dir));
		$('table').hide();
		function im(path) { return OC.filePath('files_pdfviewer','js','pdfjs/web/images/'+path); }
		showPDFviewer.oldcode = $("#controls").html();
		$("#controls").empty();
		$("#controls").html('<button id="previous" onclick="PDFView.page--;" oncontextmenu="return false;"><img src="'+im('go-up.svg')+'" align="top" height="10"/>Previous</button><button id="next" onclick="PDFView.page++;" oncontextmenu="return false;"><img src="'+im('go-down.svg')+'" align="top" height="10"/>Next</button><div class="separator"></div><input style="width:25px;" type="number" id="pageNumber" onchange="PDFView.page = this.value;" value="1" size="4" min="1" /><span>/</span><span id="numPages">--</span><div class="separator"></div><button id="zoomOut" title="Zoom Out" onclick="PDFView.zoomOut();" oncontextmenu="return false;"><img src="'+im('zoom-out.svg')+'" align="top" height="10"/></button><button id="zoomIn" title="Zoom In" onclick="PDFView.zoomIn();" oncontextmenu="return false;"><img src="'+im('zoom-in.svg')+
			'" align="top" height="10"/></button><div class="separator"></div><select id="scaleSelect" onchange="PDFView.parseScale(this.value);" oncontextmenu="return false;"><option id="customScaleOption" value="custom"></option><option value="0.5">50%</option><option value="0.75">75%</option><option value="1">100%</option><option value="1.25" selected="selected">125%</option><option value="1.5">150%</option><option value="2">200%</option><option id="pageWidthOption" value="page-width">Page Width</option><option id="pageFitOption" value="page-fit">Page Fit</option></select><div class="separator"></div><button id="print" onclick="window.print();" oncontextmenu="return false;"><img src="'+im('document-print.svg')+'" align="top" height="10"/>Print</button><button id="download" title="Download" onclick="PDFView.download();" oncontextmenu="return false;">'+
			'<img src="'+im('download.svg')+'" align="top" height="10"/>Download</button><button id="close" title="Close viewer" onclick="hidePDFviewer();" oncontextmenu="return false;">x</button><span id="info">--</span></div>');
		var oldcontent = $("#content").html();
		$("#content").html(oldcontent+'<div id="loading">Loading... 0%</div><div id="viewer"></div>');
		showPDFviewer.lastTitle = document.title;
		if(!showPDFviewer.loaded){
			OC.addScript( 'files_pdfviewer', 'pdfjs/build/pdf',function(){
				OC.addScript( 'files_pdfviewer', 'pdfview',function(){
					showPDFviewer.loaded=true;
					PDFJS.workerSrc = OC.filePath('files_pdfviewer','js','pdfjs/build/pdf.js');
					PDFView.Ptitle = filename;
					PDFView.open(url,1.00);
					PDFView.active=true;
				});
			});
		}else{
			PDFView.Ptitle = filename;
			PDFView.open(url,1.00);
			PDFView.active=true;
		}
		$("#pageWidthOption").attr("selected","selected");
		showPDFviewer.shown = true;
	}
}
showPDFviewer.shown=false;
showPDFviewer.oldCode='';
showPDFviewer.lastTitle='';
showPDFviewer.loaded=false;

$(document).ready(function(){
	if(!$.browser.msie){//doesnt work on IE
		if(location.href.indexOf("files")!=-1) {
			PDFJS.workerSrc = OC.filePath('files_pdfviewer','js','pdfjs/build/pdf.js');
			if(typeof FileActions!=='undefined'){
				FileActions.register('application/pdf','Edit','',function(filename){
					showPDFviewer($('#dir').val(),filename);
				});
				FileActions.setDefault('application/pdf','Edit');
			}
		}
	}
});
