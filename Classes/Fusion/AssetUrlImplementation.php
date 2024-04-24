<?php
namespace Networkteam\Neos\Vite\Fusion;

use Neos\Flow\Annotations as Flow;
use Networkteam\Neos\Vite\AssetIncludesBuilder;

class AssetUrlImplementation extends AssetImplementation
{
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
            return $builder->productionUrl($entry);
        }
        return $builder->developmentUrl($entry);
    }

}
