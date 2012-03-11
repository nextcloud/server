Scanner={
  albumsFound:0,
  eventSource:null,
  albumsScanned:0,
  scanAlbums:function(callback){
    $('#scanprogressbar').progressbar({value:0});
    $('#scanprogressbar').fadeIn();
    $('#scan input.start').hide();
    $('#scan input.stop').show();
    Scanner.albumsScanned=0;
    Scanner.eventSource=new OC.EventSource(OC.linkTo('gallery', 'ajax/galleryOp.php'),{operation:'scan'});
    Scanner.eventSource.listen('count', function(total){Scanner.albumsFound=total;});
    Scanner.eventSource.listen('scanned', function(data) {
      Scanner.albumsScanned++;
      var progress=(Scanner.albumsScanned/Scanner.albumsFound)*100;
      $('#scanprogressbar').progressbar('value',progress);
    });
    Scanner.eventSource.listen('done', function(count){
			$('#scan input.start').show();
			$('#scan input.stop').hide();
      $('#scanprogressbar').fadeOut();
      returnToElement(0);
    });
    if (callback)
      callback();
  },
  stop:function() {
    Scanner.eventSource.close();
			$('#scan input.start').show();
			$('#scan input.stop').hide();
    $('#scanprogressbar').fadeOut();
  }
}

