# Pilcrowls Translation Provider

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

---

This bundle provides a [Pilcrowls](https://web.pilcrowls.com) integration for [Symfony Translation](https://symfony.com/doc/current/translation.html).

It is a fork of `m2mtech/pilcrowls-translation-provider` updated for Pilcrowls specific implementation of weblate.

## Installation

```bash
composer require pilcrowls/pilcrowls-translation-provider
```

If you are not using Flex enable the bundle:

```php
// config/bundles.php

return [
    // ...
    Pilcrowls\PilcrowlsTranslationProvider\PilcrowlsTranslationProviderBundle::class => ['all' => true],
];
```

Enable the translation provider:

```yaml
# config/packages/translation.yaml
framework:
    translator:
        providers:
            pilcrowls:
                dsn: '%env(PILCROWLS_DSN)%'
                locales: ['en', 'fr', 'it']
```

and set the DSN in your .env file:

```dotenv
# .env
PILCROWLS_DSN=pilcrowls://PROJECT_NAME:API_TOKEN@PILCROWLS_PROJECT_URL
```

## Usage

```bash
bin/console translation:push [options] pilcrowls
bin/console translation:pull [options] pilcrowls
```

## Testing

This package has been developed for php 7.4 with compatibility tested for php 7.2 to 8.1.

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please see [SECURITY](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- (c) 2022 m2m server software gmbh <tech@m2m.at> and their contributors
- (c) Pilcrowls Ltd

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
