<?php
namespace Psalm\Internal\ExecutionEnvironment;

use Psalm\SourceControl\Git\CommitInfo;
use Psalm\SourceControl\Git\GitInfo;
use function explode;

/**
 * Environment variables collector for CI environment.
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class BuildInfoCollector
{
    /**
     * Environment variables.
     *
     * Overwritten through collection process.
     *
     * @var array
     */
    protected $env;

    /**
     * Read environment variables.
     *
     * @var array
     */
    protected $readEnv = [];

    public function __construct(array $env)
    {
        $this->env = $env;
    }

    // API

    /**
     * Collect environment variables.
     */
    public function collect() : array
    {
        $this->readEnv = [];

        $this
            ->fillTravisCi()
            ->fillCircleCi()
            ->fillAppVeyor()
            ->fillJenkins()
            ->fillScrutinizer()
            ->fillGithubActions();

        return $this->readEnv;
    }

    // internal method

    /**
     * Fill Travis CI environment variables.
     *
     * "TRAVIS", "TRAVIS_JOB_ID" must be set.
     *
     * @return $this
     *
     * @psalm-suppress PossiblyUndefinedStringArrayOffset
     */
    protected function fillTravisCi() : self
    {
        if (isset($this->env['TRAVIS']) && $this->env['TRAVIS'] && isset($this->env['TRAVIS_JOB_ID'])) {
            $this->readEnv['CI_JOB_ID'] = $this->env['TRAVIS_JOB_ID'];
            $this->env['CI_NAME'] = 'travis-ci';

            // backup
            $this->readEnv['TRAVIS'] = $this->env['TRAVIS'];
            $this->readEnv['TRAVIS_JOB_ID'] = $this->env['TRAVIS_JOB_ID'];
            $this->readEnv['CI_NAME'] = $this->env['CI_NAME'];
            $this->readEnv['TRAVIS_TAG'] = $this->env['TRAVIS_TAG'] ?? '';

            $repo_slug = (string) $this->env['TRAVIS_REPO_SLUG'];

            if ($repo_slug) {
                $slug_parts = explode('/', $repo_slug);
                $this->readEnv['CI_REPO_OWNER'] = $slug_parts[0];
                $this->readEnv['CI_REPO_NAME'] = $slug_parts[1];
            }

            $pr_slug = (string) ($this->env['TRAVIS_PULL_REQUEST_SLUG'] ?? '');

            if ($pr_slug) {
                $slug_parts = explode('/', $pr_slug);

                $this->readEnv['CI_PR_REPO_OWNER'] = $slug_parts[0];
                $this->readEnv['CI_PR_REPO_NAME'] = $slug_parts[1];
            }

            $this->readEnv['CI_PR_NUMBER'] = $this->env['TRAVIS_PULL_REQUEST'];
            $this->readEnv['CI_BRANCH'] = $this->env['TRAVIS_BRANCH'];
        }

        return $this;
    }

    /**
     * Fill CircleCI environment variables.
     *
     * "CIRCLECI", "CIRCLE_BUILD_NUM" must be set.
     *
     * @return $this
     */
    protected function fillCircleCi() : self
    {
        if (isset($this->env['CIRCLECI']) && $this->env['CIRCLECI'] && isset($this->env['CIRCLE_BUILD_NUM'])) {
            $this->env['CI_BUILD_NUMBER'] = $this->env['CIRCLE_BUILD_NUM'];
            $this->env['CI_NAME'] = 'circleci';

            // backup
            $this->readEnv['CIRCLECI'] = $this->env['CIRCLECI'];
            $this->readEnv['CIRCLE_BUILD_NUM'] = $this->env['CIRCLE_BUILD_NUM'];
            $this->readEnv['CI_NAME'] = $this->env['CI_NAME'];

            $this->readEnv['CI_PR_REPO_OWNER'] = $this->env['CIRCLE_PR_USERNAME'] ?? null;
            $this->readEnv['CI_PR_REPO_NAME'] = $this->env['CIRCLE_PR_REPONAME'] ?? null;

            $this->readEnv['CI_REPO_OWNER'] = $this->env['CIRCLE_PROJECT_USERNAME'] ?? null;
            $this->readEnv['CI_REPO_NAME'] = $this->env['CIRCLE_PROJECT_REPONAME'] ?? null;

            $this->readEnv['CI_PR_NUMBER'] = $this->env['CIRCLE_PR_NUMBER'] ?? null;

            $this->readEnv['CI_BRANCH'] = $this->env['CIRCLE_BRANCH'] ?? null;
        }

        return $this;
    }

    /**
     * Fill AppVeyor environment variables.
     *
     * "APPVEYOR", "APPVEYOR_BUILD_NUMBER" must be set.
     *
     * @psalm-suppress PossiblyUndefinedStringArrayOffset
     *
     * @return $this
     */
    protected function fillAppVeyor() : self
    {
        if (isset($this->env['APPVEYOR']) && $this->env['APPVEYOR'] && isset($this->env['APPVEYOR_BUILD_NUMBER'])) {
            $this->readEnv['CI_BUILD_NUMBER'] = $this->env['APPVEYOR_BUILD_NUMBER'];
            $this->readEnv['CI_JOB_ID'] = $this->env['APPVEYOR_JOB_NUMBER'];
            $this->readEnv['CI_BRANCH'] = $this->env['APPVEYOR_REPO_BRANCH'];
            $this->readEnv['CI_PR_NUMBER'] = $this->env['APPVEYOR_PULL_REQUEST_NUMBER'] ?? '';
            $this->env['CI_NAME'] = 'AppVeyor';

            // backup
            $this->readEnv['APPVEYOR'] = $this->env['APPVEYOR'];
            $this->readEnv['APPVEYOR_BUILD_NUMBER'] = $this->env['APPVEYOR_BUILD_NUMBER'];
            $this->readEnv['APPVEYOR_JOB_NUMBER'] = $this->env['APPVEYOR_JOB_NUMBER'];
            $this->readEnv['APPVEYOR_REPO_BRANCH'] = $this->env['APPVEYOR_REPO_BRANCH'];
            $this->readEnv['CI_NAME'] = $this->env['CI_NAME'];

            $repo_slug = (string) ($this->env['APPVEYOR_REPO_NAME'] ?? '');

            if ($repo_slug) {
                $slug_parts = explode('/', $repo_slug);

                $this->readEnv['CI_REPO_OWNER'] = $slug_parts[0];
                $this->readEnv['CI_REPO_NAME'] = $slug_parts[1];
            }

            $pr_slug = (string) ($this->env['APPVEYOR_PULL_REQUEST_HEAD_REPO_NAME'] ?? '');

            if ($pr_slug) {
                $slug_parts = explode('/', $pr_slug);

                $this->readEnv['CI_PR_REPO_OWNER'] = $slug_parts[0];
                $this->readEnv['CI_PR_REPO_NAME'] = $slug_parts[1];
            }

            $this->readEnv['CI_BRANCH'] = $this->env['APPVEYOR_PULL_REQUEST_HEAD_REPO_BRANCH']
                ?? $this->env['APPVEYOR_REPO_BRANCH'];
        }

        return $this;
    }

    /**
     * Fill Jenkins environment variables.
     *
     * "JENKINS_URL", "BUILD_NUMBER" must be set.
     *
     * @return $this
     */
    protected function fillJenkins() : self
    {
        if (isset($this->env['JENKINS_URL']) && isset($this->env['BUILD_NUMBER'])) {
            $this->readEnv['CI_BUILD_NUMBER'] = $this->env['BUILD_NUMBER'];
            $this->readEnv['CI_BUILD_URL'] = $this->env['JENKINS_URL'];
            $this->env['CI_NAME'] = 'jenkins';

            // backup
            $this->readEnv['BUILD_NUMBER'] = $this->env['BUILD_NUMBER'];
            $this->readEnv['JENKINS_URL'] = $this->env['JENKINS_URL'];
            $this->readEnv['CI_NAME'] = $this->env['CI_NAME'];
        }

        return $this;
    }

    /**
     * Fill Scrutinizer environment variables.
     *
     * "JENKINS_URL", "BUILD_NUMBER" must be set.
     *
     * @psalm-suppress PossiblyUndefinedStringArrayOffset
     *
     * @return $this
     */
    protected function fillScrutinizer() : self
    {
        if (isset($this->env['SCRUTINIZER']) && $this->env['SCRUTINIZER']) {
            $this->readEnv['CI_JOB_ID'] = $this->env['SCRUTINIZER_INSPECTION_UUID'];
            $this->readEnv['CI_BRANCH'] = $this->env['SCRUTINIZER_BRANCH'];
            $this->readEnv['CI_PR_NUMBER'] = $this->env['SCRUTINIZER_PR_NUMBER'] ?? '';

            // backup
            $this->readEnv['CI_NAME'] = 'Scrutinizer';

            $repo_slug = (string) ($this->env['SCRUTINIZER_PROJECT'] ?? '');

            if ($repo_slug) {
                $slug_parts = explode('/', $repo_slug);

                if ($this->readEnv['CI_PR_NUMBER']) {
                    $this->readEnv['CI_PR_REPO_OWNER'] = $slug_parts[1];
                    $this->readEnv['CI_PR_REPO_NAME'] = $slug_parts[2];
                } else {
                    $this->readEnv['CI_REPO_OWNER'] = $slug_parts[1];
                    $this->readEnv['CI_REPO_NAME'] = $slug_parts[2];
                }
            }
        }

        return $this;
    }

    /**
     * Fill Github Actions environment variables.
     *
     * @return $this
     * @psalm-suppress PossiblyUndefinedStringArrayOffset
     */
    protected function fillGithubActions(): BuildInfoCollector
    {
        if (isset($this->env['GITHUB_ACTIONS'])) {
            $this->env['CI_NAME'] = 'github-actions';
            $this->env['CI_JOB_ID'] = $this->env['GITHUB_ACTIONS'];

            $githubRef = (string) $this->env['GITHUB_REF'];
            if (\strpos($githubRef, 'refs/heads/') !== false) {
                $githubRef = \str_replace('refs/heads/', '', $githubRef);
            } elseif (\strpos($githubRef, 'refs/tags/') !== false) {
                $githubRef = \str_replace('refs/tags/', '', $githubRef);
            }

            $this->env['CI_BRANCH'] = $githubRef;

            $this->readEnv['GITHUB_ACTIONS'] = $this->env['GITHUB_ACTIONS'];
            $this->readEnv['GITHUB_REF'] = $this->env['GITHUB_REF'];
            $this->readEnv['CI_NAME'] = $this->env['CI_NAME'];
            $this->readEnv['CI_BRANCH'] = $this->env['CI_BRANCH'];

            $slug_parts = explode('/', (string) $this->env['GITHUB_REPOSITORY']);

            $this->readEnv['CI_REPO_OWNER'] = $slug_parts[0];
            $this->readEnv['CI_REPO_NAME'] = $slug_parts[1];

            if (isset($this->env['GITHUB_EVENT_PATH'])) {
                $event_json = \file_get_contents((string) $this->env['GITHUB_EVENT_PATH']);
                /** @var array */
                $event_data = \json_decode($event_json, true);

                if (isset($event_data['head_commit'])) {
                    /**
                     * @var array{
                     *    id: string,
                     *    author: array{name: string, email: string},
                     *    committer: array{name: string, email: string},
                     *    message: string,
                     *    timestamp: string
                     * }
                     */
                    $head_commit_data = $event_data['head_commit'];
                    $gitinfo = new GitInfo(
                        $githubRef,
                        (new CommitInfo())
                            ->setId($head_commit_data['id'])
                            ->setAuthorName($head_commit_data['author']['name'])
                            ->setAuthorEmail($head_commit_data['author']['email'])
                            ->setCommitterName($head_commit_data['committer']['name'])
                            ->setCommitterEmail($head_commit_data['committer']['email'])
                            ->setMessage($head_commit_data['message'])
                            ->setDate(\strtotime($head_commit_data['timestamp'])),
                        []
                    );

                    $this->readEnv['git'] = $gitinfo->toArray();
                }

                if ($this->env['GITHUB_EVENT_PATH'] === 'pull_request') {
                    $this->readEnv['CI_PR_NUMBER'] = $event_data['number'];
                }
            }
        }
        return $this;
    }
}
