<?php

namespace Illuminate\Tests\Foundation\Testing\Concerns;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\TestCase as FoundationTestCase;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\Concerns\CreatesApplication;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class InteractsWithTestCaseLifecycleTest extends TestCase
{
    public function testTearDownReleasesUniqueJobLocksTakenWhileTheQueueWasFaked()
    {
        $testCase = new TestCaseWithFakedQueue('foo');

        (new ReflectionMethod($testCase, 'setUpTheTestEnvironment'))->invoke($testCase);

        $store = $testCase->dispatchUniqueJob();

        $this->assertArrayHasKey('laravel_unique_job:'.LifecycleUniqueJob::class.':', $store->locks);

        (new ReflectionMethod($testCase, 'tearDownTheTestEnvironment'))->invoke($testCase);

        $this->assertSame([], $store->locks);
    }
}

class TestCaseWithFakedQueue extends FoundationTestCase
{
    use CreatesApplication;

    public function dispatchUniqueJob()
    {
        Queue::fake();

        dispatch(new LifecycleUniqueJob);

        return $this->app['cache']->store()->getStore();
    }
}

class LifecycleUniqueJob implements ShouldQueue, ShouldBeUnique
{
    //
}
