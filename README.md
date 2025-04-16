# Networkteam.Neos.Vite

## Installation

Go to your site package:

```bash
cd DistributionPackages/Your.Site
```

### 1. Install the package via composer

```bash
composer require networkteam/neos-vite
```

### 2. Install Vite via NPM (or Yarn, pnpm):

```bash
npm install --save-dev vite
```

### 3. Create a `vite.config.mjs` file in your site package:

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
        // The Flowpack.CacheBuster package adds a `?bust` get parameter with a hash based on the file content.
        // This leads to issues with files imported from the manifest, as they may be loaded twice.
        // (Once with the bust parameter and once without, as they are technically two different URLs.)
        // To work around this we add a "bust" infix so CacheBuster skips adding the bust parameter.
        entryFileNames: "assets/[name]-bust-[hash].js",
        // If you use this output option the Fusion object will just work™️
        dir: "Resources/Public/Dist",
      },
    },
  },
}));
```

### 4. You can now include Vite assets for development / production in your Fusion files:

Let the package include everything for you, including [vite development scripts](https://vitejs.dev/guide/backend-integration):

```fusion
prototype(Customer.Base:Document.DefaultPage) < prototype(Neos.Neos:Page) {
    head {
        javascript.base = Networkteam.Neos.Vite:Head.Assets {
            header = Networkteam.Neos.Vite:Asset {
                entry = 'Resources/Private/Javascript/header.js'
            }
        }
    }

    bodyAssets = Networkteam.Neos.Vite:Body.Assets {
        footer = Networkteam.Neos.Vite:Asset {
          entry = 'Resources/Private/Javascript/footer.js'
        }

        // Include development scripts needed by Vite, for example:
        developmentOnlyScripts {
            // here you have access to the Vite server URL via context variable `viteUrl`
            pluginName = ${'<script type="module" src="' + viteUrl + '@vite-plugin/client"></script>'}
        }
        @position = 'before closingBodyTag'
    }
}
```

Or, include everything manually using:

```fusion
header = Networkteam.Neos.Vite:Asset {
    entry = 'Resources/Private/Javascript/header.js'
}

developmentOnlyScripts = Networkteam.Neos.Vite:Helper.DevelopmentOnlyScripts {
    @context.viteUrl = ${Configuration.setting('Networkteam.Neos.Vite.server._default.url')}
    pluginName = '<script type="module" src="' + viteUrl + '@vite-plugin/client"></script>'
}
```

You can also use development-only scripts as Condition with Prototype `Networkteam.Neos.Vite:Helper.IsDevelopmentOnly`:

```fusion
prototype(Customer.Base:Helper.SpritemapBaseUrl) < prototype(Neos.Fusion:Case) {
    inDevelopmentMode {
        condition = Networkteam.Neos.Vite:Helper.IsDevelopmentOnly
        renderer = '#icon-'
    }
    default {
        condition = ${false}
        renderer = Neos.Fusion:Value {
            @context.spritemap = Networkteam.Neos.Vite:AssetUrl {
                entry = 'spritemap.svg'
            }
            value = ${spritemap + '#icon-'}
        }
    }
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

### Bonus: You can generate the URL to entries in the manifest

This will only return the URL to the file for an entry without considering imports.

Example for an SVG spritemap (using [vite-plugin-svg-spritemap](https://github.com/SpiriitLabs/vite-plugin-svg-spritemap)):

```fusion
src = Networkteam.Neos.Vite:AssetUrl {
    entry = 'spritemap.svg'
}
```

## Multi-site

Configure individual Vite configurations for each site by adding a Vite setup with a corresponding `vite.config.mjs` file in _each_ site package.
Make sure to run each server on a different port by configuring the `server.port` option in the Vite configuration.

Add Settings for each site in your `Settings.yaml`:

```yaml
Networkteam:
  Neos:
    Vite:
      server:
        # This is the default setting if no configuration part is found for the site package key
        _default:
          url: http://localhost:5173/

        # Specify server configuration for a specific site package key
        MyExample.Site:
          url: http://localhost:5174/
```

Make sure to run multiple Vite servers for each site package.
