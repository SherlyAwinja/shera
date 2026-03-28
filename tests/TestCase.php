<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $compiledViewsPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'shera-testing-views';

        if (! is_dir($compiledViewsPath)) {
            mkdir($compiledViewsPath, 0777, true);
        }

        config()->set('view.compiled', $compiledViewsPath);
        config()->set('logging.default', 'errorlog');
    }
}
