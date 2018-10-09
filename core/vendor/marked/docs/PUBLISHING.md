# Releasing Marked

- [ ] See [contributing](CONTRIBUTING.md)
- [ ] Create release branch from `master` (`release-x.y.z`)
- [ ] Submit PR with minimal name: Release x.y.z
- [ ] Complete PR checklists

## Overall strategy

**Master is always shippable:** We try to merge PRs in such a way that `master` is the only branch to really be concerned about *and* `master` can always be released. This allows smoother flow between new fetures, bug fixes, and so on. (Almost a continuous deployment setup, without automation.)

## Versioning

We follow [semantic versioning](https://semver.org) where the following sequence is true `[major].[minor].[patch]`; therefore, consider the following implications of the release you are preparing:

1. **Major:** There is at least one change not deemed backward compatible. 
2. **Minor:** There is at least one new feature added to the release. 
3. **Patch:** No breaking changes, no new features.

What to expect while Marked is a zero-major (0.x.y):

1. The major will remain at zero; thereby, alerting consumers to the potentially volatile nature of the package.
2. The minor will tend to be more analagous to a `major` release. For example, we plan to release `0.4.0` once we have fixed most, if not all, known issues related to the CommonMark and GFM specifications because the architecture changes planned during `0.4.0` will most likely introduce breaking changes.
3. The patch will tend to be more analagous to a `minor` release.