Albums={
  // album item in this array should look as follow
  // {name: string,
  //  numOfCovers: int}
  //
  // previews array should be an array of base64 decoded images
  // to display to user as preview picture when scrolling throught
  // the album cover
  albums:new Array(),
  // add simply adds new album to internal structure
  // however albums names must be unique so other
  // album with the same name wont be insered,
  // and false will be returned
  // true on success
  add: function(album_name, num) {
    for (var a in Albums.albums) {
      if (a.name == album_name) {
        return false;
      }
    }
    Albums.albums.push({name: album_name, numOfCovers: num});
    return true;
  },
  // remove element with given name
  // returns remove element or undefined if no such element was present
  remove: function(name) {
    var i = -1, tmp = 0;
    for (var a in Albums.albums) {
      if (a.name == name) {
        i = tmp;
        break;
      }
      tmp++;
    }
    if (i != -1) {
      return Albums.albums.splice(i,1);
    }
    return undefined;
  },
  // return element which match given name
  // of undefined if such element do not exist
  find: function(name) {
    var i = -1, tmp = 0;
    for (var k in Albums.albums) {
      var a = Albums.albums[k];
      if (a.name == name) {
        i = tmp;
        break;
      }
      tmp++;
    }
    if (i != -1) {
      return Albums.albums[i];
    }
    return undefined;
  },
  // displays gallery in linear representation
  // on given element, and apply default styles for gallery
  display: function(element) {
    var displayTemplate = '<div id="gallery_album_box" title="*NAME*"><a href="?view=*NAME*"><div id="gallery_album_cover"></div></a><h1>*NAME*</h1></div></div>';
    for (var i in Albums.albums) {
      var a = Albums.albums[i];
      var local = $(displayTemplate.replace(/\*NAME\*/g, a.name));
      local.css('background-repeat', 'no-repeat');
      local.css('background-position', '0 0');
      local.css('background-image','url("ajax/getCovers.php?album_name='+a.name+'")');
      local.mousemove(function(e) {
        var albumMetadata = Albums.find(this.title);
        if (albumMetadata == undefined) {
          return;
        }
        var x = Math.min(Math.floor((e.layerX - this.offsetLeft)/(this.offsetWidth/albumMetadata.numOfCovers)), albumMetadata.numOfCovers-1);
        x *= this.offsetWidth;
        $(this).css('background-position', -x+'px 0');
      });
      $(element).append(local);
    }
  }

}
