(function() {  
  var UserPage = function(baseUrl) {
    this.baseUrl = baseUrl;
    this.path = 'index.php/settings/users';
    this.url = baseUrl + this.path;
    
    this.newUserNameInput = element(by.id('newusername'));
    this.newUserPasswordInput = element(by.id('newuserpassword'));
    this.createNewUserButton = element(by.css('#newuser input[type="submit"]')); 

    this.newGroupButton = element(by.css('#newgroup-init a'));
    this.newGroupNameInput = element(by.css('#newgroup-form input#newgroupname'));

  };

  UserPage.prototype.get = function() {
    browser.get(this.url);
  };
  
  UserPage.prototype.isUserPage = function() {
    return browser.driver.getCurrentUrl() == this.url;
  };
  
  UserPage.prototype.ensureUserPage = function() {
    // console.log(this.isUserPage());
    // if (! this.isUserPage()) {
    //   display.log('Warning: Auto loading UserPage');
    //   this.get();
    // }
  };
  
  UserPage.prototype.fillNewUserInput = function(user, pass) {
    this.ensureUserPage();
    this.newUserNameInput.sendKeys(user);
    this.newUserPasswordInput.sendKeys(pass);
  };
  
  UserPage.prototype.createNewUser = function(user, pass) {
    this.ensureUserPage();
    this.fillNewUserInput(user, pass);
    this.createNewUserButton.click();
  };
  
  UserPage.prototype.deleteUser = function(user) {
    this.ensureUserPage();
    
    var removeId = by.css('#userlist tr[data-displayname="' + user + '"] td.remove a');
    var filter = browser.findElement(removeId);
    var scrollIntoView = function () {
      arguments[0].scrollIntoView();
    }
    browser.executeScript(scrollIntoView, filter).then(function () {
      browser.actions().mouseMove(browser.findElement(removeId)).perform();
      element(removeId).click();
    });
  };

  UserPage.prototype.setCurrentListElem = function(name) {
    return element(by.css("tr[data-uid='" + name + "']"));
  }

  UserPage.prototype.renameDisplayName = function(name, newName) {
    var renameDisplayNameButton = element(by.css("tr[data-uid='" + name + "'] td.displayName"));
    renameDisplayNameButton.click();
    var renameDisplayNameForm = element(by.css("tr[data-uid='" + name + "'] td.displayName input"));
    renameDisplayNameForm.sendKeys(newName);
    renameDisplayNameForm.sendKeys(protractor.Key.ENTER);
  };
  
  UserPage.prototype.listUser = function() {
    this.ensureUserPage();
    return element.all(by.css('td.displayName')).map(function(user) {
      return user.getText();
    });
  };
  
  UserPage.prototype.createNewGroup = function(name) {
    this.newGroupButton.click();
    var newGroupNameInput = this.newGroupNameInput;
    browser.wait(function() {
      return newGroupNameInput.isDisplayed();
    }, 3000);
    this.newGroupNameInput.sendKeys(name);
    this.newGroupNameInput.sendKeys(protractor.Key.ENTER);
  };

///// NOT WORKING, CLICK ON CHECKBOX RESEIVES AN OTHER ELEMENT //////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  // UserPage.prototype.setUserGroup = function(userName, groupName) {
  //   var renameDisplayNameButton = element(by.css("tr[data-uid='" + userName + "'] td.groups .multiselect.button"));
  //   renameDisplayNameButton.click();

  //   var a = 'tr[data-uid="' + userName + '"] ul.multiselectoptions.down';

  //   var dropdown = element(by.css(a));
  //   browser.wait(function() {
  //     return dropdown.isDisplayed();
  //   }, 3000);
  //   browser.pause();
  //   var checkboxId = by.css('tr[data-uid="' + userName + '"] ul.multiselectoptions.down label');
  //   element.all(checkboxId).each(function(checkbox) {
  //     checkbox.getText().then(function(text) {
  //       console.log(checkboxId);
  //       console.log(text);
  //       if(text == groupName) {
  //         return checkbox.click();
  //       }
  //     })
  //   });
  // };

  module.exports = UserPage;
})();