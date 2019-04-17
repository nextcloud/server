workflow "On comment" {
  resolves = ["Status"]
  on = "issue_comment"
}

action "Is pull request" {
  uses = "actions/bin/filter@master"
  args = "branch *"
}

action "Check command" {
  needs = "Is pull request"
  uses = "actions/bin/filter@master"
  args = "issue_comment build and commit /*"
}

action "Npm install" {
  needs = "Check command"
  uses = "actions/npm@master"
  args = "install"
}

action "Npm build" {
  needs = "Npm install"
  uses = "actions/npm@master"
  args = "run build"
}

action "Status" {
  needs = "Npm build"
  uses = "srt32/git-actions@v0.0.3"
  args = "git status"
}
