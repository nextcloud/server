# Contributing to Psalm

The following is a set of guidelines for contributing to Psalm, which is hosted in the [Vimeo Organization](https://github.com/vimeo) on GitHub.

Make sure to check out the [Contributing to Open Source on GitHub](https://guides.github.com/activities/contributing-to-open-source/) guide.

## Submitting Issues

You can create an issue [here](https://github.com/vimeo/psalm/issues/new), but before you do, follow these guidelines:

* Make sure that you are using the latest version (`master`).
* It’s by no means a requirement, but if it's a bug, and you provide demonstration code that can be pasted into https://psalm.dev, it will likely get fixed faster.

## Pull Requests

[Here’s a guide to the codebase you may find useful](docs/how_psalm_works.md).

Before you send a pull request, make sure you follow these guidelines:

* Make sure to run `composer tests` and `./psalm` to ensure that Travis builds will pass
* Don’t forget to add tests!
