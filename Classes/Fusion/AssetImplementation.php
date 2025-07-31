<?php
namespace Networkteam\Neos\Vite\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Environment;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Networkteam\Neos\Vite\AssetIncludesBuilder;
use Networkteam\Neos\Vite\Exception;

class AssetImplementation extends AbstractFusionObject
{

    /**
     * @Flow\Inject
     * @var Environment
     */
    protected $environment;

    /**
     * @throws Exception
     */
    public function evaluate()
    {
        $entry = $this->fusionValue('entry');
        $outputPath = $this->fusionValue('outputPath');
        $manifest = $this->fusionValue('manifest');

        $outputPathPattern = $this->fusionValue('outputPathPattern');

        $sitePackageKey = $this->fusionValue('sitePackageKey');

        if (empty($outputPath)) {
            $outputPath = str_replace('{sitePackageKey}', $sitePackageKey, $outputPathPattern);
        }

        $builder = new AssetIncludesBuilder($sitePackageKey, $outputPath, $manifest);

        if ($this->environment->getContext()->isProduction()) {
            return $builder->productionIncludes($entry);
        }
        return $builder->developmentInclude($entry);
    }

}
