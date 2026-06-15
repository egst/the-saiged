<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Core;

use RuntimeException;
use TheSaiged\Core\Env;
use TheSaiged\Tests\TestCase;

final class EnvTest extends TestCase {

    protected function tearDown (): void {
        parent::tearDown();
        putenv('THE_SAIGED_TEST_VAR');
    }

    function testRequiredReturnsValue (): void {
        putenv('THE_SAIGED_TEST_VAR=hello');
        $this->assertSame('hello', Env::required('THE_SAIGED_TEST_VAR'));
    }

    function testRequiredThrowsOnMissing (): void {
        putenv('THE_SAIGED_TEST_VAR');
        $this->expectException(RuntimeException::class);
        Env::required('THE_SAIGED_TEST_VAR');
    }

    function testRequiredThrowsOnEmptyString (): void {
        putenv('THE_SAIGED_TEST_VAR=');
        $this->expectException(RuntimeException::class);
        Env::required('THE_SAIGED_TEST_VAR');
    }

}
