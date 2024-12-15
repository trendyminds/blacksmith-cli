<?php

use App\Data\Sandbox;
use Illuminate\Support\Facades\Config;
use Laravel\Forge\Resources\Site;
use Mockery\LegacyMockInterface;

/**
 * Helper for mocking deployment script expectations.
 *
 * @param  string  $expectedScript  The script expected to be passed to updateDeploymentScript.
 */
function expectDeploymentScript(string $expectedScript): LegacyMockInterface
{
    // Create a partial mock of the Sandbox class
    $sandboxMock = Mockery::mock(Sandbox::class)->makePartial();

    // Create a mock of the Site class
    $siteMock = Mockery::mock(Site::class);

    // Expect updateDeploymentScript to be called with the expected script
    $siteMock->shouldReceive('updateDeploymentScript')
        ->once()
        ->with($expectedScript);

    // Mock getSite to return the Site mock
    $sandboxMock->shouldReceive('getSite')->andReturn($siteMock);

    return $sandboxMock;
}

it('adds custom deployment scripts when defined', function () {
    Config::set('forge.deploy_script', 'npm install; npm run build');

    $expectedScript = <<<'EOD'
# Default Blacksmith commands
cd $FORGE_SITE_PATH
git pull origin $FORGE_SITE_BRANCH
$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Via FORGE_DEPLOY_SCRIPT
npm install
npm run build
EOD;

    $sandboxMock = expectDeploymentScript($expectedScript);
    $sandboxMock->updateDeployScript();
});

it('does not matter where the semicolon for the deployment scripts', function () {
    Config::set('forge.deploy_script', 'npm install; npm run build;npm run deploy; npm run test;');

    $expectedScript = <<<'EOD'
# Default Blacksmith commands
cd $FORGE_SITE_PATH
git pull origin $FORGE_SITE_BRANCH
$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Via FORGE_DEPLOY_SCRIPT
npm install
npm run build
npm run deploy
npm run test
EOD;

    $sandboxMock = expectDeploymentScript($expectedScript);
    $sandboxMock->updateDeployScript();
});

it('excludes custom deployment scripts when not set', function () {
    Config::set('forge.deploy_script', null);

    $expectedScript = <<<'EOD'
# Default Blacksmith commands
cd $FORGE_SITE_PATH
git pull origin $FORGE_SITE_BRANCH
$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader
EOD;

    $sandboxMock = expectDeploymentScript($expectedScript);
    $sandboxMock->updateDeployScript();
});
