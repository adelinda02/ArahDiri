<?php

namespace Tests;

use App\Support\Settings;
use App\Support\SmaTracks;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Cache statis per proses: kosongkan supaya tiap tes membaca basis datanya sendiri.
        Settings::flush();
        SmaTracks::flush();
    }
}
