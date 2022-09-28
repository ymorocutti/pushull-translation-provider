<?php
/*
 * This file is part of the pushull-translation-provider package.
 *
 * (c) 2022 m2m server software gmbh <tech@m2m.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pushull\PushullTranslationProvider\Tests;

use Pushull\PushullTranslationProvider\PushullProviderFactory;
use Symfony\Component\Translation\Provider\ProviderFactoryInterface;

class PushullProviderFactoryTest extends ProviderFactoryTestCase
{
    public function createFactory(): ProviderFactoryInterface
    {
        return new PushullProviderFactory(
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
        yield [true, 'pushull://project:key@server'];
        yield [false, 'somethingElse://project:key@server'];
    }

    public function unsupportedSchemeProvider(): iterable
    {
        yield ['somethingElse://project:key@server', 'scheme is not supported'];
    }

    public function createProvider(): iterable
    {
        yield [
            'pushull://server',
            'pushull://project:key@server',
        ];
    }

    public function incompleteDsnProvider(): iterable
    {
        yield ['pushull://project@default', 'Password is not set'];
        yield ['pushull://default', 'Password is not set'];
    }
}
