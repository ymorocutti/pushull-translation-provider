<?php
/*
 * This file is part of the pushull-translation-provider package.
 *
 * (c) 2022 m2m server software gmbh <tech@m2m.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pushull\PushullTranslationProvider;

use Psr\Log\LoggerInterface;
use Pushull\PushullTranslationProvider\Api\ComponentApi;
use Pushull\PushullTranslationProvider\Api\TranslationApi;
use Pushull\PushullTranslationProvider\Api\UnitApi;
use Symfony\Component\Translation\Catalogue\AbstractOperation;
use Symfony\Component\Translation\Catalogue\TargetOperation;
use Symfony\Component\Translation\Dumper\XliffFileDumper;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Provider\ProviderInterface;
use Symfony\Component\Translation\TranslatorBag;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PushullProvider implements ProviderInterface
{
    /** @var LoaderInterface */
    private $loader;

    /** @var LoggerInterface */
    private $logger;

    /** @var XliffFileDumper */
    private $xliffFileDumper;

    /** @var string */
    private $defaultLocale;

    /** @var string */
    private $endpoint;

    public function __construct(
        HttpClientInterface $client,
        LoaderInterface $loader,
        LoggerInterface $logger,
        XliffFileDumper $xliffFileDumper,
        string $defaultLocale,
        string $endpoint,
        string $project
    ) {
        $this->loader = $loader;
        $this->logger = $logger;
        $this->xliffFileDumper = $xliffFileDumper;

        $this->defaultLocale = $defaultLocale;

        $this->endpoint = $endpoint;

        ComponentApi::setup($client, $logger, $project, $defaultLocale);
        TranslationApi::setup($client, $logger);
        UnitApi::setup($client, $logger);
    }

    public function __toString(): string
    {
        return sprintf('pushull://%s', $this->endpoint);
    }

    protected function loadFirstDomain(array $domains): void
    {
        // If we try to fetch only one domain, don't load them all
        if (1 === count($domains) && ($domain = reset($domains))) {
            ComponentApi::getOneComponent(self::domainNormalize($domain));
        }
    }

    private static function domainNormalize(string $domain): string
    {
        return str_replace('.', '_dot_', $domain);
    }

    private static function domainDenormalize(string $domain): string
    {
        return str_replace('_dot_', '.', $domain);
    }

    /**
     * @throws ExceptionInterface
     */
    public function write(TranslatorBagInterface $translatorBag): void
    {
        /** @var MessageCatalogue $catalogue */
        foreach ($translatorBag->getCatalogues() as $catalogue) {
            $this->loadFirstDomain($catalogue->getDomains());

            foreach ($catalogue->getDomains() as $domain) {
                if (0 === count($catalogue->all($domain))) {
                    $this->logger->info('Catalog is empty for '.$domain);
                    continue;
                }

                $content = $this->xliffFileDumper->formatCatalogue($catalogue, $domain, ['default_locale' => $this->defaultLocale]);
                $component = ComponentApi::getComponent(self::domainNormalize($domain), $content);
                if (!$component) {
                    $this->logger->error('Could not get/add component for '.$domain);

                    continue;
                }

                if ($component->created && $catalogue->getLocale() === $this->defaultLocale) {
                    continue;
                }

                $translation = TranslationApi::getTranslation($component, $catalogue->getLocale());

                if ($translation->created) {
                    TranslationApi::uploadTranslation($translation, $content);

                    continue;
                }

                $file = TranslationApi::downloadTranslation($translation);
                $weblateCatalogue = $this->loader->load($file, $catalogue->getLocale(), $domain);

                $operation = new TargetOperation($catalogue, $weblateCatalogue);
                $operation->moveMessagesToIntlDomainsIfPossible(AbstractOperation::NEW_BATCH);
                $catalogue->add($operation->getNewMessages($domain), $domain);
                $content = $this->xliffFileDumper->formatCatalogue($catalogue, $domain, ['default_locale' => $this->defaultLocale]);
                TranslationApi::uploadTranslation($translation, $content);
            }
        }
    }

    /**
     * @param array<string> $domains
     * @param array<string> $locales
     *
     * @throws ExceptionInterface
     */
    public function read(array $domains, array $locales): TranslatorBag
    {
        if (!$domains) {
            $domains = array_keys(ComponentApi::getComponents());
        }

        // If we try to fetch only one domain, don't load them all
        $this->loadFirstDomain($domains);

        $translatorBag = new TranslatorBag();

        foreach ($domains as $domain) {
            $component = ComponentApi::getComponent(self::domainNormalize($domain));
            if (!$component) {
                continue;
            }

            foreach ($locales as $locale) {
                $translation = TranslationApi::getTranslation($component, $locale);

                $file = TranslationApi::downloadTranslation($translation);
                $translatorBag->addCatalogue($this->loader->load($file, $locale, self::domainDenormalize($domain)));
            }
        }

        return $translatorBag;
    }

    /**
     * @throws ExceptionInterface
     */
    public function delete(TranslatorBagInterface $translatorBag): void
    {
        /** @var MessageCatalogue $catalogue */
        foreach ($translatorBag->getCatalogues() as $catalogue) {
            $this->loadFirstDomain($catalogue->getDomains());

            foreach ($catalogue->getDomains() as $domain) {
                if (0 === count($catalogue->all($domain))) {
                    continue;
                }

                $component = ComponentApi::getComponent(self::domainNormalize($domain));
                if (!$component) {
                    continue;
                }

                if (!TranslationApi::hasTranslation($component, $catalogue->getLocale())) {
                    continue;
                }

                $translation = TranslationApi::getTranslation($component, $catalogue->getLocale());

                foreach ($catalogue->all($domain) as $key => $message) {
                    $unit = UnitApi::getUnit($translation, $key);
                    if (!$unit) {
                        continue;
                    }

                    UnitApi::deleteUnit($unit);
                }
            }
        }
    }
}
