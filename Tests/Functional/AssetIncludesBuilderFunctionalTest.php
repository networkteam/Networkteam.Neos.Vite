<?php

namespace Networkteam\Neos\Vite\Tests\Functional;

use Neos\Flow\Tests\FunctionalTestCase;
use Networkteam\Neos\Vite\AssetIncludesBuilder;

class AssetIncludesBuilderFunctionalTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function developmentInclude()
    {
        $serverConfiguration = [
            '_default' => [
                'url' => 'http://localhost:1234'
            ],
            'my-site' => [
                'url' => 'http://localhost:4321'
            ]
        ];

        $builder = new AssetIncludesBuilder('my-site', 'outputPath', 'manifest');
        $this->inject($builder, 'serverConfiguration', $serverConfiguration);

        $html = $builder->developmentInclude('main.js');
        $this->assertEquals('<script type="module" src="http://localhost:4321/main.js"></script>', $html, 'should use site specific server URL');

        $builder = new AssetIncludesBuilder('another-site', 'outputPath', 'manifest');
        $this->inject($builder, 'serverConfiguration', $serverConfiguration);

        $html = $builder->developmentInclude('main.js');
        $this->assertEquals('<script type="module" src="http://localhost:1234/main.js"></script>', $html, 'should use default server URL');
    }

    /**
     * @test
     */
    public function productionIncludes()
    {
        $builder = new AssetIncludesBuilder('my-site', 'resource://Networkteam.Neos.Vite/Public/Dist', '.vite/manifest.json');

        $html = $builder->productionIncludes('main.js');
        $this->assertEquals(
            '<link rel="stylesheet" href="http://localhost/_Resources/Testing/Static/Packages/Networkteam.Neos.Vite/Dist/assets/main.b82dbe22.css">' . PHP_EOL .
            '<link rel="stylesheet" href="http://localhost/_Resources/Testing/Static/Packages/Networkteam.Neos.Vite/Dist/assets/shared.a834bfc3.css">' . PHP_EOL .
            '<script type="module" src="http://localhost/_Resources/Testing/Static/Packages/Networkteam.Neos.Vite/Dist/assets/main.4889e940.js"></script>' . PHP_EOL .
            '<script type="module" src="http://localhost/_Resources/Testing/Static/Packages/Networkteam.Neos.Vite/Dist/assets/shared.83069a53.js"></script>'
            , $html, 'should use site specific server URL');


    }
}
