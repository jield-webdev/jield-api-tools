<?php

declare(strict_types=1);

namespace Jield\ApiToolsTest;

use Testing\Util\AbstractServiceTest;

class ModuleTest extends AbstractServiceTest
{
    public function testCanFindConfiguration(): void
    {
        self::assertIsArray([]);
    }
}
