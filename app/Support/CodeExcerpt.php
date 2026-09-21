<?php

namespace App\Support;

/**
 * File: app/Support/CodeExcerpt.php
 *
 * Purpose:
 *   Reads real lines out of the codebase for the technical documentation
 *   page (/documentation/technical), so every code block there is the code
 *   that actually runs. Nothing is copied into the page by hand, and the
 *   line numbers shown are computed, not typed.
 *
 * How it fits into the project:
 *   Used only by resources/views/documentation/technical.blade.php. When an
 *   anchor stops matching (the code changed shape), the excerpt comes back
 *   marked as missing instead of breaking the page, and
 *   TechnicalDocumentationPageTest fails until the page is updated.
 */
final class CodeExcerpt
{
    /**
     * Shown in place of code whose anchors no longer match.
     */
    public const MISSING = 'Excerpt unavailable';

    /**
     * File contents split into lines, read at most once per process.
     *
     * @var array<string, array<int, string>|null>
     */
    private static array $files = [];

    private function __construct(
        public readonly string $path,
        public readonly int $startLine,
        public readonly int $endLine,
        public readonly string $code,
        public readonly bool $found,
    ) {
    }

    /**
     * The first line containing $from through the first line at or after it
     * containing $to, widened by $before lines above (e.g. 1 to take in a
     * docblock's opening) and $extra lines below (e.g. a closing brace).
     *
     * @param  string  $path  relative to the project root
     */
    public static function lines(string $path, string $from, string $to, int $extra = 0, int $before = 0): self
    {
        $lines = self::read($path);
        $start = $lines === null ? null : self::find($lines, $from, 0);
        $end = $start === null ? null : self::find($lines, $to, $start);

        if ($end === null || $start - $before < 0 || $end + $extra >= count($lines)) {
            return self::missing($path);
        }

        return self::slice($path, $lines, $start - $before, $end + $extra);
    }

    /**
     * A whole function or method, including the docblock directly above it
     * unless $withDocComment is false. Located with PHP's own tokenizer
     * rather than by loading the class, so it works for test files too.
     *
     * @param  string  $path  relative to the project root
     */
    public static function method(string $path, string $name, bool $withDocComment = true): self
    {
        $lines = self::read($path);

        if ($lines === null) {
            return self::missing($path);
        }

        $tokens = token_get_all(implode("\n", $lines));
        $line = 1;
        $docLine = null;
        $declarationLine = null;
        $startLine = null;
        $depth = 0;

        foreach ($tokens as $index => $token) {
            [$type, $text] = is_array($token) ? [$token[0], $token[1]] : [null, $token];
            $tokenLine = is_array($token) ? $token[2] : $line;
            $line = $tokenLine + substr_count($text, "\n");

            // Phase 1: find the declaration, remembering a docblock and the
            // first modifier (public/static/...) seen directly above it.
            if ($startLine === null) {
                if ($type === T_DOC_COMMENT) {
                    [$docLine, $declarationLine] = [$tokenLine, null];
                } elseif (in_array($type, [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_ABSTRACT, T_FINAL], true)) {
                    $declarationLine ??= $tokenLine;
                } elseif ($type === T_FUNCTION && self::nameAfter($tokens, $index) === $name) {
                    $startLine = $withDocComment && $docLine !== null ? $docLine : ($declarationLine ?? $tokenLine);
                } elseif ($type !== T_WHITESPACE) {
                    [$docLine, $declarationLine] = [null, null];
                }

                continue;
            }

            // Phase 2: follow the braces to the end of the body.
            if ($text === '{' || $type === T_CURLY_OPEN || $type === T_DOLLAR_OPEN_CURLY_BRACES) {
                $depth++;
            } elseif ($text === '}' && --$depth === 0) {
                return self::slice($path, $lines, $startLine - 1, $tokenLine - 1);
            } elseif ($text === ';' && $depth === 0) {
                return self::slice($path, $lines, $startLine - 1, $tokenLine - 1); // no body (abstract/interface)
            }
        }

        return self::missing($path);
    }

    /**
     * Name of the function whose `function` keyword sits at $index, or null
     * for a closure.
     *
     * @param  array<int, array{0: int, 1: string, 2: int}|string>  $tokens
     */
    private static function nameAfter(array $tokens, int $index): ?string
    {
        for ($i = $index + 1; $i < count($tokens); $i++) {
            $token = $tokens[$i];

            if ((is_array($token) && $token[0] === T_WHITESPACE) || $token === '&') {
                continue;
            }

            return is_array($token) && $token[0] === T_STRING ? $token[1] : null;
        }

        return null;
    }

    /**
     * @return array<int, string>|null  null when the file does not exist
     */
    private static function read(string $path): ?array
    {
        if (! array_key_exists($path, self::$files)) {
            $fullPath = base_path($path);
            self::$files[$path] = is_file($fullPath) ? preg_split('/\R/', (string) file_get_contents($fullPath)) : null;
        }

        return self::$files[$path];
    }

    /**
     * Index of the first line at or after $offset that contains $needle.
     *
     * @param  array<int, string>  $lines
     */
    private static function find(array $lines, string $needle, int $offset): ?int
    {
        for ($i = $offset; $i < count($lines); $i++) {
            if (str_contains($lines[$i], $needle)) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Lines $first..$last (0-based, inclusive), with their shared leading
     * indentation removed.
     *
     * @param  array<int, string>  $lines
     */
    private static function slice(string $path, array $lines, int $first, int $last): self
    {
        $chunk = array_slice($lines, $first, $last - $first + 1);
        $indents = array_map(
            fn (string $line) => strlen($line) - strlen(ltrim($line, ' ')),
            array_filter($chunk, fn (string $line) => trim($line) !== ''),
        );
        $cut = $indents === [] ? 0 : min($indents);

        $code = implode("\n", array_map(fn (string $line) => rtrim(substr($line, $cut)), $chunk));

        return new self($path, $first + 1, $last + 1, $code, true);
    }

    private static function missing(string $path): self
    {
        return new self($path, 0, 0, '// '.self::MISSING." — the code this section describes has changed. See {$path}.", false);
    }
}
