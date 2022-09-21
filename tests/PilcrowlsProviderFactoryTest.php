<?php
/*
 * This file is part of the pilcrowls-translation-provider package.
 *
 * (c) 2022 m2m server software gmbh <tech@m2m.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pilcrowls\PilcrowlsTranslationProvider\Tests;

use Pilcrowls\PilcrowlsTranslationProvider\PilcrowlsProviderFactory;
use Symfony\Component\Translation\Provider\ProviderFactoryInterface;

class PilcrowlsProviderFactoryTest extends ProviderFactoryTestCase
{
    public function createFactory(): ProviderFactoryInterface
    {
        return new PilcrowlsProviderFactory(
            $this->getClient(),
            $this->getLoader(),
            $this->getLogger(),
            $this->getXliffFileDumper(),
            $this->getDefaultLocale(),
            ['https' => true, 'verify_peer' => true]
        );
    }

    public function supportsProvider(): iterable
    {
        yield [true, 'pilcrowls://project:key@server'];
        yield [false, 'somethingElse://project:key@server'];
    }

    public function unsupportedSchemeProvider(): iterable
    {
        yield ['somethingElse://project:key@server', 'scheme is not supported'];
    }

    public function createProvider(): iterable
    {
        yield [
            'pilcrowls://server',
            'pilcrowls://project:key@server',
        ];
    }

    public function incompleteDsnProvider(): iterable
    {
        yield ['pilcrowls://project@default', 'Password is not set'];
        yield ['pilcrowls://default', 'Password is not set'];
    }
}
