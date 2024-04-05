<?php
namespace Networkteam\Neos\Vite\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Environment;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Neos\Domain\Service\ContentContext;
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

        $node = $this->fusionValue('node');
        /** @var ContentContext $context */
        $context = $node->getContext();
        $site = $context->getCurrentSite();
        $siteNodeName = $site->getNodeName();

        if (empty($outputPath)) {
            $outputPath = str_replace('{sitePackageKey}', $site->getSiteResourcesPackageKey(), $outputPathPattern);
        }

        $builder = new AssetIncludesBuilder($siteNodeName, $outputPath, $manifest);

        if ($this->environment->getContext()->isProduction()) {
            return $builder->productionIncludes($entry);
        }
        return $builder->developmentInclude($entry);
    }

}
