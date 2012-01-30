var actual_cover;
$(document).ready(function() {
  $.getJSON('ajax/getAlbums.php', function(r) {
    if (r.status == 'success') {
      for (var i in r.albums) {
        var a = r.albums[i];
        Albums.add(a.name, a.numOfItems);
      }
      var targetDiv = document.getElementById('gallery_list');
      if (targetDiv) {
        $(targetDiv).html('');
        Albums.display(targetDiv);
      } else {
        alert('Error occured: no such layer `gallery_list`');
      }
    } else {
      alert('Error occured: ' + r.message);
    }
  });
});

function createNewAlbum() {
  var name = prompt("album name", "");
  if (name != null && name != "") {
    $.getJSON("ajax/createAlbum.php", {album_name: name}, function(r) {
      if (r.status == "success") {
        var v = '<div class="gallery_album_box"><a href="?view='+r.name+'"><img class="gallery_album_cover"/></a><h1>'+r.name+'</h1></div>';
        $('div#gallery_list').append(v);
      }
    });
  }
}

var albumCounter = 0;
var totalAlbums = 0;

function scanForAlbums() {
  var albumCounter = 0;
  var totalAlbums = 0;
  $('#notification').text(t('gallery',"Scanning directories"));
  $("#notification").fadeIn();
  $("#notification").slideDown();
  $.getJSON('ajax/galleryOp.php?operation=filescan', function(r) {

    if (r.status == 'success') {
      totalAlbums = r.paths.length;
      if (totalAlbums == 0) {
        $('#notification').text(t('gallery', "No photos found")).fadeIn().slideDown().delay(3000).fadeOut().slideUp();
        return;
      }
	    $('#notification').text(t('gallery',"Creating thumbnails")+' ... ' + Math.floor((albumCounter/totalAlbums)*100) + "%");
      for(var a in r.paths) {
        $.getJSON('ajax/galleryOp.php?operation=partial_create&path='+r.paths[a], function(r) {

          if (r.status == 'success') {
            Albums.add(r.album_details.albumName, r.album_details.imagesCount);
          }

          albumCounter++;
		  $('#notification').text(t('gallery',"Creating thumbnails")+' ... ' + Math.floor((albumCounter/totalAlbums)*100) + "%");
          if (albumCounter == totalAlbums) {
            $("#notification").fadeOut();
            $("#notification").slideUp();
            var targetDiv = document.getElementById('gallery_list');
            if (targetDiv) {
              targetDiv.innerHTML = '';
              Albums.display(targetDiv);
            } else {
              alert('Error occured: no such layer `gallery_list`');
            }
          }
        });
      }
    } else {
      alert('Error occured: ' + r.message);
    }
  });
}

function galleryRemove(albumName) {
  if (confirm(t('gallery',"Do you wan't to remove album")+' ' + albumName + "?")) {
	$.getJSON("ajax/galleryOp.php", {operation: "remove", name: albumName}, function(r) {
	  if (r.status == "success") {
		$(".gallery_album_box").filterAttr('data-album',albumName).remove();
		Albums.remove(albumName);
	  } else {
		alert("Error: " + r.cause);
	  }
	});
  }
}

function galleryRename(name) {
  var result = window.prompt(t('gallery',"Input new gallery name"), name);
  if(result=='' || result==name){
	return;
  }
  if (result) {
	if (Albums.find(result)) {
	  alert("Album named '" + result + "' already exists");
	  return;
	}
	$.getJSON("ajax/galleryOp.php", {operation: "rename", oldname: name, newname: result}, function(r) {
	  if (r.status == "success") {
		  Albums.rename($(".gallery_album_box").filterAttr('data-album',name), result);
      } else {
	    alert("Error: " + r.cause);
      }
	});
	
  }
}

