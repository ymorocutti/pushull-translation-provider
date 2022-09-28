<?php
/*
 * This file is part of the pushull-translation-provider package.
 *
 * (c) 2022 m2m server software gmbh <tech@m2m.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Pushull\PushullTranslationProvider\PushullProvider;
use Pushull\PushullTranslationProvider\PushullProviderFactory;

use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set('pushull.translation.provider_factory.pushull', PushullProviderFactory::class)
        ->args([
            service('http_client'),
            service('translation.loader.xliff'),
            service('logger'),
            service('translation.dumper.xliff'),
            param('kernel.default_locale'),
            abstract_arg('bundle config'),
        ])
        ->tag('translation.provider_factory');

    $services->set('pushull.translation.provider.pushull', PushullProvider::class);
};
