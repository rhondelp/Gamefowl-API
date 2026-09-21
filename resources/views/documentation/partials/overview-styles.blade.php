{{-- Styles for the overview documentation page only (show.blade.php): the
     knowledge tree, formula cards, flowchart, phone mockups, and the other
     section components. Shared styles live in base-styles.blade.php. --}}
<style>
    /* ── Section 1 ────────────────────────────────────────────── */
    .glance {
        display: grid;
        gap: 12px;
        margin-top: 26px;
    }

    .glance-item {
        padding: 18px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 16px;
        box-shadow: var(--shadow);
    }

    .icon-tile {
        width: 38px;
        height: 38px;
        margin-bottom: 10px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        background: var(--primary-soft);
        font-size: 18px;
    }

    .doc .glance-item h3 {
        margin: 0 0 4px;
        font-size: 14.5px;
    }

    .doc .glance-item p {
        margin: 0;
        color: var(--text-secondary);
        font-size: 13.5px;
        line-height: 1.7;
    }

    /* ── Section 2 ────────────────────────────────────────────── */
    .concepts {
        display: grid;
        gap: 14px;
        margin: 22px 0 8px;
    }

    .concept {
        display: flex;
        flex-direction: column;
        padding: 20px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 16px;
        box-shadow: var(--shadow);
    }

    .doc .concept h3 {
        margin: 0 0 6px;
        font-size: 16px;
    }

    .doc .concept p {
        margin-bottom: 12px;
        color: var(--text-secondary);
        font-size: 13.5px;
        line-height: 1.75;
    }

    .doc .concept .concept-example {
        margin: auto 0 0;
        padding-top: 12px;
        border-top: 1px dashed var(--border-strong);
        color: var(--text-primary);
        font-size: 12.5px;
        line-height: 1.6;
    }

    .concept-example span {
        display: block;
        color: var(--text-muted);
        font-size: 10.5px;
        font-weight: 600;
        letter-spacing: 0.6px;
        text-transform: uppercase;
    }

    .disease-list {
        list-style: none;
        display: grid;
        gap: 10px;
        margin: 16px 0 8px;
    }

    .disease-list li {
        padding: 14px 16px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 14px;
        font-size: 14px;
        line-height: 1.7;
    }

    .disease-list .disease-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 4px 10px;
        margin-bottom: 2px;
        color: var(--text-primary);
        font-weight: 600;
        font-size: 14.5px;
    }

    .doc .disease-list p {
        margin: 0;
        color: var(--text-secondary);
    }

    .scale {
        display: grid;
        gap: 10px;
        margin: 18px 0 8px;
    }

    .scale-row {
        display: grid;
        grid-template-columns: 62px 1fr;
        align-items: center;
        gap: 14px;
        padding: 12px 16px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 14px;
    }

    .scale-badges {
        display: flex;
        gap: 5px;
    }

    .doc .scale-row p {
        margin: 0;
        font-size: 14px;
        line-height: 1.65;
    }

    .data-table .col-weight {
        width: 150px;
    }

    .symptom-cat {
        display: block;
        color: var(--text-muted);
        font-size: 12px;
        line-height: 1.5;
        text-transform: capitalize;
    }

    .weight-cell {
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }

    .meter {
        display: inline-flex;
        gap: 3px;
    }

    .meter i {
        width: 9px;
        height: 9px;
        border-radius: 3px;
        background: #e3e9e5;
    }

    .meter.m1 i:nth-child(-n+1),
    .meter.m2 i:nth-child(-n+2),
    .meter.m3 i:nth-child(-n+3),
    .meter.m4 i:nth-child(-n+4),
    .meter.m5 i:nth-child(-n+5) {
        background: var(--primary);
    }

    /* Knowledge tree. Mobile-first: a vertical, indented tree; from
       760px it turns into a left-to-right tree. Connector lines are
       drawn per list item (::before = rail piece, ::after = tick), so
       they stay attached however tall each item grows. */
    .tree {
        --line: #c3d0c7;
        --line-soft: #d6e0d9;
    }

    .tree ul {
        list-style: none;
    }

    .tree-root {
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        border-radius: 16px;
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        color: var(--surface);
        box-shadow: 0 10px 22px rgba(22, 163, 74, 0.2);
    }

    .tree-root-icon {
        font-size: 22px;
        line-height: 1;
    }

    .tree-root strong {
        display: block;
        color: var(--surface);
        font-size: 15px;
        line-height: 1.3;
    }

    .tree-root small {
        display: block;
        font-size: 12px;
        line-height: 1.4;
        opacity: 0.92;
    }

    .tree-branches {
        position: relative;
        margin-left: 24px;
        padding-top: 16px;
    }

    .tree-branches::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 16px;
        border-left: 2px solid var(--line);
    }

    .tree-branch {
        position: relative;
        padding: 0 0 22px 24px;
    }

    .tree-branch:last-child {
        padding-bottom: 0;
    }

    .tree-branch::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        border-left: 2px solid var(--line);
    }

    .tree-branch:last-child::before {
        bottom: auto;
        height: 30px;
    }

    .tree-branch::after {
        content: '';
        position: absolute;
        left: 0;
        top: 29px;
        width: 24px;
        border-top: 2px solid var(--line);
    }

    .tree-disease {
        position: relative;
        min-height: 60px;
        padding: 9px 13px;
        background: var(--surface);
        border: 1px solid var(--border-strong);
        border-radius: 13px;
        box-shadow: var(--shadow);
    }

    .tree-disease strong {
        display: block;
        font-size: 14px;
        line-height: 1.35;
    }

    .tree-disease-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
        margin-top: 4px;
        color: var(--text-muted);
        font-size: 11.5px;
        line-height: 1.3;
    }

    .tree-leaves {
        position: relative;
        margin: 10px 0 0 18px;
    }

    .tree-leaf {
        position: relative;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 0 4px 20px;
        color: var(--text-primary);
        font-size: 13.5px;
        line-height: 1.4;
        transition: opacity 0.15s ease;
    }

    .tree-leaf::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        border-left: 2px solid var(--line-soft);
    }

    .tree-leaf:first-child::before {
        top: -10px;
    }

    .tree-leaf:last-child::before {
        bottom: 50%;
    }

    .tree-leaf::after {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        width: 14px;
        border-top: 2px solid var(--line-soft);
    }

    .tree-leaf-name {
        margin-left: -4px;
        padding: 1px 4px;
        border-radius: 6px;
        transition: background-color 0.15s ease;
    }

    .tree-shared {
        flex: none;
        width: 15px;
        height: 15px;
        color: var(--text-muted);
    }

    /* Inside a leaf name, glued to the last word so it never wraps alone */
    .tree-tail {
        white-space: nowrap;
    }

    .tree-leaf-name .tree-shared {
        display: inline-block;
        margin-left: 4px;
        vertical-align: -2px;
    }

    .tree.is-tracing .tree-leaf {
        opacity: 0.3;
    }

    .tree.is-tracing .tree-leaf.is-linked {
        opacity: 1;
    }

    .tree-leaf.is-linked .tree-leaf-name {
        background: #fef3c7;
    }

    .legend {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px 18px;
        margin-bottom: 8px;
    }

    .legend-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--text-secondary);
        font-size: 12.5px;
    }

    .legend-item .tree-shared {
        color: var(--text-secondary);
    }

    .overlap {
        list-style: none;
        display: grid;
        gap: 10px;
    }

    .overlap li {
        display: grid;
        gap: 6px;
        padding-bottom: 10px;
        border-bottom: 1px dashed var(--border);
    }

    .overlap li:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .overlap-symptom {
        color: var(--text-primary);
        font-size: 14px;
        font-weight: 600;
        line-height: 1.5;
    }

    .overlap-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .chip {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 3px 10px 3px 3px;
        background: var(--surface-soft);
        border: 1px solid var(--border);
        border-radius: 999px;
        color: var(--text-body);
        font-size: 12.5px;
        line-height: 1.5;
    }

    .chip .w {
        min-width: 20px;
        height: 20px;
        border-radius: 999px;
        font-size: 11px;
    }

    /* ── Section 3 ────────────────────────────────────────────── */
    .formula-pair {
        display: grid;
        gap: 14px;
        margin: 24px 0 8px;
    }

    .formula-card {
        display: flex;
        flex-direction: column;
        padding: 20px 22px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 16px;
        box-shadow: var(--shadow);
    }

    .formula-card.is-precise {
        background: #f3faf5;
        border-color: rgba(22, 163, 74, 0.25);
    }

    .doc .formula-label {
        margin-bottom: 10px;
        color: var(--text-muted);
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.7px;
        text-transform: uppercase;
    }

    .doc .formula-plain {
        margin: 0;
        color: var(--text-primary);
        font-size: 17px;
        font-weight: 500;
        line-height: 1.7;
    }

    .formula-math {
        display: grid;
        gap: 10px;
        color: var(--text-primary);
        font-size: 15px;
        line-height: 1.4;
    }

    .fm-lhs {
        font-weight: 600;
    }

    /* One row that never wraps: only the fraction's text wraps. */
    .fm-row {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .fm-op {
        flex: none;
        color: var(--text-secondary);
        font-weight: 500;
        white-space: nowrap;
    }

    .frac {
        flex: 1 1 auto;
        min-width: 0;
        display: flex;
        flex-direction: column;
        text-align: center;
    }

    .frac > span {
        padding: 0 6px;
        font-size: 13px;
        line-height: 1.45;
    }

    .frac > span:first-child {
        padding-bottom: 6px;
        border-bottom: 2px solid var(--text-primary);
    }

    .frac > span:last-child {
        padding-top: 6px;
    }

    .doc .formula-foot {
        margin: 14px 0 0;
        color: var(--text-secondary);
        font-size: 12.5px;
        line-height: 1.65;
    }

    .wbar {
        display: flex;
        gap: 3px;
        height: 46px;
    }

    .wbar-seg {
        min-width: 0;
        display: grid;
        place-items: center;
        border: 1px dashed #c9d4cd;
        border-radius: 8px;
        background: repeating-linear-gradient(135deg, #f1f4f2 0 6px, #e7ece9 6px 12px);
        color: var(--text-muted);
        font-size: 13px;
        font-weight: 700;
    }

    .wbar-seg.is-hit {
        border: 0;
        background: var(--primary);
        color: var(--surface);
    }

    .wbar-scale {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-top: 8px;
        color: var(--text-secondary);
        font-size: 12.5px;
        line-height: 1.5;
    }

    .wbar-scale strong {
        color: var(--primary-dark);
    }

    .wbar-legend {
        display: grid;
        gap: 16px;
        margin-top: 18px;
    }

    .wbar-legend h4 {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 6px;
        font-size: 13px;
    }

    .wbar-legend ul {
        list-style: none;
        display: grid;
        gap: 5px;
        font-size: 13.5px;
        line-height: 1.5;
    }

    .wbar-legend li {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .wbar-legend .is-miss {
        color: var(--text-secondary);
    }

    .mark-yes,
    .mark-no {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: inline-grid;
        place-items: center;
        flex: none;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
    }

    .mark-yes {
        background: var(--primary);
        color: var(--surface);
    }

    .mark-no {
        background: #e3e9e5;
        color: var(--text-secondary);
    }

    .calc {
        list-style: none;
        display: grid;
        gap: 10px;
        margin: 20px 0 8px;
    }

    .calc li {
        display: grid;
        grid-template-columns: 32px 1fr;
        align-items: start;
        gap: 14px;
        padding: 14px 16px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 14px;
    }

    .calc-num {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: var(--primary-soft-2);
        color: var(--primary-dark);
        font-size: 14px;
        font-weight: 700;
    }

    .doc .calc h4 {
        margin: 3px 0 2px;
    }

    .doc .calc p {
        margin: 0;
        color: var(--text-secondary);
        font-size: 14px;
        line-height: 1.7;
    }

    .doc .calc .calc-eq {
        margin-top: 4px;
        color: var(--text-primary);
        font-size: 17px;
        font-weight: 500;
        font-variant-numeric: tabular-nums;
    }

    .calc-eq b {
        color: var(--primary-dark);
        font-weight: 700;
    }

    .calc li.is-result {
        align-items: center;
        border: 0;
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        color: var(--surface);
        box-shadow: 0 10px 24px rgba(22, 163, 74, 0.22);
    }

    .calc li.is-result .calc-num {
        background: rgba(255, 255, 255, 0.22);
        color: var(--surface);
    }

    .doc .calc li.is-result p {
        color: var(--surface);
        font-size: 16px;
    }

    .calc li.is-result strong {
        color: var(--surface);
        font-size: 20px;
    }

    .scores-key {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px 16px;
        margin: 18px 0 10px;
        color: var(--text-secondary);
        font-size: 12.5px;
    }

    .scores-key span {
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }

    .key-cut {
        width: 0;
        height: 14px;
        border-left: 2px dashed var(--attention);
    }

    .key-fill {
        width: 22px;
        height: 8px;
        border-radius: 999px;
        background: var(--primary);
    }

    .scores {
        list-style: none;
        display: grid;
        gap: 10px;
        margin-bottom: 18px;
    }

    .score {
        padding: 14px 16px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 14px;
    }

    .score.is-hidden {
        background: var(--surface-soft);
    }

    .score.is-skipped {
        background: transparent;
        border-style: dashed;
    }

    .score-top {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px 10px;
    }

    .score-name {
        color: var(--text-primary);
        font-size: 14.5px;
        font-weight: 600;
    }

    .score-tag {
        padding: 2px 9px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.2px;
        line-height: 1.6;
    }

    .tag-shown {
        background: var(--primary-soft-2);
        color: var(--primary-dark);
    }

    .tag-hidden {
        background: #eef1ef;
        color: var(--text-secondary);
    }

    .score-pct {
        margin-left: auto;
        color: var(--text-primary);
        font-size: 17px;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }

    .score.is-hidden .score-pct,
    .score.is-skipped .score-pct {
        color: var(--text-muted);
    }

    .score-track {
        position: relative;
        height: 8px;
        margin: 10px 0 8px;
        border-radius: 999px;
        background: #e9eeea;
    }

    .score-fill {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        border-radius: inherit;
        background: var(--primary);
    }

    .score.is-hidden .score-fill {
        background: #a9b6ad;
    }

    /* 20% cut-off marker, at the same spot on every bar */
    .score-track::after {
        content: '';
        position: absolute;
        left: 20%;
        top: -5px;
        bottom: -5px;
        border-left: 2px dashed var(--attention);
    }

    .doc .score .score-math {
        margin: 0;
        color: var(--text-secondary);
        font-size: 13px;
        line-height: 1.95;
    }

    .score-math .w {
        min-width: 20px;
        height: 20px;
        margin: 0 1px;
        font-size: 11px;
        vertical-align: -1px;
    }

    .compare {
        display: grid;
        gap: 12px;
        margin: 16px 0;
    }

    .compare-item {
        padding: 16px 18px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 14px;
    }

    .doc .compare-item p {
        margin: 0;
        font-size: 14px;
        line-height: 1.9;
    }

    .compare-item .compare-pct {
        display: block;
        color: var(--text-primary);
        font-size: 22px;
        font-weight: 700;
        line-height: 1.3;
    }

    .compare-item.is-strong {
        border-color: rgba(22, 163, 74, 0.35);
        background: #f3faf5;
    }

    /* ── Section 4: flowchart ─────────────────────────────────── */
    /* Mobile-first: one column. From 640px, two swimlanes (owner /
       server) with the handoffs drawn as horizontal arrows. */
    .flow {
        --flow-line: #9fb1a5;
        --gap: 30px;
        display: flex;
        flex-direction: column;
    }

    .flow-lane {
        display: none;
    }

    .flow-node {
        position: relative;
        display: flex;
        align-items: center;
        gap: 11px;
        min-height: 52px;
        margin-bottom: var(--gap);
        padding: 10px 14px;
        border: 1px solid rgba(22, 163, 74, 0.35);
        border-radius: 14px;
        background: #f0fdf4;
        color: var(--text-primary);
        font-size: 14px;
        font-weight: 500;
        line-height: 1.35;
    }

    .flow-node.server {
        border-color: var(--server-border);
        background: var(--server-soft);
    }

    .flow-node.is-before-handoff,
    .flow-node.is-last {
        margin-bottom: 0;
    }

    .flow-num {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        flex: none;
        background: var(--primary);
        color: var(--surface);
        font-size: 12px;
        font-weight: 700;
    }

    .flow-node.server .flow-num {
        background: var(--server);
    }

    .flow-tag {
        margin-left: auto;
        padding: 2px 8px;
        border-radius: 999px;
        background: rgba(22, 163, 74, 0.12);
        color: var(--primary-dark);
        font-size: 10px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .flow-node.server .flow-tag {
        background: rgba(71, 85, 105, 0.12);
        color: var(--server);
    }

    /* Arrow to the next step in the same lane */
    .flow-node.has-down::after {
        content: '';
        position: absolute;
        left: 50%;
        top: 100%;
        height: calc(var(--gap) - 6px);
        border-left: 2px solid var(--flow-line);
        transform: translateX(-1px);
    }

    .flow-node.has-down::before {
        content: '';
        position: absolute;
        left: 50%;
        top: calc(100% + var(--gap) - 9px);
        border-left: 6px solid transparent;
        border-right: 6px solid transparent;
        border-top: 8px solid var(--flow-line);
        transform: translateX(-50%);
    }

    /* Handoff between phone and server (drawn dashed: over the internet) */
    .flow-handoff {
        position: relative;
        height: 62px;
    }

    .flow-handoff::before {
        content: '';
        position: absolute;
        left: 50%;
        top: 6px;
        bottom: 8px;
        border-left: 2px dashed var(--flow-line);
        transform: translateX(-1px);
    }

    .flow-handoff::after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: 1px;
        border-left: 6px solid transparent;
        border-right: 6px solid transparent;
        border-top: 8px solid var(--flow-line);
        transform: translateX(-50%);
    }

    .flow-handoff-label {
        position: absolute;
        left: calc(50% + 14px);
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
        font-size: 12px;
        font-style: italic;
        line-height: 1.3;
    }

    .steps {
        list-style: none;
        margin-top: 30px;
    }

    .step {
        position: relative;
        display: grid;
        grid-template-columns: 36px 1fr;
        gap: 16px;
        padding-bottom: 26px;
    }

    .step::before {
        content: '';
        position: absolute;
        left: 17px;
        top: 42px;
        bottom: 6px;
        border-left: 2px solid var(--border-strong);
    }

    .step:last-child {
        padding-bottom: 0;
    }

    .step:last-child::before {
        display: none;
    }

    .step-num {
        position: relative;
        z-index: 1;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: var(--primary);
        color: var(--surface);
        font-size: 14px;
        font-weight: 700;
    }

    .step.server .step-num {
        background: var(--server);
    }

    .doc .step h3 {
        margin: 3px 0 0;
        font-size: 16.5px;
    }

    .step-where {
        display: block;
        margin-bottom: 6px;
        color: var(--primary-dark);
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .step.server .step-where {
        color: var(--server);
    }

    .doc .step p {
        margin-bottom: 0;
        font-size: 15px;
    }

    /* ── Section 5 ────────────────────────────────────────────── */
    .limits {
        list-style: none;
        display: grid;
        gap: 12px;
        margin: 20px 0 8px;
    }

    .limits li {
        display: grid;
        grid-template-columns: 38px 1fr;
        gap: 12px;
        padding: 16px 18px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 14px;
        font-size: 14px;
        line-height: 1.7;
    }

    .limits .icon-tile {
        margin: 0;
    }

    .limits strong {
        display: block;
        margin-bottom: 2px;
    }

    .quote {
        position: relative;
        margin: 16px 0 12px;
        padding: 20px 22px 20px 58px;
        background: #fffbf3;
        border: 1px solid rgba(217, 119, 6, 0.25);
        border-radius: 16px;
        color: var(--text-primary);
        font-size: 15.5px;
        line-height: 1.8;
    }

    .quote::before {
        content: '⚠';
        position: absolute;
        left: 20px;
        top: 18px;
        color: var(--attention);
        font-size: 22px;
        line-height: 1.4;
    }

    .quote cite {
        display: block;
        margin-top: 10px;
        color: var(--attention-dark);
        font-size: 12px;
        font-style: normal;
        font-weight: 600;
        letter-spacing: 0.3px;
    }

    /* ── Section 6 ────────────────────────────────────────────── */
    .story {
        list-style: none;
        display: grid;
        gap: 16px;
        margin: 24px 0;
    }

    .story-step {
        padding: 22px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
    }

    .story-step.has-phone {
        display: grid;
        align-items: center;
        gap: 24px;
    }

    .story-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
        color: var(--primary-dark);
        font-size: 11.5px;
        font-weight: 600;
        letter-spacing: 0.6px;
        text-transform: uppercase;
    }

    .story-label b {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: var(--primary);
        color: var(--surface);
        font-size: 11px;
    }

    .story-label.server {
        color: var(--server);
    }

    .story-label.server b {
        background: var(--server);
    }

    .doc .story-step h3 {
        margin: 0 0 8px;
        font-size: 17px;
    }

    .doc .story-step p {
        font-size: 14.5px;
    }

    .doc .story-step p:last-child,
    .doc .story-step .scores:last-child {
        margin-bottom: 0;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 4px 12px;
        border-radius: 999px;
        background: rgba(217, 119, 6, 0.10);
        border: 1px solid rgba(217, 119, 6, 0.25);
        color: var(--attention-dark);
        font-size: 13px;
        font-weight: 600;
        white-space: nowrap;
    }

    .status-pill::before {
        content: '';
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--attention);
    }

    /* Phone mockups (illustrative, not screenshots) */
    .phone {
        width: 272px;
        max-width: 100%;
        margin: 0 auto;
        padding: 9px;
        border-radius: 40px;
        background: #1b2420;
        box-shadow: 0 24px 48px rgba(23, 33, 27, 0.20), inset 0 0 0 2px #2d3933;
    }

    .phone-screen {
        height: 540px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-radius: 32px;
        background: #f4f8f5;
        color: var(--text-primary);
        font-size: 11px;
        line-height: 1.45;
    }

    .ph-status {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 9px 22px 5px;
        font-size: 10.5px;
        font-weight: 600;
    }

    .ph-signal {
        display: inline-flex;
        gap: 2px;
        align-items: flex-end;
    }

    .ph-signal i {
        width: 3px;
        border-radius: 1px;
        background: var(--text-primary);
    }

    .ph-bar {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px 10px;
        background: var(--surface);
        border-bottom: 1px solid var(--border);
        font-size: 12.5px;
        font-weight: 600;
    }

    .ph-back {
        color: var(--primary-dark);
        font-size: 20px;
        line-height: 0.8;
    }

    .ph-body {
        flex: 1;
        overflow: hidden;
        padding: 10px 12px;
    }

    .ph-hint {
        margin-bottom: 4px;
        color: var(--text-secondary);
    }

    .ph-cat {
        margin: 9px 0 4px;
        color: var(--text-muted);
        font-size: 9.5px;
        font-weight: 700;
        letter-spacing: 0.6px;
        text-transform: uppercase;
    }

    .ph-check {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 4px;
        padding: 6px 8px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 9px;
    }

    .ph-check::before {
        content: '';
        flex: none;
        width: 13px;
        height: 13px;
        border: 1.5px solid #b9c5bd;
        border-radius: 4px;
    }

    .ph-check.is-on {
        background: #f0fdf4;
        border-color: rgba(22, 163, 74, 0.45);
        font-weight: 500;
    }

    .ph-check.is-on::before {
        border-color: var(--primary);
        background: var(--primary) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12'%3E%3Cpath d='M2.5 6.5l2.2 2.2 4.8-5' fill='none' stroke='white' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center / 10px no-repeat;
    }

    .ph-cta {
        margin: 6px 12px 14px;
        padding: 10px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        color: var(--surface);
        font-size: 12px;
        font-weight: 600;
        text-align: center;
    }

    .ph-title {
        margin-bottom: 7px;
        font-size: 12.5px;
        font-weight: 600;
    }

    .ph-result {
        margin-bottom: 7px;
        padding: 9px 10px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
    }

    .ph-result-top {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .ph-rank {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        flex: none;
        background: var(--primary-soft-2);
        color: var(--primary-dark);
        font-size: 9.5px;
        font-weight: 700;
    }

    .ph-name {
        font-size: 12px;
        font-weight: 600;
    }

    .ph-result .sev {
        margin-left: auto;
        font-size: 9px;
        padding: 1px 7px;
    }

    .ph-score {
        display: flex;
        align-items: center;
        gap: 7px;
        margin: 6px 0 4px;
        color: var(--text-secondary);
        font-size: 10px;
    }

    .ph-track {
        flex: 1;
        height: 5px;
        overflow: hidden;
        border-radius: 999px;
        background: #e9eeea;
    }

    .ph-track span {
        display: block;
        height: 100%;
        background: var(--primary);
    }

    .ph-score b {
        color: var(--text-primary);
        font-size: 11.5px;
    }

    .ph-line {
        margin-top: 3px;
        color: var(--text-secondary);
        font-size: 10px;
        line-height: 1.45;
    }

    .ph-line b {
        color: var(--text-primary);
        font-weight: 600;
    }

    .ph-more {
        color: var(--primary-dark);
        font-weight: 600;
        white-space: nowrap;
    }

    .ph-warn {
        margin-top: 5px;
        padding: 6px 7px;
        border-radius: 8px;
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        font-size: 9.5px;
        line-height: 1.45;
    }

    .ph-disclaimer {
        padding: 7px 8px;
        border-radius: 9px;
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
        font-size: 9.5px;
        line-height: 1.45;
    }

    .mockup-note {
        margin-top: 10px;
        color: var(--text-muted);
        font-size: 11.5px;
        line-height: 1.5;
        text-align: center;
    }

    /* ── Section 7 ────────────────────────────────────────────── */
    .faq {
        display: grid;
        gap: 12px;
        margin-top: 20px;
    }

    .faq-item {
        padding: 18px 20px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 16px;
        box-shadow: var(--shadow);
    }

    .doc .faq-item h3 {
        display: flex;
        gap: 10px;
        margin: 0 0 6px;
        font-size: 16px;
        line-height: 1.5;
    }

    .faq-q {
        flex: none;
        width: 24px;
        height: 24px;
        margin-top: 0;
        border-radius: 8px;
        display: grid;
        place-items: center;
        background: var(--primary-soft-2);
        color: var(--primary-dark);
        font-size: 12px;
        font-weight: 700;
    }

    .doc .faq-item p {
        margin: 0;
        padding-left: 34px;
        font-size: 14.5px;
    }

    /* ── Responsive up-scales ─────────────────────────────────── */
    @media (hover: none) {
        .hover-hint {
            display: none;
        }
    }

    @media (max-width: 480px) {
        .story-step {
            padding: 16px;
        }

        .data-table .col-weight {
            width: 118px;
        }

        .meter {
            display: none;
        }

        .quote {
            padding: 18px 18px 18px 50px;
        }

        .quote::before {
            left: 16px;
        }

        .doc .faq-item p {
            padding-left: 0;
        }
    }

    @media (min-width: 640px) {
        .glance {
            grid-template-columns: repeat(3, 1fr);
        }

        .wbar-legend,
        .limits,
        .compare {
            grid-template-columns: 1fr 1fr;
        }

        /* Two-lane flowchart */
        .flow {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 108px minmax(0, 1fr);
            row-gap: var(--gap);
        }

        .flow-lane {
            display: block;
            padding-bottom: 8px;
            border-bottom: 2px solid rgba(22, 163, 74, 0.35);
            color: var(--primary-dark);
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .flow-lane.server {
            grid-column: 3;
            border-bottom-color: var(--server-border);
            color: var(--server);
        }

        .flow-node {
            margin-bottom: 0;
        }

        .flow-tag {
            display: none;
        }

        .n1 { grid-area: 2 / 1; }
        .n2 { grid-area: 3 / 1; }
        .n3 { grid-area: 4 / 1; }
        .n4 { grid-area: 5 / 1; }
        .h1 { grid-area: 5 / 2; }
        .n5 { grid-area: 5 / 3; }
        .n6 { grid-area: 6 / 3; }
        .n7 { grid-area: 7 / 3; }
        .h2 { grid-area: 7 / 2; }
        .n8 { grid-area: 7 / 1; }

        .flow-handoff {
            height: auto;
        }

        .flow-handoff::before {
            left: 4px;
            right: 4px;
            top: 50%;
            bottom: auto;
            border-left: 0;
            border-top: 2px dashed var(--flow-line);
            transform: translateY(-1px);
        }

        .flow-handoff::after {
            top: 50%;
            bottom: auto;
            border-top: 6px solid transparent;
            border-bottom: 6px solid transparent;
            transform: translateY(-50%);
        }

        .flow-handoff.to-server::after {
            left: auto;
            right: 0;
            border-left: 8px solid var(--flow-line);
            border-right: 0;
        }

        .flow-handoff.to-app::after {
            left: 0;
            border-right: 8px solid var(--flow-line);
            border-left: 0;
        }

        .flow-handoff-label {
            left: 0;
            right: 0;
            top: auto;
            bottom: calc(50% + 7px);
            transform: none;
            font-size: 11px;
            text-align: center;
        }
    }

    @media (min-width: 760px) {
        .concepts {
            grid-template-columns: repeat(3, 1fr);
        }

        .formula-pair {
            grid-template-columns: 1fr 1.45fr;
        }

        .story-step.has-phone {
            grid-template-columns: minmax(0, 1fr) 272px;
        }

        /* Left-to-right knowledge tree */
        .tree {
            display: flex;
            align-items: center;
        }

        .tree-root {
            flex: none;
            flex-direction: column;
            gap: 8px;
            width: 124px;
            padding: 18px 12px;
            text-align: center;
        }

        .tree-root::after {
            content: '';
            position: absolute;
            left: 100%;
            top: 50%;
            width: 22px;
            border-top: 2px solid var(--line);
        }

        .tree-branches {
            flex: 1;
            min-width: 0;
            margin-left: 22px;
            padding-top: 0;
        }

        .tree-branches::before {
            display: none;
        }

        .tree-branch,
        .tree-branch:last-child {
            display: flex;
            align-items: center;
            padding: 8px 0 8px 22px;
        }

        .tree-branch::before,
        .tree-branch:last-child::before {
            top: 0;
            bottom: 0;
            height: auto;
        }

        .tree-branch:first-child::before {
            top: 50%;
        }

        .tree-branch:last-child::before {
            bottom: 50%;
        }

        .tree-branch::after {
            top: 50%;
            width: 22px;
        }

        .tree-disease {
            flex: none;
            width: 170px;
        }

        .tree-disease::after {
            content: '';
            position: absolute;
            left: 100%;
            top: 50%;
            width: 20px;
            border-top: 2px solid var(--line-soft);
        }

        .tree-leaves {
            flex: 1;
            min-width: 0;
            margin: 0 0 0 20px;
        }

        .tree-leaf:first-child::before {
            top: 50%;
        }
    }

    @media print {
        .hover-hint {
            display: none !important;
        }

        .concept,
        .story-step,
        .faq-item,
        .calc li,
        .score,
        .phone,
        .flow {
            break-inside: avoid;
            box-shadow: none;
        }
    }
</style>
