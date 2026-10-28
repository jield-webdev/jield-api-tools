<?php

declare(strict_types=1);

namespace Jield\ApiToolsTest;

use Jield\ApiTools\ListenerConfigProvider;
use Jield\ApiTools\Module;
use Jield\ApiToolsTest\Fixture\Listeners\ExampleListener;
use PHPUnit\Framework\TestCase;

final class ListenerConfigProviderTest extends TestCase
{
    public function testPackageConfigurationDoesNotAssumeAnApplicationDirectory(): void
    {
        self::assertSame([], (new ListenerConfigProvider())());
        self::assertIsArray((new Module())->getConfig());
    }

    public function testDiscoversConfiguredDirectoryAndNamespaceWithoutAConstructor(): void
    {
        $config = (new ListenerConfigProvider([
            'Jield\\ApiToolsTest\\Fixture\\Listeners' => __DIR__ . '/Fixture/Listeners',
        ]))();
        self::assertSame('/example', $config['router']['routes'][ExampleListener::class]['options']['route']);
        self::assertSame([], $config[\Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory::class][ExampleListener::class]);
        self::assertSame(['GET'], $config['api-tools-rest'][ExampleListener::class]['collection_http_methods']);
    }
}
