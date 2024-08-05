# TypeScript Bundle For Symfony!

[![Latest Version](https://img.shields.io/github/release/sensiolabs/AssetMapperTypeScriptBundle.svg?style=flat-square)](https://github.com/sensiolabs/AssetMapperTypeScriptBundle/releases)
[![Total Downloads](https://poser.pugx.org/sensiolabs/typescript-bundle/downloads)](https://packagist.org/packages/sensiolabs/typescript-bundle)
[![Monthly Downloads](https://poser.pugx.org/sensiolabs/typescript-bundle/d/monthly.png)](https://packagist.org/packages/sensiolabs/typescript-bundle)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENCE)
[![CI](https://github.com/sensiolabs/AssetMapperTypeScriptBundle/actions/workflows/ci.yaml/badge.svg?branch=main)](https://github.com/sensiolabs/AssetMapperTypeScriptBundle/actions/workflows/ci.yaml?query=branch%3Amain)

This bundle allows you to compile TypeScript and use it with Symfony's AssetMapper Component
(no Node required!).

- Automatically downloads the correct [SWC](https://github.com/swc-project/swc) binary
- Adds a ``typescript:build`` command to compile your typescript files
- Automatically compiles your typescript files when you run ``asset-map:compile`` command

## Installation
```bash
composer require sensiolabs/typescript-bundle
```

## Documentation

Read the documentation at: [https://symfony.com/bundles/AssetMapperTypeScriptBundle/current/index.html](https://symfony.com/bundles/AssetMapperTypeScriptBundle/current/index.html)

## Credits
This bundle was greatly inspired by the [Sass Bundle](https://github.com/SymfonyCasts/sass-bundle) from [SymfonyCasts](https://github.com/SymfonyCasts).
- [Maelan Le Borgne](https://github.com/maelanleborgne)
- [All Contributors](../../contributors)

## License

MIT License (MIT): see the [License File](LICENSE) for more details.
