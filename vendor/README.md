# 3rdparty

Some 3rd party libraries that are necessary to run Nextcloud.

[![Dependency Status](https://www.versioneye.com/user/projects/576c043fcd6d510048bab256/badge.svg?style=flat-square)](https://www.versioneye.com/user/projects/576c043fcd6d510048bab256)

## Updating libraries manually

1. Make sure to use the latest version of composer: `composer self-update`
2. Edit composer.json and adjust the version of the library to the one to update to. Pay attention to use the full version number (i.e. ^5.3.14).
3. Run `composer update thevendor/thelib` (replace accordingly)
4. Delete all installed dependencies with `rm -rf ./*/`
5. Run `composer install --no-dev`
6. Run `git clean -X -d -f`
7. Run `composer dump-autoload`
8. Commit all changes onto a new branch
9. You might need the following command for pushing if used as submodule: `git push git@github.com:nextcloud/3rdparty.git branchname`

### Updating polyfills

We make use of Symfony polyfills to allow us writing code already using methods from newer PHP version.
This allows cleaner code and once our minimum PHP version is bumped we have no duplicated code for those methods anymore.

To keep the list of polyfills up to date, we need to add new polyfills once a new PHP version was released,
for example with the release of PHP 8.4 we need to add its polyfills by running `composer require symfony/polyfill-php84`.

But we need to also remove unused polyfills to reduce any (minimal) overhead.
Thus once we increase our minimal PHP version we should remove the added polyfills from the list of dependencies.
Moreover also some dependencies require polyfills, to also reduce the list of added polyfills,
we add the unneeded onces to the `replace` section of the `composer.json`.

So all PHP polyfills for already supported versions should be added to that section.
But also polyfills for PHP modules that we require to be installed should be added, one example here is the `mbstring` module.

## Testing your PR with server

1. On https://github.com/nextcloud/server make a new branch `3rdparty/my-dependency`
2. Navigate into the 3rdparty directory
3. Checkout the commit sha of the **last commit** of your PR in the 3rdparty repository
4. Leave the directory
5. Add the change to the stash
6. Commit (with sign-off and message)
7. Push the branch and send a PR
8. ⏳ Wait for CI and reviews
9. Navigate into the 3rdparty directory
10. Checkout the commit sha of the **merge commit** of your PR in the 3rdparty repository
11. Leave the directory
12. Add the change to the stash
13. Amend to the previous dependency bump
14. Push with lease force
15. ⏳ Wait for CI
16. Merge 🎉

```sh
cd 3rdparty
git checkout 16cd747ebb8ab4d746193416aa2448c8114d5084
cd ..
git add 3rdparty
git commit
git push origin 3rdparty/my-dependency

# Wait for CI and reviews

cd 3rdparty
git checkout 54b63cc87af3ddb0ddfa331f20ecba5fcc01d495
cd ..
git add 3rdparty
git commit --amend
git push --force-with-lease origin 3rdparty/my-dependency
```
