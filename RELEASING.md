# Releasing jield-api-tools

## Branches and versions

- `2.x` is the development and release branch for version 2. Target feature pull requests at this branch.
- Keep the legacy 1.x code separate. Do not merge `2.x` or `allow-php85` into the legacy branches or move existing tags.
- The initial `2.x` branch includes all work from `allow-php85`. Subsequent releases come from `2.x`.
- Composer derives package versions from Git tags. Do not add a `version` field to `composer.json`.
  The numeric `2.x` branch is automatically available to Composer as `2.x-dev`; it needs no branch alias.

Use these tag formats:

| Release              | Tag                  |
|----------------------|----------------------|
| First beta           | `v2.0.0-beta.1`      |
| Next beta            | `v2.0.0-beta.2`      |
| Release candidate    | `v2.0.0-rc.1`        |
| First stable version | `v2.0.0`             |
| Later minor or patch | `v2.1.0` or `v2.0.1` |

The workflow also accepts numbered alpha tags, for example `v2.1.0-alpha.1`. Prerelease numbers start at 1.
Always create a new tag for a new release; never reuse or move a published tag.

## Prepare a release

1. Merge the release changes into `2.x`, including any relevant documentation and dependency lock file changes.
2. Update the local branch and install its locked dependencies:

   ```shell
   git switch 2.x
   git pull --ff-only origin 2.x
   composer install
   ```

   PHP 8.4 installs without platform overrides. Some upstream dependencies still cap PHP support at 8.4.
   For PHP 8.5, use `composer install --ignore-platform-req=php+`. This ignores PHP upper bounds only;
   extension requirements and minimum PHP versions remain enforced. CI uses the same override only on PHP 8.5.

3. Run the same checks as CI:

   ```shell
   composer validate --strict
   composer php-lint
   composer phpstan
   composer test
   ```

4. Confirm the **PHP Composer and Release** workflow passed on the exact `2.x` commit to release.
   It tests PHP 8.4 and 8.5 with both locked and highest permitted dependency versions.
5. Check `git status --short` is empty. Commit and push any outstanding release changes before tagging.

## Publish the first beta

From the checked and clean `2.x` branch:

```shell
git tag -a v2.0.0-beta.1 -m "Release 2.0.0 beta 1"
git push origin v2.0.0-beta.1
```

Pushing a `v2.*` tag runs the checks again on the tagged commit. Once all four jobs pass, the release job
validates the tag format, verifies that the commit is part of `origin/2.x`, and publishes a GitHub release
with automatically generated release notes. It uses the workflow's `GITHUB_TOKEN` with `contents: write`;
no personal access token is required.

Tags containing `-alpha.N`, `-beta.N`, or `-rc.N` become GitHub prereleases and are not marked as the latest release.
Tags such as `v2.0.0` become stable releases and are marked as latest. No release is published when checks fail.
Tag pushes trigger this workflow directly; publishing a release does not require changing the default branch.

Verify the release in [GitHub Releases](https://github.com/jield-webdev/jield-api-tools/releases) and confirm that
[Packagist](https://packagist.org/packages/jield-webdev/jield-api-tools) lists `2.0.0-beta.1` at the correct commit.
Packagist reads the pushed Git tag and may index it before the GitHub workflow finishes, so only tag a commit
whose branch checks have already passed. If the tag is not indexed automatically, use the package's Update button
with a maintainer account and verify the repository's Packagist integration.

If a workflow run fails because of a transient service problem, rerun the failed jobs in GitHub Actions.
If code needs fixing after a tag has been pushed, commit the fix on `2.x`, wait for CI, and publish a new version
such as `v2.0.0-beta.2`. Do not move the failed tag.

## Later releases

Repeat preparation and tagging with the next version, for example:

```shell
git tag -a v2.0.0-beta.2 -m "Release 2.0.0 beta 2"
git push origin v2.0.0-beta.2
```

When version 2.0 is ready for stable use, tag `v2.0.0` on the checked `2.x` commit. The workflow selects stable
release behavior from the tag automatically; no workflow or Composer version change is needed.

Consumers can opt into a specific beta or accept later compatible beta releases:

```shell
composer require 'jield-webdev/jield-api-tools:2.0.0-beta.1' 'solarium/solarium:dev-master'
composer require 'jield-webdev/jield-api-tools:^2.0@beta' 'solarium/solarium:dev-master'
```

These are alternatives; choose one constraint. The explicit Solarium requirement opts into the package's existing
`dev-master` dependency, which Composer otherwise rejects under its default stability policy. Applications can keep
their project-wide `minimum-stability` unchanged. On PHP 8.5, append `--ignore-platform-req=php+` while upstream
PHP upper bounds still apply.

For development directly from the branch, use `jield-webdev/jield-api-tools:2.x-dev`.
