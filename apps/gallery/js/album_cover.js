var actual_cover;
$(document).ready(function() {
  $.getJSON('ajax/getAlbums.php', function(r) {
    if (r.status == 'success') {
      for (var i in r.albums) {
        var a = r.albums[i];
        Albums.add(a.name, a.numOfItems, a.bgPath);
      }
      var targetDiv = document.getElementById('gallery_list');
      if (targetDiv) {
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

function scanForAlbums() {
  $("#notification").fadeIn();
  $("#notification").slideDown();
  $.getJSON('ajax/scanForAlbums.php', function(r) {
    $("#notification").fadeOut();
    $("#notification").slideUp();
    if (r.status == 'success') {
      window.location.reload(true);
    } else {
      alert('Error occured: ' + r.message);
    }
  });
}

function galleryRemove(albumName) {
  if (confirm("Do you wan't to remove album " + albumName + "?")) {
	$.getJSON("ajax/galleryOp.php", {operation: "remove", name: albumName}, function(r) {
	  if (r.status == "success") {
		$("#gallery_album_box[title='"+albumName+"']").remove();
		Albums.remove(albumName);
	  } else {
		alert("Error: " + r.cause);
	  }
	});
  }
}

function galleryRename(name) {
  var result = window.prompt("Input new gallery name", "");
  if (result) {
	if (Albums.find(result)) {
	  alert("Album named '" + result + "' already exists");
	  return;
	}
	$.getJSON("ajax/galleryOp.php", {operation: "rename", oldname: name, newname: result}, function(r) {
	  if (r.status == "success") {
        Albums.rename($("#gallery_album_box[title='"+name+"']"), result);
      } else {
	    alert("Error: " + r.cause);
      }
	});
	
  } else {
    alert("Album name can't be empty")
  }
}

