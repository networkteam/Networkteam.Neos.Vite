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
     * @var array<mixed>
     */
    protected $serverConfiguration;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    public function __construct(
        private string $sitePackageKey,
        private string $outputPath,
        private string $manifest
    ) {
    }

    public function developmentInclude(string $entry): string
    {
        // Note: Vite docs mention that in development mode a http://localhost:5173/@vite/client script needs to be included,
        // but that seems to be automatically taken care of.

        $url = rtrim($this->getViteServerUrl(), '/') . '/' . $entry;
        return '<script type="module" src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"></script>';
    }

    public function developmentUrl(string $entry): string
    {
        $url = rtrim($this->getViteServerUrl(), '/') . '/' . $entry;
        return $url;
    }

    /**
     * @throws Exception
     */
    public function productionIncludes(string $entry): string
    {
        $manifest = $this->getManifest();

        if (!isset($manifest[$entry])) {
            throw new Exception('Entry "' . $entry . '" not found in manifest file "' . $this->getManifestPath() . '"', 1712320814);
        }

        $includes = [];
        $manifestEntry = $manifest[$entry];

        if (isset($manifestEntry['css'])) {
            foreach ($manifestEntry['css'] as $cssFile) {
                $includes[] = '<link rel="stylesheet" href="' . htmlspecialchars($this->buildPublicResourceUrl($cssFile), ENT_QUOTES, 'UTF-8') . '">';
            }
        }
        if (isset($manifestEntry['imports'])) {
            $this->recurseImportedChunksCSS($includes, $manifest, $manifestEntry['imports']);
        }
        if (isset($manifestEntry['file'])) {
            $includes[] = '<script type="module" src="' . htmlspecialchars($this->buildPublicResourceUrl($manifestEntry['file']), ENT_QUOTES, 'UTF-8') . '"></script>';
        }
        if (isset($manifestEntry['imports'])) {
            $this->recurseImportedChunkFiles($includes, $manifest, $manifestEntry['imports']);
        }

        return implode(PHP_EOL, $includes);
    }

    /**
     * @throws Exception
     */
    public function productionUrl(string $entry): string
    {
        $manifest = $this->getManifest();

        if (!isset($manifest[$entry])) {
            throw new Exception('Entry "' . $entry . '" not found in manifest file "' . $this->getManifestPath() . '"', 1712320815);
        }

        $manifestEntry = $manifest[$entry];
        if (isset($manifestEntry['file'])) {
            return $this->buildPublicResourceUrl($manifestEntry['file']);
        }

        throw new Exception('Entry "' . $entry . '" does not have a "file" key in manifest file "' . $this->getManifestPath() . '"', 1712320816);
    }

    private function getViteServerUrl(): string
    {
        if (isset($this->serverConfiguration[$this->sitePackageKey]['url'])) {
            return $this->serverConfiguration[$this->sitePackageKey]['url'];
        } else {
            return $this->serverConfiguration['_default']['url'];
        }
    }

    private function getManifestPath(): string
    {
        return Files::concatenatePaths([$this->outputPath, $this->manifest]);
    }

    private function getManifest(): array<mixed>
    {
        $manifestPath = $this->getManifestPath();
        $manifestContent = Files::getFileContents($manifestPath);

        $manifestJson = json_decode($manifestContent, true);

        return $manifestJson;
    }

    /**
     * @param array<string> $includes
     * @param array<mixed> $manifestJson
     * @param array<string> $imports
     * @return void
     */
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

    /**
     * @param array<string> $includes
     * @param array<mixed> $manifestJson
     * @param array<string> $imports
     * @return void
     */
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
