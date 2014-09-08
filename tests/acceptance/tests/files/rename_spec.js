var Page = require('../helper/page.js')
var LoginPage = require('../pages/login.page.js');
var FilesPage = require('../pages/files.page.js');

// =============================================== RENAME FOLDER =================================== //
// ================================================================================================= //

describe('Rename Folder', function() {
  var params = browser.params;
  var page;
  var filesPage;
  
  beforeEach(function() {
    isAngularSite(false);
    page = new Page();
    filesPage = new FilesPage(params.baseUrl);
    filesPage.getAsUser(params.login.user, params.login.password);
  });

  it('should rename a folder', function() {
    filesPage.createNewFolder('testFolder');
    filesPage.renameFile('testFolder', 'newFolder');
    browser.wait(function() {
      return(filesPage.listFiles());
    }, 3000);
    expect(filesPage.listFiles()).toContain('newFolder');
  });

  it('should show alert message if foldername already in use', function() {
    filesPage.createNewFolder('testFolder');
    filesPage.renameFile('testFolder', 'newFolder');
    browser.wait(function() {
      return(filesPage.listFiles());
    }, 3000);
    expect(filesPage.alertWarning.isDisplayed()).toBeTruthy();
  });

  it('should show alert message if using forbidden characters', function() {
    filesPage.renameFile('newFolder', 'new:Folder');
    browser.wait(function() {
      return(filesPage.listFiles());
    }, 3000);
    expect(filesPage.alertWarning.isDisplayed()).toBeTruthy();
  });

  it('should rename a file using special characters', function() {
    filesPage.get(); // TODO: reload cause warning alerts don't disappear
    filesPage.renameFile('testFolder', 'sP€c!@L B-)');
    browser.wait(function() {
      return(filesPage.listFiles());
    }, 3000);
    expect(filesPage.listFiles()).toContain('sP€c!@L B-)');
  });

  it('should show alert message if newName is empty', function() {
    filesPage.renameFile('newFolder', "");
    browser.wait(function() {
      return(filesPage.listFiles());
    }, 3000);
    expect(filesPage.alertWarning.isDisplayed()).toBeTruthy();
    filesPage.deleteFile('newFolder');
    filesPage.deleteFile('sP€c!@L B-)');
  });
}); 

// =============================================== RENAME FILES ==================================== //
// ================================================================================================= //

describe('Rename Files', function() {
  var params = browser.params;
  var page;
  var filesPage;
  
  beforeEach(function() {
    isAngularSite(false);
    page = new Page();
    filesPage = new FilesPage(params.baseUrl);
    filesPage.getAsUser(params.login.user, params.login.password);
  });

  it('should rename a txt file', function() {
    filesPage.createNewTxtFile('testText');
    filesPage.renameFile('testText.txt', 'newText.txt');
    browser.wait(function() {
      return(filesPage.listFiles());
    }, 3000);
    expect(filesPage.listFiles()).toContain('newText');
  });

  it('should show alert message if filename is already in use', function() {
    filesPage.createNewTxtFile('testText');
    filesPage.renameFile('testText.txt', 'newText.txt');
    browser.wait(function() {
      return(filesPage.listFiles());
    }, 3000);
    expect(filesPage.alertWarning.isDisplayed()).toBeTruthy();
  });

  // it('should rename a file with the same name but changed capitalization', function() {
  //   browser.takeScreenshot().then(function (png) {
      
  //     new Screenshot(png, 'SameNameCapitalization1.png');
  //   filesPage.renameFile('testText.txt', 'NewText.txt');
  //   browser.wait(function() {
  //     return(filesPage.listFiles());
  //   }, 3000);
  //   });
  //   browser.takeScreenshot().then(function (png) {
  //       new Screenshot(png, 'SameNameCapitalization2.png');
  //   });
  //   expect(filesPage.listFiles()).toContain('NewText.txt');
  // });

  it('should rename a file using special characters', function() {
    filesPage.renameFile('newText.txt', 'sP€c!@L B-).txt');
    browser.wait(function() {
      return(filesPage.listFiles());
    }, 3000);
    expect(filesPage.listFiles()).toContain('sP€c!@L B-)');
  });

  it('should show alert message if newName is empty', function() {
    filesPage.get(); // TODO: reload cause warning alerts don't disappear
    filesPage.renameFile('sP€c!@L B-).txt', '');
    browser.wait(function() {
      return(filesPage.listFiles());
    }, 3000);
    expect(filesPage.alertWarning.isDisplayed()).toBeTruthy();
  });

  it('should rename a file by taking off the file extension', function() {
    filesPage.renameFile('testText.txt', 'Without Subfix');
    browser.wait(function() {
      return(filesPage.listFiles());
    }, 3000);
    expect(filesPage.listFiles()).toContain('Without Subfix');
    filesPage.deleteFile('Without Subfix');
    filesPage.deleteFile('sP€c!@L B-).txt');
  });
});