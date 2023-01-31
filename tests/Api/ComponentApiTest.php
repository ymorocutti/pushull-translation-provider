<?php
/*
 * This file is part of the pushull-translation-provider package.
 *
 * (c) 2022 m2m server software gmbh <tech@m2m.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pushull\PushullTranslationProvider\Tests\Api;

use Psr\Log\LoggerInterface;
use Pushull\PushullTranslationProvider\Api\ComponentApi;
use Pushull\PushullTranslationProvider\Api\DTO\Component;
use Pushull\PushullTranslationProvider\Tests\Api\DTO\DTOFaker;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class ComponentApiTest extends ApiTest
{
    /**
     * @param callable[] $responses
     */
    private function setupFactory(array $responses): void
    {
        ComponentApi::setup(
            new MockHttpClient($responses, 'https://v5.3.ignores/baseUri'),
            $this->createMock(LoggerInterface::class),
            'project',
            'en'
        );
    }

    private function getGetComponentResponse(array $component): callable
    {
        return $this->getResponse(
            '/components/project/'.$component['slug'].'/',
            'GET',
            '',
            (string) json_encode($component)
        );
    }

    /**
     * @param array<array<string,string>> $results
     */
    private function getGetComponentsResponse(array $results): callable
    {
        return $this->getResponse(
            '/projects/project/components/?page=1',
            'GET',
            '',
            (string) json_encode(['results' => $results])
        );
    }

    /**
     * @param array<string,string> $result
     */
    private function getAddComponentResponse(string $fileContent, array $result): callable
    {
        return $this->getResponse(
            '/projects/project/components/',
            'POST',
            $fileContent,
            (string) json_encode($result),
            201
        );
    }

    private function getDeleteComponentResponse(Component $component): callable
    {
        return $this->getResponse(
            $component->url,
            'DELETE',
            '',
            '',
            204
        );
    }

    /**
     * @throws ExceptionInterface
     */
    public function testGetComponentsEmpty(): void
    {
        $this->setupFactory([$this->getGetComponentsResponse([])]);

        $this->assertEmpty(ComponentApi::getComponents());
    }

    /**
     * @throws ExceptionInterface
     */
    public function testGetComponents(): void
    {
        $results = [
            DTOFaker::createComponentData(),
            DTOFaker::createComponentData(),
        ];
        $this->setupFactory([$this->getGetComponentsResponse($results)]);

        $components = ComponentApi::getComponents();
        foreach ($results as $result) {
            $this->assertSame($result['translations_url'], $components[$result['slug']]->translations_url);
        }
    }

    public function testGetOneComponent(): void
    {
        $component = DTOFaker::createComponentData();
        $this->setupFactory([$this->getGetComponentResponse($component)]);

        $result = ComponentApi::getOneComponent($component['slug']);
        $this->assertEquals($component['slug'], $result->slug);
        $this->assertInstanceOf(Component::class, $result);

        $this->assertTrue(ComponentApi::hasComponent($result->slug));
    }

    public function testMultipleGetComponents(): void
    {
        $oneComponent = DTOFaker::createComponentData();
        $multipleComponents = [
            DTOFaker::createComponentData(),
            DTOFaker::createComponentData(),
        ];

        $this->setupFactory([
            $this->getGetComponentResponse($oneComponent),
            $this->getGetComponentsResponse($multipleComponents),
        ]);

        // When calling getOneComponent, response will be added in cache. But this cache is incomplete.
        // If there is call to getComponents, we need to make sure this cache won't be served.
        $result = ComponentApi::getOneComponent($oneComponent['slug']);
        $this->assertEquals($oneComponent['slug'], $result->slug);

        $components = ComponentApi::getComponents();
        foreach ($multipleComponents as $result) {
            $this->assertSame($result['translations_url'], $components[$result['slug']]->translations_url);
        }
    }

    /**
     * @throws ExceptionInterface
     */
    public function testHasComponentFalse(): void
    {
        $this->setupFactory([
            $this->getGetComponentsResponse([]),
            $this->getGetComponentsResponse([
                DTOFaker::createComponentData(),
            ]),
        ]);

        $this->assertFalse(ComponentApi::hasComponent('notExisting'));

        // calling getComponents a second time because it was empty the first time
        $this->assertFalse(ComponentApi::hasComponent('notExisting'));

        // not calling getComponents a third time
        $this->assertFalse(ComponentApi::hasComponent('notExisting'));
    }

    /**
     * @throws ExceptionInterface
     */
    public function testHasComponent(): void
    {
        $data = DTOFaker::createComponentData();
        $this->setupFactory([$this->getGetComponentsResponse([$data])]);

        $this->assertTrue(ComponentApi::hasComponent($data['slug']));

        // not calling getComponents a second time
        $this->assertTrue(ComponentApi::hasComponent($data['slug']));
    }

    /**
     * @throws ExceptionInterface
     */
    public function testAddComponent(): void
    {
        $content = DTOFaker::getFaker()->paragraph();
        $data = DTOFaker::createComponentData();
        $newComponent = new Component($data);
        $newComponent->created = true;

        $this->setupFactory([$this->getAddComponentResponse($content, $data)]);

        $component = ComponentApi::addComponent($newComponent->slug, $content);
        $this->assertEquals($newComponent, $component);

        $components = ComponentApi::getComponents();
        $this->assertEquals($newComponent, $components[$newComponent->slug]);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testGetComponent(): void
    {
        $existingData = DTOFaker::createComponentData();
        $existingComponent = new Component($existingData);
        $newData = DTOFaker::createComponentData();
        $newComponent = new Component($newData);
        $newComponent->created = true;
        $newContent = DTOFaker::getFaker()->paragraph();

        $this->setupFactory([
            $this->getGetComponentsResponse([$existingData]),
            $this->getAddComponentResponse($newContent, $newData),
        ]);

        $component = ComponentApi::getComponent($existingComponent->slug);
        if (!$component) {
            $this->fail();
        }

        $this->assertEquals($existingComponent, $component);

        $this->assertNull(ComponentApi::getComponent($newComponent->slug));

        $component = ComponentApi::getComponent($newComponent->slug, $newContent);
        if (!$component) {
            $this->fail();
        }

        $this->assertEquals($newComponent, $component);

        $components = ComponentApi::getComponents();
        $this->assertEquals($newComponent, $components[$newComponent->slug]);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testDeleteComponent(): void
    {
        $component = DTOFaker::createComponent();

        $this->setupFactory([
            $this->getDeleteComponentResponse($component),
        ]);

        ComponentApi::deleteComponent($component);
    }
}
