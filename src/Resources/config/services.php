<?php
/*
 * This file is part of the pilcrowls-translation-provider package.
 *
 * (c) 2022 m2m server software gmbh <tech@m2m.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Pilcrowls\PilcrowlsTranslationProvider\PilcrowlsProvider;
use Pilcrowls\PilcrowlsTranslationProvider\PilcrowlsProviderFactory;

use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set('pilcrowls.translation.provider_factory.pilcrowls', PilcrowlsProviderFactory::class)
        ->args([
            service('http_client'),
            service('translation.loader.xliff'),
            service('logger'),
            service('translation.dumper.xliff'),
            param('kernel.default_locale'),
            abstract_arg('bundle config'),
        ])
        ->tag('translation.provider_factory');

    $services->set('pilcrowls.translation.provider.pilcrowls', PilcrowlsProvider::class);
};
