<?php

namespace Networkteam\Neos\Vite;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Utility\Environment;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Utility\Files;

class AssetIncludesBuilder
{
    /**
     * @Flow\InjectConfiguration(path="server")
     * @var array
     */
    protected $serverConfiguration;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    public function __construct(
        private string $siteNodeName,
        private string $outputPath,
        private string $manifest
    ) {
    }

    public function developmentInclude(string $entry): string
    {
        if (isset($this->serverConfiguration[$this->siteNodeName]['url'])) {
            $viteServerUrl = $this->serverConfiguration[$this->siteNodeName]['url'];
        } else {
            $viteServerUrl = $this->serverConfiguration['_default']['url'];
        }

        // Note: Vite docs mention that in development mode a http://localhost:5173/@vite/client script needs to be included,
        // but that seems to be automatically taken care of.

        $url = rtrim($viteServerUrl, '/') . '/' . $entry;
        return '<script type="module" src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"></script>';
    }

    /**
     * @throws Exception
     */
    public function productionIncludes(string $entry): string
    {
        $manifestPath = Files::concatenatePaths([$this->outputPath, $this->manifest]);
        $manifestContent = Files::getFileContents($manifestPath);

        $manifestJson = json_decode($manifestContent, true);

        if (!isset($manifestJson[$entry])) {
            throw new Exception('Entry "' . $entry . '" not found in manifest file "' . $manifestPath . '"', 1712320814);
        }

        $includes = [];

        $manifestEntry = $manifestJson[$entry];
        if (isset($manifestEntry['css'])) {
            foreach ($manifestEntry['css'] as $cssFile) {
                $includes[] = '<link rel="stylesheet" href="' . htmlspecialchars($this->buildPublicResourceUrl($cssFile), ENT_QUOTES, 'UTF-8') . '">';
            }
            if (isset($manifestEntry['imports'])) {
                $this->recurseImportedChunksCSS($includes, $manifestJson, $manifestEntry['imports']);
            }
        }
        if (isset($manifestEntry['file'])) {
            $includes[] = '<script type="module" src="' . htmlspecialchars($this->buildPublicResourceUrl($manifestEntry['file']), ENT_QUOTES, 'UTF-8') . '"></script>';
        }
        if (isset($manifestEntry['imports'])) {
            $this->recurseImportedChunkFiles($includes, $manifestJson, $manifestEntry['imports']);
        }

        return implode(PHP_EOL, $includes);
    }

    private function recurseImportedChunksCSS(array &$includes, array $manifestJson, array $imports): void
    {
        foreach ($imports as $import) {
            $manifestEntry = $manifestJson[$import];
            if (isset($manifestEntry['css'])) {
                foreach ($manifestEntry['css'] as $cssFile) {
                    $includes[] = '<link rel="stylesheet" href="' . htmlspecialchars($this->buildPublicResourceUrl($cssFile), ENT_QUOTES, 'UTF-8') . '">';
                }
            }
            if (isset($manifestEntry['imports'])) {
                $this->recurseImportedChunksCSS($includes, $manifestJson, $manifestEntry['imports']);
            }
        }
    }

    private function recurseImportedChunkFiles(array &$includes, array $manifestJson, array $imports): void
    {
        foreach ($imports as $import) {
            $manifestEntry = $manifestJson[$import];
            if (isset($manifestEntry['file'])) {
                $includes[] = '<script type="modulepreload" src="' . htmlspecialchars($this->buildPublicResourceUrl($manifestEntry['file']), ENT_QUOTES, 'UTF-8') . '"></script>';
            }
            if (isset($manifestEntry['imports'])) {
                $this->recurseImportedChunkFiles($includes, $manifestJson, $manifestEntry['imports']);
            }
        }
    }

    private function buildPublicResourceUrl(string $file): string
    {
        $path = Files::concatenatePaths([$this->outputPath, $file]);

        $matches = [];
        if (preg_match('#^resource://([^/]+)/Public/(.*)#', $path, $matches) !== 1) {
            throw new Exception(sprintf('The specified path "%s" does not point to a public resource.', $path), 1712325000);
        }
        $package = $matches[1];
        $path = $matches[2];

        return $this->resourceManager->getPublicPackageResourceUri($package, $path);
    }
}
