<?php
/*
 * This file is part of the pilcrowls-translation-provider package.
 *
 * (c) 2022 m2m server software gmbh <tech@m2m.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pilcrowls\PilcrowlsTranslationProvider\Tests\DependencyInjection;

use Exception;
use Pilcrowls\PilcrowlsTranslationProvider\PilcrowlsTranslationProviderBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    public function __construct()
    {
        parent::__construct('test', true);
    }

    /** @return iterable<Bundle> */
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new PilcrowlsTranslationProviderBundle(),
        ];
    }

    /**
     * @throws Exception
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->loadFromExtension('framework', [
                'translator' => [
                    'fallbacks' => ['en'],
                    'providers' => [
                        'pilcrowls' => [
                            'dsn' => 'pilcrowls://project:key@server',
                            'locales' => ['en', 'de'],
                        ],
                    ],
                ],
            ]);
        });
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new TranslatorCompilerPass());
    }
}
