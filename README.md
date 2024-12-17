# ⚒️ Blacksmith CLI
### A Forge-provisioning CLI tool for sandboxes

<img src="docs/preview.png" alt="The confirmation notice posted in a pull request when Blacksmith has provisioned a site to your Laravel Forge server">

## ✅ Requirements

When provisioning a sandbox you'll need:
- A Forge API token
- An ID of a Forge server to deploy to

It is imperative to leverage GitHub secrets to ensure you are not commit this type of sensitive data to your codebase and potentially exposing this data to the outside world.

## 🚀 Provisioning a sandbox

Below is an example of a GitHub action that will create a new sandbox when it is labeled with a "sandbox" tag in the pull request.

```yaml
name: Provision Sandbox

on:
  pull_request:
    types: [opened, edited, labeled, reopened, ready_for_review]

jobs:
  sandbox:
    if: contains(github.event.pull_request.labels.*.name, 'sandbox')
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v2
    - uses: shivammathur/setup-php@v2
      with:
        php-version: 8.3
        coverage: none
    - name: Install Blacksmith
      run: composer global require trendyminds/blacksmith-cli
    - name: Start Provisioning
      env:
        GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
        GITHUB_REPO: ${{ github.repository }}
        GITHUB_BRANCH: ${{ github.head_ref }}
        GITHUB_PR_NUMBER: ${{ github.event.number }}
        FORGE_TOKEN: ${{ secrets.BLACKSMITH_FORGE_TOKEN }}
        FORGE_SERVER: ${{ secrets.BLACKSMITH_SANDBOX_SERVER }}
        FORGE_SUBDOMAIN: site-${{ github.event.number }}
        FORGE_DOMAIN: mydomain.io
        FORGE_DEPLOY_SCRIPT: "npm install; npm run build"
      run: blacksmith create
```

## 🗑️ Decommissioning a sandbox

Below is an example of a GitHub action that will decommission a sandbox after it is merged or closed.

```yaml
name: Decommission Sandbox

on:
  pull_request:
    types: [closed]

jobs:
  sandbox:
    if: contains(github.event.pull_request.labels.*.name, 'sandbox')
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v2
    - uses: shivammathur/setup-php@v2
      with:
        php-version: 8.3
        coverage: none
    - name: Install Blacksmith
      run: composer global require trendyminds/blacksmith-cli
    - name: Destroy sandbox
      env:
        GITHUB_REPO: ${{ github.repository }}
        GITHUB_BRANCH: ${{ github.head_ref }}
        FORGE_TOKEN: ${{ secrets.BLACKSMITH_FORGE_TOKEN }}
        FORGE_SERVER: ${{ secrets.BLACKSMITH_SANDBOX_SERVER }}
        FORGE_SUBDOMAIN: site-${{ github.event.number }}
        FORGE_DOMAIN: mydomain.io
      run: blacksmith destroy
```

## ⚙️ Configuration options

| Environment Name       |  Default value  |  Description                                                                                                            |
|------------------------|-----------------|-------------------------------------------------------------------------------------------------------------------------|
| `FORGE_TOKEN`          |                 | The [API token](https://forge.laravel.com/docs/accounts/api) to use to authenticate to your Forge account               |
| `FORGE_SERVER`         |                 | The ID of the server to use when provisioning new sites                                                                 |
| `FORGE_PHP_VERSION`    | `php83`         | The version of PHP to use                                                                                               |
| `FORGE_SUBDOMAIN`      |                 | The subdomain for your application (Ex: `my-feature-123`)                                                               |
| `FORGE_DOMAIN`         |                 | The domain to use (Ex: `domain.com`)                                                                                    |
| `FORGE_DEPLOY_SCRIPT`  |                 | Additional steps to add to your deploy process. Use `;` to delineate between steps (Ex: `npm install; npm run build`)   |
| `FORGE_WEB_DIRECTORY`  | `/public`       | The public root of the site                                                                                             |
| `FORGE_ENABLE_DB`      | `false`         | Whether your site needs a database. If `true` one will be created for you and shared in the post-deploy comment         |
| `GITHUB_TOKEN`         |                 | Used to create a post-deploy comment within the pull request                                                            |
| `GITHUB_REPO`          |                 | The GitHub repo to deploy and mount for the sandbox generation (Ex: `myorg/repo`)                                      |
| `GITHUB_BRANCH`        |                 | The branch to use when mounting your repo to the site                                                                  |
| `GITHUB_PR_NUMBER`     |                 | The pull request number used to create a post-deploy comment within the pull request                                   |

## <img src="docs/statamic.svg" alt="Statamic"> Statamic notes

### Git Automation
Git automation should be enabled by including `STATAMIC_GIT_AUTOMATIC` in your environment variables and setting it to `false`.

This means content commits have to be manually performed by visiting Utilties > Git. However, it _greatly_ simplifies your sandbox:

1. You do not need to run Redis in every Statamic sandbox you create handling queued commits
2. You do not need to commit every single content save to your pull request if you do not queue your commits

While `STATAMIC_GIT_AUTOMATIC=false` means some occasional manual labor, it makes the setup simpler and also enables you to create Statamic sandboxes that _shouldn't_ have committed sandbox changes.
