<?php

namespace Rowbot\DOM\Tests;

use Closure;
use PHPUnit\Framework\Constraint\Exception;
use PHPUnit\Framework\Constraint\ExceptionCode;
use PHPUnit\Framework\Constraint\ExceptionMessage;
use PHPUnit\Framework\Constraint\ExceptionMessageRegularExpression;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Throwable;

use function call_user_func;
use function is_string;

abstract class TestCase extends PHPUnitTestCase
{
    public function assertThrows(
        Closure $callback,
        string $expectedException,
        $expectedExceptionMessage = '',
        $expectedExceptionMessageRegExp = '',
        $expectedExceptionCode = null
    ): void {
        $e = null;

        try {
            call_user_func($callback);
        } catch (Throwable $e) {
        }

        $this->assertThat($e, new Exception($expectedException));

        if (is_string($expectedExceptionMessage) && !empty($expectedExceptionMessage)) {
            $this->assertThat($e, new ExceptionMessage($expectedExceptionMessage));
        }

        if (is_string($expectedExceptionMessageRegExp) && !empty($expectedExceptionMessageRegExp)) {
            $this->assertThat(
                $e,
                new ExceptionMessageRegularExpression($expectedExceptionMessageRegExp)
            );
        }

        if ($expectedExceptionCode !== null) {
            $this->assertThat($e, new ExceptionCode($expectedExceptionCode));
        }
    }
}
