# Networkteam.Neos.Vite

## Installation

Go to your site package:

```bash
cd DistributionPackages/Your.Site
```

1. Install the package via composer

```bash
composer require networkteam/neos-vite
```

2. Install Vite via NPM (or Yarn, pnpm):

```bash
npm install --save-dev vite
```

3. Create a `vite.config.mjs` file in your site package:

```js
import { defineConfig } from "vite";

export default defineConfig((configEnv) => ({
  base: "./",
  build: {
    // generate .vite/manifest.json in outDir
    manifest: true,
    rollupOptions: {
      // overwrite default .html entry
      input: {
        footer: "Resources/Private/Javascript/footer.js",
        header: "Resources/Private/Javascript/header.js",
      },
      output: {
        // If you use this output option the Fusion object will just work™️
        dir: "Resources/Public/Dist",
      },
    },
  },
}));
```

4. You can now include Vite assets for development / production in your Fusion files:

```fusion
header = Networkteam.Neos.Vite:Asset {
    entry = 'Resources/Private/Javascript/header.js'
}
```

This Fusion object will use a different include based on the FLOW_CONTEXT:

- Development: Loads entry from development server configured for the site (defaults to http://localhost:5173/)
- Production: Based on the generated manifest file it will include the hashed assets with CSS and recursive imports

By default the manifest is expected in `Resources/Public/Dist`, but that can be changed by overriding Fusion properties:

```fusion
prototype(Networkteam.Neos.Vite:Asset) {
    // ...

    outputPathPattern = 'resource://{sitePackageKey}/Public/Dist'
    manifest = '.vite/manifest.json'
}
```

## Multi-site

Configure individual Vite configurations for each site by adding a `vite.config.mjs` file in each site package.
Make sure to run each server on a different port.

Add Settings for each site in your `Settings.yaml`:

```yaml
Networkteam:
  Neos:
    Vite:
      server:
        # This is the default setting if no configuration part is found for the site node name
        _default:
          url: http://localhost:5173/

        # Specify server configuration for a specific site node name
        other-site:
          url: http://localhost:5174/

```
