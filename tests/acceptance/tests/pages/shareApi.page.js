(function() { 
  var request = require('request');
  var parseString = require('xml2js').parseString;
  process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0" // Avoids DEPTH_ZERO_SELF_SIGNED_CERT error for self-signed certs
  
  var ShareApi = function(baseUrl) {
    this.baseUrl = baseUrl;
    this.path = 'ocs/v1.php/apps/files_sharing/api/v1/shares';
    this.url = baseUrl + this.path;

    this.request = {
      method: "GET"
    };
  };
  ShareApi.prototype.get = function () {
    var url = this.url;

    var defer = protractor.promise.defer();
    console.log("Calling", this.url);

    request({
      method: "GET",
      uri: url,
      followRedirect: true,
      auth: {
        user: "admin", 
        password: "password",
      }
    },
    function(error, response) {
      console.log("Done call to", url, "status:", response.statusCode);
      if (error || response.statusCode >= 400) {
          defer.reject({
              error : error,
              response : response
          });
      } else {
          defer.fulfill(response);
      }
    });
    return defer.promise;
  };

  ShareApi.prototype.create = function (path, shareWith, shareType) {
    var url = this.url;

    var defer = protractor.promise.defer();
    console.log("Calling", this.url);

    request({
      method: "POST",
      uri: url,
      followRedirect: true,
      form: {
        path: path,
        shareWith: shareWith,
        shareType: shareType
      },
      auth: {
        user: "admin", 
        password: "password",
      }
    },
    function(error, response) {
      console.log("Done call to", url, "status:", response.statusCode);
      if (error || response.statusCode >= 400) {
          defer.reject({
              error : error,
              response : response
          });
      } else {
          defer.fulfill(response);
      }
    });
    return defer.promise;

  };

  module.exports = ShareApi;

})();



