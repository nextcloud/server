if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register(OC.getRootPath() + '/core/js/serviceworker.js', {scope: OC.getRootPath() + '/'});
    });
}
