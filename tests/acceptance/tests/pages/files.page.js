(function() {  
  var Page = require('../helper/page.js');
  var LoginPage = require('../pages/login.page.js');

  var FilesPage = function(baseUrl) {
    this.baseUrl = baseUrl;
    this.path = 'index.php/apps/files';
    this.url = baseUrl + this.path;

    var url = this.url
    this.folderUrl = function(folder) {
      return url + '/?dir=%2F' + folder
    }

    // topbar
    this.UserActionDropdown = element(by.id("expandDisplayName"));

    // filelist
    this.selectedFileListId = by.css('tr.searchresult td.filename .innernametext');
    this.firstListElem = element(by.css('#fileList tr:first-child'));

    // new Button and sublist
    this.newButton = element(by.css('#new a'));
    this.newTextButton = element(by.css('li.icon-filetype-text.svg'));
    this.newFolderButton = element(by.css('li.icon-filetype-folder.svg'));
    this.newTextnameForm = element(by.css('li.icon-filetype-text form input'));
    this.newFoldernameForm = element(by.css('li.icon-filetype-folder form input'));

    this.alertWarning = element(by.css('.tipsy-inner'));

    this.trashbinButton = element(by.css('#app-navigation li.nav-trashbin a'));

    // sort arrows
    this.nameSortArrow = element(by.css('a.name.sort'));
    this.sizeSortArrow = element(by.css('a.size.sort'));
    this.modifiedSortArrow = element(by.id('modified'));

    this.searchInput = element(by.id('searchbox'));

    this.shareWithForm = element(by.id('shareWith'));
    this.sharedWithDropdown = element(by.id('ui-id-1'));
    // this.textArea = element(by.css('.ace_content'));
    // this.textLine = element(by.css('.ace_content .ace_line'));
    // this.saveButton = element(by.id('editor_save'));
  };

//================ LOCATOR FUNCTIONS ====================================//
  FilesPage.prototype.fileListId = function() {
    return by.css('td.filename .innernametext');
  }

  FilesPage.prototype.fileListElemId = function(fileName) {
    return by.css("tr[data-file='" + fileName + "']");
  };

  FilesPage.prototype.fileListElemNameId = function(fileName) {
    return by.css("tr[data-file='" + fileName + "'] span.innernametext");
  };

  FilesPage.prototype.restoreListElemId = function(id) {
    return (by.css("#fileList tr[data-id='" + id + "']"));
  };

  FilesPage.prototype.restoreButtonId = function(id) {
    return (by.css("#fileList tr[data-id='" + id + "'] .action.action-restore"));
  };

  FilesPage.prototype.renameButtonId = function(fileName) {
    return by.css("tr[data-file='" + fileName + "'] .action.action-rename");
  };

  FilesPage.prototype.renameFormId = function(fileName) {
    return by.css("tr[data-file='" + fileName + "'] form input");
  };

  FilesPage.prototype.shareButtonId = function(fileName) {
    return by.css("tr[data-file='" + fileName + "'] .action.action-share");
  };

  FilesPage.prototype.deleteButtonId = function(fileName) {
    return by.css("tr[data-file='" + fileName +  "'] .action.delete.delete-icon");
  };

//================ SHARED ===============================================//
 
  FilesPage.prototype.isLoggedIn = function() {
    return this.UserActionDropdown.isPresent().then(function(isLoggedIn) {
      return isLoggedIn;
    });
  }

  FilesPage.prototype.get = function() { 
    browser.get(this.url);

    var button = this.newButton;
    browser.wait(function() {
      return button.isDisplayed();
    }, 5000, 'load files content');
  };

  FilesPage.prototype.getAsUser = function(name, pass) { 
    var loginPage;
    loginPage = new LoginPage(this.baseUrl);

    this.isLoggedIn().then(function(isLoggedIn) {
      if(isLoggedIn) {
        // console.log('isLoggedIn: ' + isLoggedIn);
        return false
      } else {
        console.log('isLoggedIn: ' + isLoggedIn);
        browser.manage().deleteAllCookies(); // logout the hard way
        loginPage.get();
        loginPage.login(name, pass);
      }
    });
  };

  FilesPage.prototype.getFolder = function(folder) {
    folderUrl = this.folderUrl(folder);
    browser.get(folderUrl);
    var button = this.newButton;
    browser.wait(function() {
      return button.isDisplayed();
    }, 5000, 'load files content');
  }
  FilesPage.prototype.getSubFolder = function(folder, subFolder) {
    folderUrl = this.folderUrl(folder) + '%2F' + subFolder;
    console.log(folderUrl);
    browser.get(folderUrl);
    var button = this.newButton;
    browser.wait(function() {
      return button.isDisplayed();
    }, 5000, 'load files content');
  }

  FilesPage.prototype.listFiles = function() {
    // TODO: waiting to avoid "index out of bound error" 
    browser.sleep(800);
    return element.all(this.fileListId()).map(function(filename) {
      return filename.getText();
    });
  };

  FilesPage.prototype.listSelctedFiles = function() {
    return element.all(this.selectedFileListId).map(function(filename) {
      return filename.getText();
    });
  };

//================ SHARED ACTIONS ========================================//

  // FilesPage.prototype.setCurrentListElem = function(name) {
  //   this.setCurrentListElem = element(by.css("tr[data-file='" + name + "']"));
  // }

  FilesPage.prototype.openRenameForm = function(fileName) {
    var page = new Page();
    var renameButton = element(this.renameButtonId(fileName));

    return page.moveMouseTo(this.fileListElemId(fileName)).then(function() {
      return renameButton.click();
    })
  };

  FilesPage.prototype.renameFile = function(fileName, newFileName) {
    var renameForm = element(this.renameFormId(fileName));
    return this.openRenameForm(fileName).then(function() {
      for(var i=0; i<5; i++) {
        renameForm.sendKeys(protractor.Key.DELETE)
      };
      renameForm
        .sendKeys(newFileName)
        .sendKeys(protractor.Key.ENTER)
    });
  };

  FilesPage.prototype.deleteFile = function(fileName) {
    var page = new Page();

    page.moveMouseTo(this.fileListElemId(fileName));
    return element(this.deleteButtonId(fileName)).click();
  };

  FilesPage.prototype.openShareForm = function(fileName) {
    var page = new Page();

    page.moveMouseTo(this.fileListElemId(fileName));
    return element(this.shareButtonId(fileName)).click();
  };

  FilesPage.prototype.shareFile = function(fileName, userName) {
    this.openShareForm(fileName);
    this.shareWithForm.sendKeys(userName);
    var dropdown = this.sharedWithDropdown
    browser.wait(function(){
      return dropdown.isDisplayed();
    }, 3000);
    this.shareWithForm.sendKeys(protractor.Key.ENTER);
  }

  FilesPage.prototype.disableReshare = function(fileName, userName) {
    var disableReshareButton = element(by.css("li[title='" + userName + "'] label input[name='share']"));
    var dropdown = this.sharedWithDropdown

    // this.openShareForm(fileName);

    // TODO: find correct wait trigger
    //  browser.wait(function(){
    //   return dropdown.isDisplayed();
    // }, 3000);s

    // TODO: Timing Workaround
    browser.sleep(800);
    disableReshareButton.click();
  };

  FilesPage.prototype.checkReshareability = function(fileName) {
    var page = new Page();
    var shareButtonLocator = this.shareButtonId(fileName);

    return page.moveMouseTo(this.fileListElemId(fileName)).then(function() {        
      return element(shareButtonLocator).isPresent();
    });
  };

  // FilesPage.prototype.showFileVersions = function(name) {
  //   this.moveMouseTo("tr[data-file='"+name+"']");
  //   var versionId = by.css("tr[data-file='"+name+"'] a.action.action-versions");
  //   return element(versionId).click();
  // };

  // FilesPage.prototype.downloadFile = function(name) {
  //   this.moveMouseTo("tr[data-file='"+name+"']");
  //   var downloadId = by.css("tr[data-file='"+name+"'] a.action.action-download");
  //   return element(downloadId).click();
  // };

  FilesPage.prototype.restoreFile = function(id) {
    var page = new Page();
    page.moveMouseTo(this.restoreListElemId(id));
    return element(this.restoreButtonId(id)).click();
  };

//================ TXT FILES ============================================//

  FilesPage.prototype.createNewTxtFile = function(name) {
    this.newButton.click();
    this.newTextButton.click();
    this.newTextnameForm.sendKeys(name); 
    this.newTextnameForm.sendKeys(protractor.Key.ENTER);
    
    // TODO: find correct wait trigger
    // browser.wait(function() {
    //  // return 
    // });

    // TODO: Timing Workaround
    browser.sleep(800);
  };

//================ FOLDERS ==============================================//

  FilesPage.prototype.createNewFolder = function(name) {
    this.newButton.click()
    this.newFolderButton.click();
    this.newFoldernameForm.sendKeys(name); 
    this.newFoldernameForm.sendKeys(protractor.Key.ENTER);

    // TODO: find correct wait trigger
    // browser.wait(function() {
    //  // return 
    // });

    // TODO: Timing Workaround
    browser.sleep(800);
  };

  FilesPage.prototype.goInToFolder = function(fileName) {
    var page = new Page();

    page.moveMouseTo(this.fileListElemId(fileName));
    element(this.fileListElemNameId(fileName)).click();
    var button = this.newButton;
    browser.wait(function() {
      return button.isDisplayed();
    }, 5000, 'load files content');
  };

  FilesPage.prototype.createSubFolder = function(folderName, subFolderName) {
    this.goInToFolder(folderName);
    this.createNewFolder(subFolderName);
  };

//================ NOT WORKING STUFF ====================================//

  // FilesPage.prototype.editFile = function(file) {
  //   var listElement = element(by.css("tr[data-file='testText.txt'] span.innernametext"));
  //   listElement.click();
  //   var textArea = this.textArea;
  //   browser.pause();
  //   browser.wait(function() {
  //     return(textArea.isDisplayed());
  //   }, 3000, 'load textEditPage');
  // };

  // FilesPage.prototype.writeInFile = function(text) {
  //   // this.textArea.click();
  //   this.textLine.sendKeys(text);
  // };

  // FilesPage.prototype.saveFile = function() {
  //   this.saveButton.click();
  // };


  module.exports = FilesPage;
})();