<?php
/*
 * This file is part of the pushull-translation-provider package.
 *
 * (c) 2022 m2m server software gmbh <tech@m2m.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pushull\PushullTranslationProvider\Api;

use Psr\Log\LoggerInterface;
use Pushull\PushullTranslationProvider\Api\DTO\Component;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\Translation\Exception\ProviderException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ComponentApi
{
    /** @var array<string,Component> */
    private static $components = [];

    private static $partialResponse = false;

    /** @var HttpClientInterface */
    private static $client;

    /** @var LoggerInterface */
    private static $logger;

    /** @var string */
    private static $project;

    /** @var string */
    private static $defaultLocale;

    public static function setup(
        HttpClientInterface $client,
        LoggerInterface $logger,
        string $project,
        string $defaultLocale
    ): void {
        self::$client = $client;
        self::$logger = $logger;
        self::$project = $project;
        self::$defaultLocale = $defaultLocale;

        self::$components = [];
    }

    /**
     * @return array<string,Component>
     *
     * @throws ExceptionInterface
     */
    public static function getComponents(bool $reload = false): array
    {
        if ($reload) {
            self::$components = [];
        }

        if (self::$components && false === self::$partialResponse) {
            return self::$components;
        }

        self::$partialResponse = false;

        /**
         * GET /api/projects/(string: project)/components/.
         *
         * @see https://docs.weblate.org/en/latest/api.html#get--api-projects-(string-project)-components-
         */
        $page = 1;
        do {
            $response = self::$client->request('GET', 'projects/'.self::$project.'/components/?'.http_build_query(['page' => $page]));

            if (200 !== $response->getStatusCode()) {
                self::$logger->debug($response->getStatusCode().': '.$response->getContent(false));
                throw new ProviderException('Unable to get pushull components.', $response);
            }

            $results = $response->toArray();

            foreach ($results['results'] ?? [] as $result) {
                $component = new Component($result);

                if ('glossary' === $component->slug) {
                    continue;
                }

                self::$components[$component->slug] = $component;
                self::$logger->debug('Loaded component '.$component->slug);
            }

            ++$page;
            $nextUrl = $results['next'] ?? null;
        } while (null !== $nextUrl);

        return self::$components;
    }

    /**
     * GET /api/components/(string: project)/(string: component)/.
     *
     * @see https://docs.weblate.org/en/latest/api.html#get--api-components-(string-project)-(string-component)-
     */
    public static function getOneComponent(string $component): ?Component
    {
        if (self::$components && isset(self::$components[$component])) {
            return self::$components[$component];
        }

        self::$partialResponse = true;

        $response = self::$client->request('GET', 'components/'.self::$project.'/'.$component.'/');

        if (200 !== $response->getStatusCode()) {
            return null;
        }

        $result = $response->toArray();

        $component = new Component($result);

        self::$components[$component->slug] = $component;
        self::$logger->debug('Loaded component '.$component->slug);

        return $component;
    }

    /**
     * @throws ExceptionInterface
     */
    public static function hasComponent(string $slug): bool
    {
        // Check if it's been loaded using single component
        if (isset(self::$components[$slug])) {
            return true;
        }

        // Otherwise, load everything and check again
        self::getComponents();
        if (isset(self::$components[$slug])) {
            return true;
        }

        return false;
    }

    /**
     * @throws ExceptionInterface
     */
    public static function getComponent(string $slug, string $optionalContent = ''): ?Component
    {
        if (self::hasComponent($slug)) {
            return self::$components[$slug];
        }

        if (!$optionalContent) {
            return null;
        }

        return self::addComponent($slug, $optionalContent);
    }

    /**
     * @throws ExceptionInterface
     */
    public static function addComponent(string $domain, string $content): Component
    {
        $content = str_replace('<trans-unit', '<trans-unit xml:space="preserve"', $content);

        /**
         * POST /api/projects/(string: project)/components/.
         *
         * @see https://docs.weblate.org/en/latest/api.html#post--api-projects-(string-project)-components-
         */
        $formFields = [
            'name' => $domain,
            'slug' => $domain,
            'edit_template' => 'true',
            'manage_units' => 'true',
            'source_language' => self::$defaultLocale,
            'file_format' => 'xliff',
            'docfile' => new DataPart($content, $domain.'/'.self::$defaultLocale.'.xlf'),
        ];
        $formData = new FormDataPart($formFields);

        $response = self::$client->request('POST', 'projects/'.self::$project.'/components/', [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToString(),
        ]);

        if (201 !== $response->getStatusCode()) {
            self::$logger->debug($response->getStatusCode().': '.$response->getContent(false));
            throw new ProviderException('Unable to add pushull component '.$domain.'.', $response);
        }

        $result = $response->toArray();
        $component = new Component($result);
        $component->created = true;
        self::$components[$component->slug] = $component;

        self::$logger->debug('Added component '.$component->slug);

        return $component;
    }

    /**
     * @throws ExceptionInterface
     */
    public static function deleteComponent(Component $component): void
    {
        /**
         * DELETE /api/components/(string: project)/(string: component)/.
         *
         * @see https://docs.weblate.org/en/latest/api.html#delete--api-components-(string-project)-(string-component)-
         */
        $response = self::$client->request('DELETE', $component->url);

        if (204 !== $response->getStatusCode()) {
            self::$logger->debug($response->getStatusCode().': '.$response->getContent(false));
            throw new ProviderException('Unable to delete pushull component '.$component->slug.'.', $response);
        }

        unset(self::$components[$component->slug]);

        self::$logger->debug('Deleted component '.$component->slug);
    }

    /**
     * @throws ExceptionInterface
     */
    public static function commitComponent(Component $component): void
    {
        /**
         * POST /api/components/(string: project)/(string: component)/repository/.
         *
         * @see https://docs.weblate.org/en/latest/api.html#post--api-components-(string-project)-(string-component)-repository-
         */
        $response = self::$client->request('POST', $component->repository_url, [
            'body' => ['operation' => 'commit'],
        ]);

        if (200 !== $response->getStatusCode()) {
            self::$logger->debug($response->getStatusCode().': '.$response->getContent(false));
            throw new ProviderException('Unable to commit pushull component '.$component->slug.'.', $response);
        }

        self::$logger->debug('Committed component '.$component->slug);
    }
}
