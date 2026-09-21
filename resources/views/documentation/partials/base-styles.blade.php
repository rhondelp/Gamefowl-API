{{-- Styles shared by every documentation page (the overview and the technical
     walkthrough): tokens, page chrome, typography, and the generic components.
     Kept inline like the other public pages, so no asset build step is needed. --}}
<style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    :root {
        /* Brand — shared with the landing page and the mobile app */
        --primary: #16a34a;
        --primary-dark: #15803d;
        --primary-deep: #14532d;
        --primary-light: #22c55e;
        --primary-soft: rgba(22, 163, 74, 0.08);
        --primary-soft-2: rgba(22, 163, 74, 0.14);

        /* Server-side steps in the flow diagrams */
        --server: #475569;
        --server-soft: #f1f5f9;
        --server-border: #cbd5e1;

        --attention: #d97706;
        --attention-dark: #b45309;

        --text-primary: #17211b;
        --text-body: #34413a;
        --text-secondary: #66736b;
        --text-muted: #8a968f;
        --border: #e2e8e4;
        --border-strong: #d3dcd6;
        --background: #f4f8f5;
        --surface: #ffffff;
        --surface-soft: #f8fbf9;

        --radius: 18px;
        --shadow: 0 4px 14px rgba(23, 33, 27, 0.04);

        /* Anchor offset so headings clear the sticky mobile section bar */
        --anchor-offset: 76px;
    }

    html {
        scroll-behavior: smooth;
        -webkit-text-size-adjust: 100%;
    }

    body {
        font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: var(--background);
        color: var(--text-body);
        font-size: 15.5px;
        line-height: 1.8;
        -webkit-font-smoothing: antialiased;
    }

    a {
        color: var(--primary-dark);
    }

    a:hover {
        color: var(--primary);
    }

    :focus-visible {
        outline: 3px solid rgba(22, 163, 74, 0.45);
        outline-offset: 2px;
    }

    strong {
        color: var(--text-primary);
        font-weight: 600;
    }

    code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 0.86em;
        background: var(--primary-soft);
        color: var(--primary-dark);
        padding: 2px 6px;
        border-radius: 6px;
        overflow-wrap: anywhere;
    }

    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    .skip-link {
        position: absolute;
        left: 16px;
        top: -60px;
        z-index: 100;
        padding: 8px 14px;
        border-radius: 10px;
        background: var(--primary-dark);
        color: var(--surface);
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
    }

    .skip-link:focus {
        top: 12px;
        color: var(--surface);
    }

    .container {
        width: 100%;
        max-width: 1160px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* ── Top bar ──────────────────────────────────────────────── */
    .topbar {
        background: rgba(255, 255, 255, 0.75);
        border-bottom: 1px solid var(--border);
    }

    .topbar-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        min-height: 62px;
    }

    .brand {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: var(--text-primary);
        text-decoration: none;
    }

    .brand:hover {
        color: var(--text-primary);
    }

    .brand-mark {
        width: 34px;
        height: 34px;
        border-radius: 11px;
        display: grid;
        place-items: center;
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        color: var(--surface);
        font-size: 16px;
        font-weight: 700;
        box-shadow: 0 6px 14px rgba(22, 163, 74, 0.22);
    }

    .brand-name {
        font-size: 15px;
        font-weight: 700;
        letter-spacing: 1px;
    }

    .brand-tag {
        padding: 3px 9px;
        border-radius: 999px;
        background: var(--primary-soft);
        border: 1px solid rgba(22, 163, 74, 0.18);
        color: var(--primary-dark);
        font-size: 10.5px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        line-height: 1.5;
    }

    .topbar-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--text-secondary);
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        white-space: nowrap;
    }

    .topbar-link svg {
        width: 16px;
        height: 16px;
    }

    /* ── Hero ─────────────────────────────────────────────────── */
    .hero {
        padding: 52px 0 44px;
        background:
            radial-gradient(circle at 0% 0%, rgba(34, 197, 94, 0.14), transparent 55%),
            radial-gradient(circle at 100% 100%, rgba(22, 163, 74, 0.07), transparent 50%);
        border-bottom: 1px solid var(--border);
    }

    .hero-inner {
        max-width: 800px;
    }

    .eyebrow {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 999px;
        background: rgba(22, 163, 74, 0.10);
        border: 1px solid rgba(22, 163, 74, 0.18);
        color: var(--primary-dark);
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.6px;
        text-transform: uppercase;
        margin-bottom: 18px;
        line-height: 1.5;
    }

    .hero h1 {
        color: var(--text-primary);
        font-size: 28px;
        font-weight: 700;
        line-height: 1.22;
        letter-spacing: -0.8px;
        margin-bottom: 14px;
    }

    .hero-lead {
        max-width: 690px;
        color: var(--text-secondary);
        font-size: 16px;
        line-height: 1.8;
    }

    .hero-meta {
        list-style: none;
        display: flex;
        flex-wrap: wrap;
        gap: 8px 10px;
        margin-top: 24px;
    }

    .hero-meta li {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 13px;
        border-radius: 999px;
        background: var(--surface);
        border: 1px solid var(--border);
        color: var(--text-secondary);
        font-size: 12.5px;
        line-height: 1.45;
    }

    /* ── Section navigation ───────────────────────────────────── */
    /* Phones/tablets: sticky, horizontally scrolling chip bar. */
    .toc-mobile {
        position: sticky;
        top: 0;
        z-index: 20;
        background: rgba(244, 248, 245, 0.92);
        -webkit-backdrop-filter: saturate(1.4) blur(10px);
        backdrop-filter: saturate(1.4) blur(10px);
        border-bottom: 1px solid var(--border);
    }

    .toc-mobile ol {
        list-style: none;
        display: flex;
        gap: 8px;
        max-width: 1160px;
        margin: 0 auto;
        padding: 10px 20px;
        overflow-x: auto;
        scrollbar-width: none;
    }

    .toc-mobile ol::-webkit-scrollbar {
        display: none;
    }

    .toc-mobile a {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 13px 6px 7px;
        border-radius: 999px;
        background: var(--surface);
        border: 1px solid var(--border);
        color: var(--text-secondary);
        font-size: 12.5px;
        font-weight: 500;
        line-height: 1.4;
        text-decoration: none;
        white-space: nowrap;
    }

    .toc-mobile a span {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: var(--primary-soft-2);
        color: var(--primary-dark);
        font-size: 10.5px;
        font-weight: 700;
    }

    .toc-mobile a.is-active {
        background: var(--primary-dark);
        border-color: var(--primary-dark);
        color: var(--surface);
    }

    .toc-mobile a.is-active span {
        background: rgba(255, 255, 255, 0.22);
        color: var(--surface);
    }

    /* Desktop: sticky sidebar. */
    .toc {
        display: none;
    }

    .toc-title {
        margin-bottom: 12px;
        padding-left: 14px;
        color: var(--text-muted);
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.8px;
        text-transform: uppercase;
    }

    .toc-list {
        list-style: none;
        border-left: 2px solid var(--border);
    }

    .toc-list a {
        display: flex;
        align-items: baseline;
        gap: 10px;
        margin-left: -2px;
        padding: 7px 12px;
        border-left: 2px solid transparent;
        border-radius: 0 10px 10px 0;
        color: var(--text-secondary);
        font-size: 13.5px;
        line-height: 1.45;
        text-decoration: none;
        transition: color 0.15s ease, border-color 0.15s ease, background-color 0.15s ease;
    }

    .toc-list a:hover {
        color: var(--text-primary);
    }

    .toc-list a.is-active {
        border-left-color: var(--primary);
        background: linear-gradient(90deg, var(--primary-soft), transparent);
        color: var(--primary-dark);
        font-weight: 600;
    }

    .toc-num {
        min-width: 12px;
        color: var(--text-muted);
        font-size: 11.5px;
        font-weight: 700;
    }

    .toc-list a.is-active .toc-num {
        color: var(--primary);
    }

    .toc-foot {
        margin-top: 22px;
        padding: 16px 0 0 14px;
        border-top: 1px solid var(--border);
        color: var(--text-muted);
        font-size: 12px;
        line-height: 1.7;
    }

    .toc-foot a {
        display: inline-block;
        margin-bottom: 6px;
        font-weight: 600;
        text-decoration: none;
    }

    .layout {
        padding-bottom: 32px;
    }

    /* ── Document body ────────────────────────────────────────── */
    .doc {
        max-width: 760px;
        min-width: 0;
    }

    .doc-section {
        padding: 52px 0 36px;
        border-bottom: 1px solid var(--border);
        scroll-margin-top: var(--anchor-offset);
    }

    .doc-section:last-child {
        border-bottom: 0;
    }

    .doc .section-kicker {
        margin-bottom: 6px;
        color: var(--primary-dark);
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.8px;
        text-transform: uppercase;
    }

    .doc h2 {
        margin-bottom: 18px;
        color: var(--text-primary);
        font-size: 23px;
        font-weight: 600;
        line-height: 1.3;
        letter-spacing: -0.5px;
    }

    .doc h3 {
        margin: 40px 0 12px;
        color: var(--text-primary);
        font-size: 18px;
        font-weight: 600;
        line-height: 1.4;
        letter-spacing: -0.2px;
        scroll-margin-top: var(--anchor-offset);
    }

    .doc h4 {
        color: var(--text-primary);
        font-size: 15px;
        font-weight: 600;
        line-height: 1.5;
    }

    .doc p {
        margin-bottom: 16px;
    }

    .doc .lede {
        font-size: 16.5px;
        line-height: 1.9;
    }

    .prose-list {
        margin: 0 0 18px;
        padding-left: 22px;
    }

    .prose-list li {
        margin-bottom: 10px;
        padding-left: 4px;
    }

    .prose-list li::marker {
        color: var(--primary);
        font-weight: 700;
    }

    /* Figures */
    .figure {
        margin: 24px 0 28px;
        padding: 22px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
    }

    .figure figcaption {
        margin-top: 18px;
        padding-top: 14px;
        border-top: 1px solid var(--border);
        color: var(--text-secondary);
        font-size: 13px;
        line-height: 1.75;
    }

    .doc .figure-label {
        margin-bottom: 14px;
        color: var(--text-muted);
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.7px;
        text-transform: uppercase;
    }

    /* Callouts */
    .callout {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        margin: 26px 0 8px;
        padding: 16px 18px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-left: 4px solid var(--primary);
        border-radius: 14px;
        font-size: 14.5px;
        line-height: 1.75;
    }

    .callout-icon {
        flex: none;
        font-size: 18px;
        line-height: 1.5;
    }

    .doc .callout p {
        margin: 0;
    }

    .callout-title {
        display: block;
        color: var(--text-primary);
        font-weight: 600;
    }

    .callout-summary {
        background: #f3faf5;
    }

    .callout-amber {
        background: #fffbf3;
        border-left-color: var(--attention);
    }

    /* Link to the other documentation page */
    .page-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 16px;
        border-radius: 999px;
        background: var(--surface);
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        color: var(--text-primary);
        font-size: 13.5px;
        font-weight: 600;
        line-height: 1.4;
        text-decoration: none;
        transition: border-color 0.15s ease, color 0.15s ease;
    }

    .page-link:hover {
        border-color: var(--border-strong);
        color: var(--primary-dark);
    }

    /* Weight badges: darker = stronger rule */
    .w {
        display: inline-grid;
        place-items: center;
        flex: none;
        min-width: 22px;
        height: 22px;
        padding: 0 5px;
        border-radius: 7px;
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
        font-variant-numeric: tabular-nums;
    }

    .w5 {
        background: #14532d;
        color: #ffffff;
    }

    .w4 {
        background: #15803d;
        color: #ffffff;
    }

    .w3 {
        background: #22c55e;
        color: #052e16;
    }

    .w2 {
        background: #86efac;
        color: #14532d;
    }

    .w1 {
        background: #dcfce7;
        color: #14532d;
    }

    /* Severity pills */
    .sev {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 10.5px;
        font-weight: 600;
        letter-spacing: 0.4px;
        line-height: 1.5;
        text-transform: capitalize;
        white-space: nowrap;
    }

    .sev-mild {
        background: #dcfce7;
        color: #166534;
    }

    .sev-moderate {
        background: #fef3c7;
        color: #92400e;
    }

    .sev-severe {
        background: #ffedd5;
        color: #9a3412;
    }

    .sev-critical {
        background: #fee2e2;
        color: #991b1b;
    }

    .data-table {
        width: 100%;
        margin: 18px 0 12px;
        border-collapse: separate;
        border-spacing: 0;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--shadow);
        font-size: 14px;
    }

    /* Wide tables scroll sideways on phones instead of widening the page */
    .table-wrap {
        margin: 18px 0 12px;
        padding-bottom: 4px;
        overflow-x: auto;
    }

    .table-wrap .data-table {
        margin: 0;
    }

    .data-table th,
    .data-table td {
        padding: 11px 16px;
        border-bottom: 1px solid var(--border);
        text-align: left;
        vertical-align: middle;
    }

    .data-table thead th {
        background: var(--surface-soft);
        color: var(--text-muted);
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.6px;
        text-transform: uppercase;
    }

    .data-table tfoot th,
    .data-table tfoot td {
        border-bottom: 0;
        background: #f3faf5;
        color: var(--text-primary);
        font-weight: 600;
    }

    .tech {
        margin: 22px 0 0;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 14px;
    }

    .tech summary {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 18px;
        border-radius: 14px;
        color: var(--text-primary);
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        list-style: none;
    }

    .tech summary::-webkit-details-marker {
        display: none;
    }

    .tech summary::after {
        content: '+';
        margin-left: auto;
        color: var(--primary-dark);
        font-size: 20px;
        font-weight: 500;
        line-height: 1;
    }

    .tech[open] summary::after {
        content: '−';
    }

    .tech-body {
        padding: 0 18px 18px;
    }

    .doc .tech-body p {
        color: var(--text-secondary);
        font-size: 13.5px;
    }

    .tech pre {
        overflow-x: auto;
        padding: 16px;
        border-radius: 12px;
        background: #0f1a14;
        color: #d6f2df;
        font-size: 12px;
        line-height: 1.6;
    }

    .tech pre code {
        padding: 0;
        background: none;
        color: inherit;
        font-size: inherit;
        overflow-wrap: normal;
    }

    /* ── Footer ───────────────────────────────────────────────── */
    .doc-footer {
        padding: 36px 0 44px;
        background: var(--surface);
        border-top: 1px solid var(--border);
        text-align: center;
    }

    .foot-brand {
        color: var(--text-primary);
        font-size: 15px;
        font-weight: 600;
        letter-spacing: 1px;
    }

    .foot-sub {
        margin-top: 4px;
        color: var(--text-secondary);
        font-size: 12px;
    }

    .foot-links {
        margin-top: 14px;
        font-size: 12.5px;
    }

    .foot-links a {
        margin: 0 6px;
        font-weight: 500;
        text-decoration: none;
    }

    .copyright {
        margin-top: 14px;
        color: var(--text-muted);
        font-size: 11px;
    }

    /* ── Responsive up-scales ─────────────────────────────────── */
    @media (max-width: 480px) {
        body {
            font-size: 15px;
        }

        .container {
            padding: 0 16px;
        }

        .toc-mobile ol {
            padding: 10px 16px;
        }

        .brand-tag {
            display: none;
        }

        .figure {
            padding: 16px;
        }

        .data-table th,
        .data-table td {
            padding: 10px 12px;
        }
    }

    @media (min-width: 640px) {
        .hero h1 {
            font-size: 34px;
        }

        .doc h2 {
            font-size: 26px;
        }
    }

    @media (min-width: 1024px) {
        :root {
            --anchor-offset: 24px;
        }

        .toc-mobile {
            display: none;
        }

        .layout {
            display: grid;
            grid-template-columns: 228px minmax(0, 1fr);
            gap: 56px;
            align-items: start;
        }

        .toc {
            display: block;
            position: sticky;
            top: 0;
            max-height: 100vh;
            overflow-y: auto;
            padding: 40px 0 24px;
        }

        .hero h1 {
            font-size: 40px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        html {
            scroll-behavior: auto;
        }

        *,
        *::before,
        *::after {
            transition: none !important;
        }
    }

    @media print {
        .toc,
        .toc-mobile,
        .skip-link,
        .topbar-link {
            display: none !important;
        }

        body,
        .hero,
        .doc-footer {
            background: #ffffff;
        }

        .layout {
            display: block;
        }

        .doc {
            max-width: none;
        }

        .figure {
            break-inside: avoid;
            box-shadow: none;
        }

        a {
            color: inherit;
            text-decoration: none;
        }
    }
</style>
