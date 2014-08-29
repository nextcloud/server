var ShareApi = require('../pages/shareApi.page.js');
var LoginPage = require('../pages/login.page.js');
var UserPage = require('../pages/user.page.js');
var FilesPage = require('../pages/files.page.js');
var parseXml = require('xml2js').parseString;

var flow = protractor.promise.controlFlow();

ddescribe('Share Api', function() {
  var params = browser.params;
  var shareApi;
  var loginPage;
  var userPage
  var filesPage;
  var firstRunWizardPage;


  beforeEach(function() {
    isAngularSite(false);
    shareApi = new ShareApi(params.baseUrl);
  });

  it('should get all shares', function() {
    var get = function () {
      return shareApi.get();
    };

    flow.execute(get).then(function(res){
      parseXml(res.body, function (err, result) {
        console.dir(result.ocs.data);
      });
      expect(res.statusCode).toEqual(200);
    });
  });

  it('should create a new share', function() {
    var create = function () {
      return shareApi.create('asdf.txt', 'demo2', 0);
    };

    flow.execute(create).then(function(res){
      parseXml(res.body, function (err, result) {
        console.log(result.ocs.data, result.ocs.meta);
        expect(result.ocs.meta[0].statuscode[0]).toEqual('100');
      });
    });
  });


});