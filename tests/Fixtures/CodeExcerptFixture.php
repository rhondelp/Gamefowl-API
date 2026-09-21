<?php

namespace Tests\Fixtures;

/**
 * Read as text by tests/Unit/Support/CodeExcerptTest.php; never executed.
 */
class CodeExcerptFixture
{
    /**
     * Adds two numbers.
     */
    public function add(int $a, int $b): int
    {
        $sum = $a + $b; // START
        $message = "sum is {$sum}";

        return $sum; // END
    }

    private static function closureHolder(): callable
    {
        return function () {
            return 'inner';
        };
    }
}
