TypeScript Bundle For Symfony!
=================

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
