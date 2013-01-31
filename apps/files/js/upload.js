function Upload(fileSelector) {
	if ($.support.xhrFileUpload) {
		return new XHRUpload(fileSelector.target.files);
	} else {
		return new FormUpload(fileSelector);
	}
}
Upload.target = OC.filePath('files', 'ajax', 'upload.php');
