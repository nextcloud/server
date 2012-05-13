Albums={
	// album item in this array should look as follow
	// {name: string,
	//  numOfCovers: int}
	//
	// previews array should be an array of base64 decoded images
	// to display to user as preview picture when scrolling throught
	// the album cover
	albums:new Array(),
	photos:new Array(),
  shared: false,
  recursive: false,
  token: '',
	// add simply adds new album to internal structure
	// however albums names must be unique so other
	// album with the same name wont be insered,
	// and false will be returned
	// true on success
	add: function(album_name, num, path) {
		if (Albums.albums[album_name] != undefined) return false;
		Albums.albums[album_name] = {name: album_name, numOfCovers: num, path:path};
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
		return Albums.albums[name];
	},
	// displays gallery in linear representation
	// on given element, and apply default styles for gallery
	display: function(element) {
		var displayTemplate = '<div class="gallery_box album"><div class="dummy"></div><a class="view"><div class="gallery_album_cover"></div></a><h1></h1></div>';
		for (var i in Albums.albums) {
			var a = Albums.albums[i];
			var local=$(displayTemplate);
			local.attr('title', a.name);
			local.attr('data-path', a.path);
			local.attr('data-album',a.name);
			$(".gallery_album_decoration a.rename", local).bind('click', {name: a.name},function(name,event){
				event.preventDefault();
				event.stopPropagation();
				galleryRename(name);
			}.bind(null,a.name));
			$(".gallery_album_decoration a.remove", local).bind('click', {name: a.name},function(name,event){
				event.preventDefault();
				event.stopPropagation();
				galleryRemove(name);
			}.bind(null,a.name));
			$('h1',local).text(decodeURIComponent(escape(a.name)));
			$(".gallery_album_cover", local).attr('title',decodeURIComponent(escape(a.name)));
			$(".gallery_album_cover", local).css('background-repeat', 'no-repeat');
			$(".gallery_album_cover", local).css('background-position', '0');
			$(".gallery_album_cover", local).css('background-image','url("'+OC.filePath('gallery','ajax','galleryOp.php')+'&operation=get_covers&albumname='+escape(a.name)+'")');
			$(".gallery_album_cover", local).mousemove(function(event) {
				var albumMetadata = Albums.find(this.title);
				if (albumMetadata == undefined) {
					return;
				}
				var x = Math.floor(event.offsetX/(this.offsetWidth/albumMetadata.numOfCovers));
				x *= this.offsetWidth;
        if (x < 0 ||  isNaN(x)) x=0;
				$(this).css('background-position', -x+'px 0');
			});
			element.append(local);
		}
		var photoDisplayTemplate = '<div class="gallery_box"><div class="dummy"></div><div><a rel="images" href="'+OC.linkTo('files','download.php')+'?file=URLPATH"><img src="'+OC.filePath('gallery','ajax','thumbnail.php')+'?img=IMGPATH"></a></div></div>';
		for (var i in Albums.photos) {
			element.append(photoDisplayTemplate.replace("IMGPATH", escape(Albums.photos[i])).replace("URLPATH", escape(Albums.photos[i])));
		}
		$("a[rel=images]").fancybox({
			'titlePosition': 'inside'
		});
	},
	rename: function(element, new_name) {
		if (new_name) {
			$(element).attr("data-album", new_name);
			$("a.view", element).attr("href", "?view="+new_name);
			$("h1", element).text(new_name);
		}
	},
	clear: function(element) {
		Albums.albums = new Array();
		element.innerHTML = '';
	}
}
