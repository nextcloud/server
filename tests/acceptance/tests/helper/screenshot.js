(function() {

  var fs = require('fs');
  
  var Screenshot = function(data, filename) {
    this.screenshotPath = __dirname + '/../../screenshots/';
    
    display.log('Created screenshot: ' + this.screenshotPath + filename);
    var stream = fs.createWriteStream(this.screenshotPath + filename);

    stream.write(new Buffer(data, 'base64'));
    stream.end();
  };

  module.exports = Screenshot;
})();