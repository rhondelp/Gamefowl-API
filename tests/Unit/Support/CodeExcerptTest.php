<?php

namespace Tests\Unit\Support;

use App\Support\CodeExcerpt;
use Tests\TestCase;

/**
 * File: tests/Unit/Support/CodeExcerptTest.php
 *
 * Purpose:
 *   Pins how CodeExcerpt cuts code out of files for the technical
 *   documentation page, using tests/Fixtures/CodeExcerptFixture.php.
 *
 * Covers: whole methods with and without their docblock, braces inside
 * strings and nested closures not ending a method early, anchor-based line
 * ranges (optionally widened above and below), indentation removal, and
 * missing files/anchors/methods/ranges coming back as "missing" rather
 * than throwing.
 */
class CodeExcerptTest extends TestCase
{
    private const FIXTURE = 'tests/Fixtures/CodeExcerptFixture.php';

    public function test_method_includes_its_docblock_and_ends_at_its_own_closing_brace(): void
    {
        $excerpt = CodeExcerpt::method(self::FIXTURE, 'add');

        $this->assertTrue($excerpt->found);
        $this->assertSame([10, 19], [$excerpt->startLine, $excerpt->endLine]);
        $this->assertSame(implode("\n", [
            '/**',
            ' * Adds two numbers.',
            ' */',
            'public function add(int $a, int $b): int',
            '{',
            '    $sum = $a + $b; // START',
            '    $message = "sum is {$sum}";',
            '',
            '    return $sum; // END',
            '}',
        ]), $excerpt->code);
    }

    public function test_method_can_leave_out_the_docblock(): void
    {
        $excerpt = CodeExcerpt::method(self::FIXTURE, 'add', withDocComment: false);

        $this->assertSame([13, 19], [$excerpt->startLine, $excerpt->endLine]);
        $this->assertStringStartsWith('public function add(', $excerpt->code);
    }

    public function test_nested_closure_braces_do_not_end_the_method_early(): void
    {
        $excerpt = CodeExcerpt::method(self::FIXTURE, 'closureHolder');

        $this->assertSame([21, 26], [$excerpt->startLine, $excerpt->endLine]);
        $this->assertStringStartsWith('private static function closureHolder(): callable', $excerpt->code);
        $this->assertStringEndsWith("    };\n}", $excerpt->code);
    }

    public function test_lines_cuts_between_anchors_and_removes_shared_indentation(): void
    {
        $excerpt = CodeExcerpt::lines(self::FIXTURE, '// START', '// END');

        $this->assertSame([15, 18], [$excerpt->startLine, $excerpt->endLine]);
        $this->assertSame(implode("\n", [
            '$sum = $a + $b; // START',
            '$message = "sum is {$sum}";',
            '',
            'return $sum; // END',
        ]), $excerpt->code);
    }

    public function test_lines_can_widen_the_range_above_and_below(): void
    {
        $excerpt = CodeExcerpt::lines(self::FIXTURE, '// START', '// END', extra: 1, before: 2);

        $this->assertSame([13, 19], [$excerpt->startLine, $excerpt->endLine]);
        $this->assertStringStartsWith('public function add(', $excerpt->code);
        $this->assertStringEndsWith("\n}", $excerpt->code);
    }

    public function test_missing_code_is_reported_instead_of_throwing(): void
    {
        $missing = [
            CodeExcerpt::lines(self::FIXTURE, 'no such anchor', '// END'),
            CodeExcerpt::lines(self::FIXTURE, '// START', 'no such anchor'),
            CodeExcerpt::method(self::FIXTURE, 'noSuchMethod'),
            CodeExcerpt::method('tests/Fixtures/does-not-exist.php', 'add'),
            CodeExcerpt::lines(self::FIXTURE, '<?php', '<?php', before: 1),
        ];

        foreach ($missing as $excerpt) {
            $this->assertFalse($excerpt->found);
            $this->assertStringContainsString(CodeExcerpt::MISSING, $excerpt->code);
        }
    }
}
