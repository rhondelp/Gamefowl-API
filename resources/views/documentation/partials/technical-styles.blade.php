{{-- Styles for the technical documentation page only (technical.blade.php):
     code blocks and their syntax colours, the call chain, annotations, guard
     notes, and the worked-example cards. Shared styles live in
     base-styles.blade.php. --}}
<style>
    /* ── Code blocks ──────────────────────────────────────────── */
    .code {
        --code-bg: #0f1a14;
        --code-text: #d6f2df;
        --code-muted: #7f9f8b;
        margin: 16px 0 22px;
        overflow: hidden;
        background: var(--code-bg);
        border: 1px solid #1e2d24;
        border-radius: 14px;
        box-shadow: var(--shadow);
    }

    .code-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 4px 14px;
        padding: 9px 16px;
        background: #16241c;
        border-bottom: 1px solid #22342a;
        color: var(--code-muted);
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 12px;
        line-height: 1.5;
    }

    .code-file {
        color: var(--code-text);
        overflow-wrap: anywhere;
    }

    .code-meta {
        flex: none;
    }

    .code-body {
        display: flex;
    }

    .code-gutter,
    .code-pre {
        margin: 0;
        padding: 14px 0;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 12.5px;
        line-height: 1.7;
    }

    .code-gutter {
        flex: none;
        padding-left: 14px;
        padding-right: 12px;
        border-right: 1px solid #1e2d24;
        color: #587565;
        text-align: right;
        user-select: none;
    }

    /* Annotation marker in place of a line number */
    .code-gutter b {
        display: inline-block;
        min-width: 18px;
        padding: 0 5px;
        border-radius: 999px;
        background: var(--attention);
        color: #1f1300;
        font-size: 11px;
        font-weight: 700;
        text-align: center;
    }

    .code-pre {
        flex: 1;
        min-width: 0;
        overflow-x: auto;
        padding-left: 16px;
        padding-right: 16px;
        color: var(--code-text);
    }

    .code-pre code {
        padding: 0;
        background: none;
        border-radius: 0;
        color: inherit;
        font-size: inherit;
        white-space: pre;
        overflow-wrap: normal;
    }

    .code.is-missing {
        border: 2px solid var(--attention);
    }

    /* highlight.js token colours, in the page's palette. Without the
       script the code simply stays in the plain text colour above. */
    .hljs-comment,
    .hljs-quote {
        color: var(--code-muted);
        font-style: italic;
    }

    .hljs-doctag,
    .hljs-meta {
        color: #c4b5fd;
    }

    .hljs-keyword,
    .hljs-literal,
    .hljs-built_in,
    .hljs-type {
        color: #86efac;
    }

    .hljs-variable,
    .hljs-template-variable {
        color: #fcd34d;
    }

    .hljs-string,
    .hljs-regexp,
    .hljs-symbol {
        color: #fdba74;
    }

    .hljs-number {
        color: #f9a8d4;
    }

    .hljs-title,
    .hljs-title.function_,
    .hljs-title.class_ {
        color: #93c5fd;
    }

    .hljs-attr,
    .hljs-property {
        color: #a5f3fc;
    }

    .hljs-params,
    .hljs-operator,
    .hljs-punctuation,
    .hljs-subst {
        color: var(--code-text);
    }

    /* ── Hero links ───────────────────────────────────────────── */
    .hero-links {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px 14px;
        margin-top: 24px;
    }

    .hero-links span {
        color: var(--text-muted);
        font-size: 12.5px;
    }

    /* ── Section 1: call chain ────────────────────────────────── */
    .chain {
        list-style: none;
        margin: 22px 0 10px;
    }

    .chain > li {
        position: relative;
        display: grid;
        grid-template-columns: 34px minmax(0, 1fr);
        gap: 14px;
        padding-bottom: 16px;
    }

    .chain > li::before {
        content: '';
        position: absolute;
        left: 16px;
        top: 38px;
        bottom: 2px;
        border-left: 2px dashed var(--border-strong);
    }

    .chain > li:last-child {
        padding-bottom: 0;
    }

    .chain > li:last-child::before {
        display: none;
    }

    .chain-num {
        position: relative;
        z-index: 1;
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: grid;
        place-items: center;
        background: var(--surface);
        border: 1px solid var(--border-strong);
        color: var(--text-primary);
        font-size: 13px;
        font-weight: 700;
    }

    .chain-card {
        min-width: 0;
        padding: 12px 16px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 14px;
        box-shadow: var(--shadow);
    }

    .chain-top {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px 10px;
    }

    .chain-file {
        color: var(--text-secondary);
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 12px;
        overflow-wrap: anywhere;
    }

    .chain-layer {
        margin-left: auto;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 10.5px;
        font-weight: 600;
        letter-spacing: 0.5px;
        line-height: 1.6;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .layer-http {
        background: #e0f2fe;
        color: #075985;
    }

    .layer-validation {
        background: #fef3c7;
        color: #92400e;
    }

    .layer-engine {
        background: #dcfce7;
        color: #166534;
    }

    .layer-data {
        background: #ede9fe;
        color: #5b21b6;
    }

    .layer-response {
        background: #f1f5f9;
        color: #334155;
    }

    .chain-symbol {
        display: block;
        margin-top: 3px;
        color: var(--primary-dark);
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 13.5px;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .doc .chain-role {
        margin: 6px 0 0;
        color: var(--text-body);
        font-size: 14px;
        line-height: 1.7;
    }

    /* ── Section 2: annotations, steps, guards ────────────────── */
    .notes {
        list-style: none;
        display: grid;
        gap: 10px;
        margin: 4px 0 8px;
    }

    .notes > li {
        display: grid;
        grid-template-columns: 26px minmax(0, 1fr);
        align-items: start;
        gap: 12px;
        padding: 12px 14px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        font-size: 14px;
        line-height: 1.75;
    }

    .marker {
        width: 24px;
        height: 24px;
        margin-top: 2px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: var(--attention);
        color: #1f1300;
        font-size: 12px;
        font-weight: 700;
    }

    .note-lines {
        display: block;
        color: var(--text-muted);
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 11.5px;
    }

    .step-badge {
        display: inline-block;
        margin-right: 8px;
        padding: 2px 10px;
        border-radius: 999px;
        background: #0f1a14;
        color: #d6f2df;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 12px;
        font-weight: 600;
        vertical-align: 2px;
    }

    .doc h4.sub-step {
        margin: 28px 0 8px;
        font-size: 16px;
        scroll-margin-top: var(--anchor-offset);
    }

    .guard {
        margin: 12px 0 24px;
        padding: 14px 16px;
        background: #fffbf3;
        border: 1px solid rgba(217, 119, 6, 0.28);
        border-left: 4px solid var(--attention);
        border-radius: 12px;
        font-size: 14px;
        line-height: 1.75;
    }

    .guard-label {
        display: block;
        margin-bottom: 2px;
        color: var(--attention-dark);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.6px;
        text-transform: uppercase;
    }

    .guard code {
        background: rgba(217, 119, 6, 0.10);
        color: #7c2d12;
    }

    /* Code keeps its real case inside the uppercase label */
    .guard-label code {
        letter-spacing: normal;
        text-transform: none;
    }

    .doc .guard p {
        margin: 0;
    }

    .recap {
        color: var(--text-secondary);
        font-size: 14px;
    }

    /* ── Section 3: worked examples ───────────────────────────── */
    .example {
        margin: 24px 0 34px;
        padding: 22px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
    }

    .doc .example h3 {
        margin: 0 0 8px;
    }

    .example-refs {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 4px;
    }

    .test-ref {
        display: inline-block;
        max-width: 100%;
        padding: 3px 10px;
        border-radius: 999px;
        background: var(--primary-soft);
        border: 1px solid rgba(22, 163, 74, 0.2);
        color: var(--primary-dark);
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 11.5px;
        line-height: 1.6;
        overflow-wrap: anywhere;
    }

    .doc .io-label {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 24px 0 8px;
        color: var(--text-primary);
        font-size: 13.5px;
        font-weight: 600;
    }

    .io-label b {
        flex: none;
        width: 24px;
        height: 24px;
        border-radius: 8px;
        display: grid;
        place-items: center;
        background: #0f1a14;
        color: #d6f2df;
        font-size: 12px;
    }

    .trace {
        margin: 0 0 8px;
        padding-left: 22px;
    }

    .trace > li {
        margin-bottom: 8px;
        padding-left: 4px;
        font-size: 14.5px;
        line-height: 1.75;
    }

    .trace > li::marker {
        color: var(--primary);
        font-weight: 700;
    }

    .example-part {
        margin-top: 28px;
        padding-top: 20px;
        border-top: 1px dashed var(--border-strong);
    }

    .doc .example-part-title {
        margin-bottom: 4px;
        color: var(--text-muted);
        font-size: 11.5px;
        font-weight: 700;
        letter-spacing: 0.6px;
        text-transform: uppercase;
    }

    .data-table code {
        font-size: 0.82em;
    }

    .num {
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    /* ── Responsive ───────────────────────────────────────────── */
    @media (max-width: 480px) {
        .code-gutter,
        .code-pre {
            font-size: 12px;
        }

        .code-gutter {
            padding-left: 10px;
            padding-right: 8px;
        }

        .code-pre {
            padding-left: 12px;
            padding-right: 12px;
        }

        .example {
            padding: 16px;
        }

        .chain > li {
            grid-template-columns: 28px minmax(0, 1fr);
            gap: 10px;
        }

        .chain > li::before {
            left: 13px;
        }

        .chain-num {
            width: 28px;
            height: 28px;
            border-radius: 8px;
        }
    }

    @media print {
        .code {
            break-inside: auto;
            box-shadow: none;
        }

        .code-gutter {
            display: none;
        }

        .code-pre code {
            white-space: pre-wrap;
        }

        .example,
        .chain-card {
            break-inside: avoid;
            box-shadow: none;
        }
    }
</style>
