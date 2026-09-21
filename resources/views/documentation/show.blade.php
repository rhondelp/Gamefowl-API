@extends('documentation.layout')

@php
    /*
     | Page data. The knowledge tree, the Coccidiosis table, the overlap list,
     | and the counts are drawn from the starter-knowledge-base snapshot in
     | config/documentation.php. The worked examples' numbers are written into
     | the page; DocumentationPageTest recomputes every one of them with the
     | real engine and checks the snapshot against KnowledgeBaseSeeder.
     */
    $diseases = collect($knowledgeBase['diseases']);
    $symptomCategories = collect($knowledgeBase['symptoms']);
    $ruleCount = $diseases->sum(fn (array $disease) => count($disease['rules']));

    // Symptoms listed under two or more diseases: name => [disease => weight],
    // most widely shared first, strongest weight first within each.
    $sharedSymptoms = $diseases
        ->flatMap(fn (array $disease) => collect($disease['rules'])
            ->map(fn (int $weight, string $symptom) => [
                'symptom' => $symptom,
                'disease' => $disease['name'],
                'weight' => $weight,
            ])
            ->values())
        ->groupBy('symptom')
        ->filter(fn ($rows) => $rows->count() > 1)
        ->sortByDesc(fn ($rows) => $rows->count())
        ->map(fn ($rows) => $rows->sortByDesc('weight')->pluck('weight', 'disease'));

    // Splits a name before its last word, so the tree's "shared" icon can be
    // kept on the same line as that word when the name wraps.
    $splitLastWord = function (string $name): array {
        $cut = strrpos($name, ' ');

        return $cut === false ? ['', $name] : [substr($name, 0, $cut + 1), substr($name, $cut + 1)];
    };

    // Symptoms owners can select that no rule uses yet.
    $unlinkedSymptoms = $symptomCategories->keys()
        ->diff($diseases->flatMap(fn (array $disease) => array_keys($disease['rules'])))
        ->values();

    // Section 3 worked example: Coccidiosis vs. the three reported symptoms.
    // Reported rules first so the weight bar fills from the left.
    $coccidiosis = $diseases->firstWhere('name', 'Coccidiosis');
    $section3Reported = ['Bloody droppings', 'Pale comb', 'Lethargy or depression'];
    $section3Rules = collect($coccidiosis['rules'])
        ->sortByDesc(fn (int $weight, string $symptom) => in_array($symptom, $section3Reported, true));

    // Section navigation: anchor => [phone chip label, sidebar label].
    $toc = [
        'what-it-is' => ['Overview', 'What this system is'],
        'knowledge-base' => ['Knowledge base', 'The knowledge base'],
        'engine' => ['Engine & formula', 'The diagnostic engine & formula'],
        'flow' => ['Flow', 'The end-to-end flow'],
        'possible' => ['"Possible," not a diagnosis', 'Why "possible," not a diagnosis'],
        'walkthrough' => ['Example', 'Example walkthrough'],
        'faq' => ['FAQ', 'Frequently asked questions'],
    ];
@endphp

@section('title', 'How the Expert System Works')
@section('description', 'Plain-language documentation of the GAMEFOWL expert system: the knowledge base, the scoring formula, and the health-check flow.')

@push('styles')
    @include('documentation.partials.overview-styles')
@endpush

@section('hero')
    <p class="eyebrow">System documentation</p>
    <h1>How the GAMEFOWL expert system works</h1>
    <p class="hero-lead">
        A plain-language guide to the knowledge base, the scoring formula, and what happens when an
        owner checks a bird. Written for advisers and panel members, so no programming background is
        needed.
    </p>
    <ul class="hero-meta">
        <li><span aria-hidden="true">⏱</span> About 20 minutes to read</li>
        <li>
            <span aria-hidden="true">📚</span>
            Starter knowledge base: {{ $diseases->count() }} diseases · {{ $symptomCategories->count() }}
            symptoms · {{ $ruleCount }} rules
        </li>
        <li><span aria-hidden="true">🗓</span> Updated September 2026</li>
    </ul>
@endsection

@section('toc-foot')
    <a href="{{ route('docs.technical') }}">Technical documentation →</a><br>
    <a href="{{ route('home') }}">← Back to home</a><br>
    Examples use the starter knowledge base that ships with the system.
@endsection

@section('content')
    <!-- ══ 1. What this system is ════════════════════════ -->
    <section id="what-it-is" class="doc-section">
        <p class="section-kicker">Section 1</p>
        <h2>What this system is</h2>

        <p class="lede">
            GAMEFOWL is a mobile app that helps gamefowl owners recognize possible diseases early, based on
            the symptoms they can see. When a bird seems unwell, the owner ticks the signs they observe
            (bloody droppings, a pale comb, sneezing) and the app compares them against a library of known
            poultry diseases, then lists the conditions that match best, how closely each one matches, and
            what to do next. Behind the app is a <strong>rule-based expert system</strong>: administrators
            write down the knowledge (which diseases exist, which symptoms point to each one, and how
            strongly), and the system applies that knowledge with simple, transparent arithmetic. It is
            <strong>not machine learning</strong>. There is no trained model and no photo analysis, so every
            result can be traced back to its rules and checked by hand, which is exactly what the rest of
            this page does.
        </p>

        <div class="glance">
            <div class="glance-item">
                <div class="icon-tile" aria-hidden="true">👤</div>
                <h3>Who it's for</h3>
                <p>Gamefowl owners and breeders who check on their birds daily, often far from a veterinarian.</p>
            </div>
            <div class="glance-item">
                <div class="icon-tile" aria-hidden="true">🩺</div>
                <h3>What it gives back</h3>
                <p>A short, ranked list of possible diseases, the reasoning behind each score, and practical care advice.</p>
            </div>
            <div class="glance-item">
                <div class="icon-tile" aria-hidden="true">🧮</div>
                <h3>How it decides</h3>
                <p>Weighted rules written by administrators. With the same rules, the same symptoms always give the same answer.</p>
            </div>
        </div>

        <div class="callout">
            <span class="callout-icon" aria-hidden="true">🧭</span>
            <p>
                <span class="callout-title">How to read this page</span>
                Sections 2 and 3 introduce the two main parts: the <em>knowledge</em> and the
                <em>reasoning</em>. Section 4 follows one health check from start to finish, Section 5
                explains why results are worded cautiously, and Section 6 works through a complete example
                with real numbers.
            </p>
        </div>

        <div class="callout">
            <span class="callout-icon" aria-hidden="true">💻</span>
            <p>
                <span class="callout-title">Looking for the code-level walkthrough?</span>
                The technical documentation follows the same health check through the actual code, for readers
                comfortable with PHP and Laravel. <a href="{{ route('docs.technical') }}">View Technical Documentation →</a>
            </p>
        </div>
    </section>

    <!-- ══ 2. The knowledge base ═════════════════════════ -->
    <section id="knowledge-base" class="doc-section">
        <p class="section-kicker">Section 2</p>
        <h2>The knowledge base: diseases, symptoms, and rules</h2>

        <p>
            The knowledge base is the system's expert knowledge, written down in a form the app can use.
            It is made of three kinds of entries:
        </p>

        <div class="concepts">
            <article class="concept">
                <div class="icon-tile" aria-hidden="true">🦠</div>
                <h3>Disease</h3>
                <p>
                    A condition the system can suggest. Each one has a <strong>name</strong>, a
                    <strong>severity</strong> (mild, moderate, severe, or critical), and
                    <strong>general information</strong> for owners: what it is, what to do, how to prevent
                    it, and, for the most serious ones, a warning to see a vet.
                </p>
                <p class="concept-example"><span>Example</span>Coccidiosis · severe</p>
            </article>

            <article class="concept">
                <div class="icon-tile" aria-hidden="true">👁️</div>
                <h3>Symptom</h3>
                <p>
                    Something an owner can see for themselves, without lab tests. Symptoms are grouped into
                    five categories (respiratory, physical, digestive, neurological, and behavioral) so
                    they're easy to find in the app.
                </p>
                <p class="concept-example"><span>Example</span>Bloody droppings · digestive</p>
            </article>

            <article class="concept">
                <div class="icon-tile" aria-hidden="true">🔗</div>
                <h3>Rule</h3>
                <p>
                    A link between one disease and one symptom, with a <strong>weight from 1 to 5</strong>
                    that says how strongly that symptom points to that disease.
                </p>
                <p class="concept-example"><span>Example</span>Bloody droppings → Coccidiosis, weight 5</p>
            </article>
        </div>

        <h3>The five diseases in the starter knowledge base</h3>
        <p>
            The system ships with five diseases common in gamefowl. Administrators can add more at any time;
            the examples on this page all use this starter set.
        </p>

        <ul class="disease-list">
            @foreach ($diseases as $disease)
                <li>
                    <span class="disease-head">
                        {{ $disease['name'] }}
                        <span class="sev sev-{{ $disease['severity'] }}">{{ $disease['severity'] }}</span>
                    </span>
                    <p>{{ $disease['about'] }}</p>
                </li>
            @endforeach
        </ul>

        <h3 id="weights">What the weights mean</h3>
        <p>
            A weight is the strength of a rule: how much that one symptom should count toward suspecting
            that particular disease. Every rule uses the same scale:
        </p>

        <div class="scale">
            <div class="scale-row">
                <span class="scale-badges"><span class="w w5">5</span></span>
                <p><strong>Hallmark sign.</strong> Highly indicative: one of the clearest, most characteristic signs of the disease.</p>
            </div>
            <div class="scale-row">
                <span class="scale-badges"><span class="w w4">4</span><span class="w w3">3</span></span>
                <p><strong>Strongly associated.</strong> Commonly seen with the disease, but less specific on its own.</p>
            </div>
            <div class="scale-row">
                <span class="scale-badges"><span class="w w2">2</span><span class="w w1">1</span></span>
                <p><strong>General support.</strong> Real, but common to many illnesses, so it counts for little by itself.</p>
            </div>
        </div>

        <h3>Example: the rules for Coccidiosis</h3>
        <p>
            Coccidiosis, an intestinal parasite, has seven rules. Bloody droppings is its hallmark sign, so it
            carries the top weight. The other six are real signs of the disease but less specific, so they
            carry 3 or 4.
        </p>

        <table class="data-table">
            <caption class="sr-only">Coccidiosis rules and their weights</caption>
            <thead>
                <tr>
                    <th scope="col">Symptom</th>
                    <th scope="col" class="col-weight">Weight</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($coccidiosis['rules'] as $symptom => $weight)
                    <tr>
                        <td>
                            {{ $symptom }}
                            <span class="symptom-cat">{{ $symptomCategories[$symptom] }}</span>
                        </td>
                        <td>
                            <span class="weight-cell">
                                <span class="w w{{ $weight }}">{{ $weight }}</span>
                                <span class="meter m{{ $weight }}" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th scope="row">Total possible weight</th>
                    <td>{{ array_sum($coccidiosis['rules']) }}</td>
                </tr>
            </tfoot>
        </table>

        <p>
            Add those seven weights together and you get <strong>24</strong>. That is Coccidiosis'
            <strong>total possible weight</strong>, reached only if an owner reported every one of its
            symptoms, and it's the number the formula in Section 3 divides by.
        </p>

        <h3 id="knowledge-tree">The whole knowledge base at a glance</h3>
        <p>
            The diagram below maps the entire starter knowledge base as a tree. Each <strong>branch</strong>
            is a disease, and each <strong>leaf</strong> is one of its symptoms, labelled with that rule's
            weight. The total under each disease is its total possible weight.
            <span class="hover-hint">Point at a symptom to highlight every disease it appears under.</span>
        </p>

        <figure class="figure">
            <div class="tree">
                <div class="tree-root">
                    <span class="tree-root-icon" aria-hidden="true">📚</span>
                    <span>
                        <strong>Knowledge base</strong>
                        <small>{{ $diseases->count() }} diseases · {{ $ruleCount }} rules</small>
                    </span>
                </div>

                <ul class="tree-branches">
                    @foreach ($diseases as $disease)
                        <li class="tree-branch">
                            <div class="tree-disease">
                                <strong>{{ $disease['name'] }}</strong>
                                <span class="tree-disease-meta">
                                    <span class="sev sev-{{ $disease['severity'] }}">{{ $disease['severity'] }}</span>
                                    total {{ array_sum($disease['rules']) }}
                                </span>
                            </div>

                            <ul class="tree-leaves">
                                @foreach ($disease['rules'] as $symptom => $weight)
                                    <li class="tree-leaf" data-symptom="{{ \Illuminate\Support\Str::slug($symptom) }}">
                                        <span class="w w{{ $weight }}"><span class="sr-only">weight </span>{{ $weight }}</span>
                                        <span class="tree-leaf-name">
                                            @if ($sharedSymptoms->has($symptom))
                                                @php([$head, $lastWord] = $splitLastWord($symptom))
                                                {{ $head }}<span class="tree-tail">{{ $lastWord }}<svg class="tree-shared"
                                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                                                </svg></span>
                                                <span class="sr-only">(also listed under another disease)</span>
                                            @else
                                                {{ $symptom }}
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endforeach
                </ul>
            </div>

            <figcaption>
                <div class="legend">
                    <span class="legend-item"><span class="w w5">5</span> hallmark</span>
                    <span class="legend-item"><span class="w w4">4</span><span class="w w3">3</span> strongly associated</span>
                    <span class="legend-item"><span class="w w2">2</span><span class="w w1">1</span> general support</span>
                    <span class="legend-item">
                        <svg class="tree-shared" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                        </svg>
                        also listed under another disease
                    </span>
                </div>
                The starter knowledge base: {{ $diseases->count() }} diseases, {{ $ruleCount }} weighted rules.
                @if ($unlinkedSymptoms->isNotEmpty())
                    @php($single = $unlinkedSymptoms->count() === 1)
                    Of the {{ $symptomCategories->count() }} symptoms owners can choose from,
                    {{ $symptomCategories->count() - $unlinkedSymptoms->count() }} appear in the tree.
                    <strong>{{ $unlinkedSymptoms->join(', ', ' and ') }}</strong> can still be selected in the
                    app, but no rule uses {{ $single ? 'it' : 'them' }} yet, so {{ $single ? 'it' : 'they' }}
                    can't raise any disease's score until an administrator adds one.
                @endif
            </figcaption>
        </figure>

        <h3>Why some symptoms appear under more than one disease</h3>
        <p>
            The overlap is deliberate: general signs such as lethargy or loss of appetite show up in many
            illnesses, so they sit under several diseases with modest weights, while the heaviest weights go
            to each disease's hallmark signs, so the same observations produce clearly different scores for
            each disease.
        </p>

        <figure class="figure">
            <p class="figure-label">Symptoms shared between diseases</p>
            <ul class="overlap">
                @foreach ($sharedSymptoms as $symptom => $weights)
                    <li>
                        <span class="overlap-symptom">{{ $symptom }}</span>
                        <span class="overlap-chips">
                            @foreach ($weights as $diseaseName => $weight)
                                <span class="chip"><span class="w w{{ $weight }}"><span class="sr-only">weight </span>{{ $weight }}</span>{{ $diseaseName }}</span>
                            @endforeach
                        </span>
                    </li>
                @endforeach
            </ul>
            <figcaption>
                Every symptom listed under two or more diseases, with its weight in each. Twisted neck, for
                example, is a hallmark of Newcastle Disease (weight 5) but only a minor sign of Fowl Cholera
                (weight 2).
            </figcaption>
        </figure>

        <h3>Who keeps it up to date</h3>
        <p>
            Administrators manage all of this through the system's admin tools. They can add diseases and
            symptoms, create rules or change their weights, and link care advice to each disease. None of it
            is hard-coded into the app. Each starter disease comes with practical advice (for Coccidiosis:
            keep litter dry, give clean water with electrolytes, consult a veterinarian before medicating,
            and monitor the flock twice daily), which the app shows alongside the results. Entries are never
            deleted, only <em>deactivated</em>, so older health records always stay readable. The starter
            content is based on general, publicly documented poultry-health knowledge.
        </p>

        <div class="callout callout-summary">
            <span class="callout-icon" aria-hidden="true">✅</span>
            <p>
                <span class="callout-title">In short</span>
                The knowledge base is a set of weighted links between diseases and the symptoms owners can
                see. The weights say how strongly each symptom points to each disease.
            </p>
        </div>
    </section>

    <!-- ══ 3. The diagnostic engine & formula ════════════ -->
    <section id="engine" class="doc-section">
        <p class="section-kicker">Section 3</p>
        <h2>The diagnostic engine and its formula</h2>

        <p>
            The <strong>diagnostic engine</strong> is the part of the system that does the reasoning. When
            an owner submits the symptoms they've observed, the engine goes through every active disease in
            the knowledge base, one at a time, and asks the same question: <em>of all the symptom weight
            this disease is known for, how much did the owner actually report?</em> The answer, as a
            percentage, is that disease's <strong>match score</strong>.
        </p>

        <div class="formula-pair">
            <div class="formula-card">
                <p class="formula-label">In plain English</p>
                <p class="formula-plain">
                    Add up the weights of the symptoms that matched, divide by the total possible weight for
                    that disease, then multiply by 100.
                </p>
            </div>

            <div class="formula-card is-precise">
                <p class="formula-label">Precisely</p>
                <div class="formula-math" role="math"
                    aria-label="Match score equals: the sum of the weights of the matched symptoms, divided by the sum of the weights of all the disease's symptoms, times 100, rounded">
                    <span class="fm-lhs">Match score =</span>
                    <span class="fm-row">
                        <span class="fm-op">round(</span>
                        <span class="frac">
                            <span>Σ weights of the matched symptoms</span>
                            <span>Σ weights of all the disease's symptoms</span>
                        </span>
                        <span class="fm-op">× 100 )</span>
                    </span>
                </div>
                <p class="formula-foot">
                    Σ ("sigma") means "add up". The result is rounded to the nearest whole number, and
                    exact halves round up (12.5 becomes 13).
                </p>
            </div>
        </div>

        <h3 id="worked-example">Worked example: Coccidiosis</h3>
        <p>
            Suppose an owner reports three symptoms: <strong>bloody droppings</strong>, a <strong>pale
            comb</strong>, and <strong>lethargy</strong>. Here is how the engine scores Coccidiosis, using
            the real weights from Section 2.
        </p>

        <figure class="figure">
            <p class="figure-label">Coccidiosis: 24 units of possible weight</p>
            <div class="wbar" aria-hidden="true">
                @foreach ($section3Rules as $symptom => $weight)
                    <span class="wbar-seg{{ in_array($symptom, $section3Reported, true) ? ' is-hit' : '' }}"
                        style="flex: {{ $weight }} 1 0%">{{ $weight }}</span>
                @endforeach
            </div>
            <div class="wbar-scale">
                <span><strong>Reported: 12</strong></span>
                <span>Not reported: 12</span>
            </div>

            <div class="wbar-legend">
                <div>
                    <h4><span class="mark-yes" aria-hidden="true">✓</span> Reported by the owner</h4>
                    <ul>
                        @foreach ($section3Rules as $symptom => $weight)
                            @if (in_array($symptom, $section3Reported, true))
                                <li><span class="w w{{ $weight }}"><span class="sr-only">weight </span>{{ $weight }}</span> {{ $symptom }}</li>
                            @endif
                        @endforeach
                    </ul>
                </div>
                <div>
                    <h4><span class="mark-no" aria-hidden="true">✕</span> Not reported</h4>
                    <ul>
                        @foreach ($section3Rules as $symptom => $weight)
                            @unless (in_array($symptom, $section3Reported, true))
                                <li class="is-miss"><span class="w w{{ $weight }}"><span class="sr-only">weight </span>{{ $weight }}</span> {{ $symptom }}</li>
                            @endunless
                        @endforeach
                    </ul>
                </div>
            </div>

            <figcaption>
                Each block is one of Coccidiosis' seven rules, sized by its weight. The owner's report
                fills 12 of the 24 units: exactly half.
            </figcaption>
        </figure>

        <ol class="calc">
            <li>
                <span class="calc-num" aria-hidden="true">1</span>
                <div>
                    <h4>Add up the weights of the symptoms that matched</h4>
                    <p>Bloody droppings, pale comb, and lethargy are all on Coccidiosis' list.</p>
                    <p class="calc-eq">5 + 4 + 3 = <b>12</b></p>
                </div>
            </li>
            <li>
                <span class="calc-num" aria-hidden="true">2</span>
                <div>
                    <h4>Add up the total possible weight</h4>
                    <p>All seven of Coccidiosis' rules, reported or not.</p>
                    <p class="calc-eq">5 + 4 + 3 + 3 + 3 + 3 + 3 = <b>24</b></p>
                </div>
            </li>
            <li>
                <span class="calc-num" aria-hidden="true">3</span>
                <div>
                    <h4>Divide, then multiply by 100</h4>
                    <p>Nothing to round here: the answer is already a whole number.</p>
                    <p class="calc-eq">12 ÷ 24 = 0.5 &nbsp;→&nbsp; 0.5 × 100 = <b>50</b></p>
                </div>
            </li>
            <li class="is-result">
                <span class="calc-num" aria-hidden="true">=</span>
                <p>Coccidiosis match score: <strong>50%</strong></p>
            </li>
        </ol>

        <h3>The same report, scored against every disease</h3>
        <p>
            The engine doesn't stop at Coccidiosis. It runs the same calculation for every active disease,
            then keeps only the matches that are strong enough to be useful:
        </p>

        <div class="scores-key" aria-hidden="true">
            <span><i class="key-fill"></i> match score</span>
            <span><i class="key-cut"></i> 20% cut-off: anything below is not shown</span>
        </div>

        <ul class="scores">
            <li class="score">
                <div class="score-top">
                    <span class="score-name">Coccidiosis</span>
                    <span class="score-tag tag-shown">Shown</span>
                    <span class="score-pct">50%</span>
                </div>
                <div class="score-track" aria-hidden="true"><span class="score-fill" style="width: 50%"></span></div>
                <p class="score-math">
                    Bloody droppings <span class="w w5">5</span> + Pale comb <span class="w w4">4</span>
                    + Lethargy <span class="w w3">3</span> = 12 of 24
                </p>
            </li>
            <li class="score is-hidden">
                <div class="score-top">
                    <span class="score-name">Fowl Cholera</span>
                    <span class="score-tag tag-hidden">Hidden: under 20%</span>
                    <span class="score-pct">13%</span>
                </div>
                <div class="score-track" aria-hidden="true"><span class="score-fill" style="width: 13%"></span></div>
                <p class="score-math">Lethargy <span class="w w3">3</span> = 3 of 24 → 12.5%, rounded to 13%</p>
            </li>
            <li class="score is-hidden">
                <div class="score-top">
                    <span class="score-name">Fowl Pox</span>
                    <span class="score-tag tag-hidden">Hidden: under 20%</span>
                    <span class="score-pct">13%</span>
                </div>
                <div class="score-track" aria-hidden="true"><span class="score-fill" style="width: 13%"></span></div>
                <p class="score-math">Lethargy <span class="w w2">2</span> = 2 of 16 → 12.5%, rounded to 13%</p>
            </li>
            <li class="score is-skipped">
                <div class="score-top">
                    <span class="score-name">Infectious Coryza and Newcastle Disease</span>
                    <span class="score-tag tag-hidden">Not considered</span>
                    <span class="score-pct">—</span>
                </div>
                <p class="score-math">None of their symptoms were reported, so they aren't candidates at all.</p>
            </li>
        </ul>

        <p>
            So for this report the owner sees exactly one possible condition: <strong>Coccidiosis, 50%</strong>.
            Fowl Pox and Fowl Cholera did match lethargy, but 13% is under the 20% cut-off, so they're left
            out.
        </p>

        <h3>Why weights, and not just a count?</h3>
        <p>
            If the engine simply counted symptoms, any two of Coccidiosis' seven signs would give the same
            score: 2 out of 7, about 29%. Weights let the telling signs count for more:
        </p>

        <div class="compare">
            <div class="compare-item is-strong">
                <p>
                    Bloody droppings <span class="w w5">5</span> + Pale comb <span class="w w4">4</span>
                    = 9 of 24
                    <span class="compare-pct">38%</span>
                </p>
            </div>
            <div class="compare-item">
                <p>
                    Ruffled feathers <span class="w w3">3</span> + Huddling together <span class="w w3">3</span>
                    = 6 of 24
                    <span class="compare-pct">25%</span>
                </p>
            </div>
        </div>

        <p>
            Same number of symptoms, but bloody droppings and a pale comb are far more telling, and the
            scores reflect that.
        </p>

        <h3>The rules the engine always follows</h3>
        <ol class="prose-list">
            <li>
                <strong>Only active entries count.</strong> A deactivated disease is never suggested. A
                deactivated symptom is dropped from every disease's calculation, from both the top and the
                bottom of the fraction, as if its rules didn't exist.
            </li>
            <li>
                <strong>A disease must match at least one reported symptom</strong> to be considered at all.
            </li>
            <li>
                <strong>Weak matches are hidden.</strong> Scores under 20% are left out as too weak to be
                useful.
            </li>
            <li>
                <strong>At most five results</strong> are returned, highest score first.
            </li>
            <li>
                <strong>Ties are broken alphabetically</strong>, so the same report always produces the same
                order.
            </li>
            <li>
                <strong>Extra symptoms never lower a score.</strong> Each disease is judged only against its
                own list; a reported symptom that isn't on that list simply doesn't count for that disease.
            </li>
            <li>
                <strong>Serious diseases carry a vet warning.</strong> For diseases rated severe or critical,
                the result includes the disease's veterinary warning, where one has been written.
            </li>
        </ol>
        <p>
            The 20% cut-off and the five-result limit are system settings, so they can be tuned without
            changing how scores are calculated.
        </p>

        <div class="callout callout-summary">
            <span class="callout-icon" aria-hidden="true">✅</span>
            <p>
                <span class="callout-title">In short</span>
                Match score = matched weight ÷ total possible weight × 100, worked out separately for every
                disease. Weak matches are hidden, and the rest are ranked.
            </p>
        </div>
    </section>

    <!-- ══ 4. The end-to-end flow ════════════════════════ -->
    <section id="flow" class="doc-section">
        <p class="section-kicker">Section 4</p>
        <h2>The end-to-end flow</h2>

        <p>
            Here is everything that happens during one health check, from the moment an owner picks a bird
            to the moment the results appear on their phone, and where each step takes place: in the mobile
            app, or on the GAMEFOWL server.
        </p>

        <figure class="figure">
            <div class="flow">
                <div class="flow-lane" aria-hidden="true">📱 Owner, in the mobile app</div>
                <div class="flow-lane server" aria-hidden="true">🖥️ GAMEFOWL server</div>

                <div class="flow-node n1 has-down"><span class="flow-num">1</span>Select a gamefowl<span class="flow-tag">App</span></div>
                <div class="flow-node n2 has-down"><span class="flow-num">2</span>Start an assessment<span class="flow-tag">App</span></div>
                <div class="flow-node n3 has-down"><span class="flow-num">3</span>Pick the symptoms<span class="flow-tag">App</span></div>
                <div class="flow-node n4 is-before-handoff"><span class="flow-num">4</span>Submit<span class="flow-tag">App</span></div>
                <div class="flow-handoff h1 to-server"><span class="flow-handoff-label">symptoms sent to the server</span></div>
                <div class="flow-node n5 server has-down"><span class="flow-num">5</span>Score every disease<span class="flow-tag">Server</span></div>
                <div class="flow-node n6 server has-down"><span class="flow-num">6</span>Rank the results<span class="flow-tag">Server</span></div>
                <div class="flow-node n7 server is-before-handoff"><span class="flow-num">7</span>Save to health history<span class="flow-tag">Server</span></div>
                <div class="flow-handoff h2 to-app"><span class="flow-handoff-label">results sent back to the app</span></div>
                <div class="flow-node n8 is-last"><span class="flow-num">8</span>Show the results<span class="flow-tag">App</span></div>
            </div>

            <figcaption>
                Steps 1–4 happen in the app, steps 5–7 on the server, and step 8 back in the app. Dashed
                arrows are the moments data travels between the phone and the server.
            </figcaption>
        </figure>

        <ol class="steps">
            <li class="step">
                <span class="step-num" aria-hidden="true">1</span>
                <div>
                    <h3>Select a gamefowl</h3>
                    <span class="step-where">Owner · in the app</span>
                    <p>
                        The owner opens their list of registered birds and picks the one to check. Owners
                        only ever see their own birds, and the server refuses any request about someone
                        else's.
                    </p>
                </div>
            </li>
            <li class="step">
                <span class="step-num" aria-hidden="true">2</span>
                <div>
                    <h3>Start an assessment</h3>
                    <span class="step-where">Owner · in the app</span>
                    <p>
                        The owner starts a new health check for that bird. They can add optional details:
                        how long the signs have lasted, whether appetite and activity seem normal, and
                        free-text notes. These are saved with the check for the record, but they don't
                        change any score.
                    </p>
                </div>
            </li>
            <li class="step">
                <span class="step-num" aria-hidden="true">3</span>
                <div>
                    <h3>Pick the symptoms</h3>
                    <span class="step-where">Owner · in the app</span>
                    <p>
                        The app lists the active symptoms from the knowledge base, grouped by category
                        (respiratory, physical, digestive, neurological, behavioral). The owner ticks
                        everything they can see: at least one symptom, and up to 30.
                    </p>
                </div>
            </li>
            <li class="step">
                <span class="step-num" aria-hidden="true">4</span>
                <div>
                    <h3>Submit</h3>
                    <span class="step-where">Owner → server</span>
                    <p>
                        The selected symptoms are sent to the server. Before anything else, the server
                        double-checks the request: the bird must belong to this owner, and every symptom must
                        exist and be active. If anything is wrong, the request is turned down with a clear
                        message and nothing is saved.
                    </p>
                </div>
            </li>
            <li class="step server">
                <span class="step-num" aria-hidden="true">5</span>
                <div>
                    <h3>Score every disease</h3>
                    <span class="step-where">Server · diagnostic engine</span>
                    <p>
                        The diagnostic engine compares the reported symptoms with the rules of every active
                        disease and works out each one's match score, exactly as shown in Section 3.
                    </p>
                </div>
            </li>
            <li class="step server">
                <span class="step-num" aria-hidden="true">6</span>
                <div>
                    <h3>Rank the results</h3>
                    <span class="step-where">Server</span>
                    <p>
                        Scores under 20% are dropped. The rest are sorted from highest to lowest (ties
                        alphabetically) and cut to the top five. Each result then gets its details: which
                        symptoms matched, which of the disease's symptoms weren't reported, the disease's
                        severity, a vet warning for severe or critical diseases, and the care advice linked
                        to that disease.
                    </p>
                </div>
            </li>
            <li class="step server">
                <span class="step-num" aria-hidden="true">7</span>
                <div>
                    <h3 id="snapshots">Save to health history</h3>
                    <span class="step-where">Server</span>
                    <p>
                        The whole check (the bird's age and sex at that moment, the symptoms reported, and
                        every ranked result) is saved as one permanent record in the bird's health history.
                        The save is all-or-nothing: if anything fails partway, nothing is kept. The record is
                        a <strong>snapshot</strong>, so if an administrator later renames or deactivates a
                        disease, it still shows exactly what the owner saw that day. It can't be edited or
                        deleted.
                    </p>
                </div>
            </li>
            <li class="step">
                <span class="step-num" aria-hidden="true">8</span>
                <div>
                    <h3>Show the results</h3>
                    <span class="step-where">Server → owner</span>
                    <p>
                        The server sends the results back and the app displays them: the possible conditions
                        in ranked order, each with its match score, the matched and missing symptoms,
                        severity, any vet warning, and care advice, always together with the disclaimer
                        described in Section 5. The bird's health-status summary is based on its latest
                        check: a top match of 50% or more flags the bird as <em>needing attention</em>.
                    </p>
                </div>
            </li>
        </ol>

        <div class="callout callout-summary">
            <span class="callout-icon" aria-hidden="true">✅</span>
            <p>
                <span class="callout-title">In short</span>
                The owner reports; the server checks, scores, ranks, and saves; the app shows the results.
                Every check becomes a permanent entry in the bird's health history.
            </p>
        </div>
    </section>

    <!-- ══ 5. Possible, not a diagnosis ══════════════════ -->
    <section id="possible" class="doc-section">
        <p class="section-kicker">Section 5</p>
        <h2>Why a result is "possible," not a diagnosis</h2>

        <p>
            Everything the system reports is framed as a <strong>possible condition</strong>, never a
            diagnosis. That is a deliberate rule built into the system, not just careful wording on one
            screen, and the reason is simple: the system only knows what the owner tells it.
        </p>

        <ul class="limits">
            <li>
                <span class="icon-tile" aria-hidden="true">🩺</span>
                <span><strong>It can't examine the bird.</strong> A veterinarian can handle the bird, look
                inside its mouth, and order laboratory tests. The system only sees a list of ticked
                boxes.</span>
            </li>
            <li>
                <span class="icon-tile" aria-hidden="true">👀</span>
                <span><strong>Observations can be incomplete.</strong> An owner might miss a sign, or
                mistake one symptom for another.</span>
            </li>
            <li>
                <span class="icon-tile" aria-hidden="true">🔀</span>
                <span><strong>Diseases share signs.</strong> As Section 2 showed, the same symptom can
                point to several diseases at once.</span>
            </li>
            <li>
                <span class="icon-tile" aria-hidden="true">📚</span>
                <span><strong>It only knows what it has been taught.</strong> A disease that isn't in the
                knowledge base can never be suggested.</span>
            </li>
        </ul>

        <h3>The disclaimer owners see</h3>
        <p>Every health-check result comes with this disclaimer, word for word:</p>

        <blockquote class="quote">
            “{{ \App\Http\Resources\HealthAssessmentResource::DISCLAIMER }}”
            <cite>Included with every assessment result</cite>
        </blockquote>

        <h3>How the cautious wording is built in</h3>
        <ul class="prose-list">
            <li>
                <strong>Results are never labelled as a diagnosis.</strong> Even in the data the server sends
                to the app, each result is called a <code>possible_disease</code>.
            </li>
            <li>
                <strong>A match score describes resemblance, not probability.</strong> 50% means the reported
                signs make up half of the disease's weighted symptom list. It does not mean there is a 50%
                chance the bird has that disease.
            </li>
            <li>
                <strong>Serious diseases carry a vet warning.</strong> For diseases rated severe or critical,
                the result includes the disease's veterinary warning. For Newcastle Disease, it says that
                suspected cases must be reported to a veterinarian and the Bureau of Animal Industry.
            </li>
            <li>
                <strong>An empty result isn't an all-clear.</strong> If nothing reaches the 20% cut-off, no
                possible conditions are listed. That only means the reported signs don't closely resemble any
                disease currently in the knowledge base.
            </li>
        </ul>

        <div class="callout callout-amber">
            <span class="callout-icon" aria-hidden="true">⚠️</span>
            <p>
                <span class="callout-title">In one sentence</span>
                GAMEFOWL is an educational and decision-support tool that helps owners notice problems early
                and act sooner. It does not replace a licensed veterinarian.
            </p>
        </div>
    </section>

    <!-- ══ 6. Example walkthrough ════════════════════════ -->
    <section id="walkthrough" class="doc-section">
        <p class="section-kicker">Section 6</p>
        <h2>Example walkthrough, from start to finish</h2>

        <p>
            This section follows one complete health check using the starter knowledge base. The numbers are
            exactly what the system produces, and they line up with the worked example in Section 3. The
            phone screens are <strong>illustrative mockups, not screenshots</strong>: the finished app may
            look different, but the symptoms, scores, and wording shown are what the system actually
            returns.
        </p>

        <ol class="story">
            <li class="story-step">
                <div class="story-label"><b>A</b> The situation</div>
                <h3>Juan notices something is wrong</h3>
                <p>
                    Juan raises gamefowl in his backyard. One morning he notices that Bruno, his 10-month-old
                    stag, is standing off by himself, dull and sleepy. Bruno's comb looks paler than usual,
                    there is blood in his droppings, and he has barely touched his feed.
                </p>
            </li>

            <li class="story-step has-phone">
                <div>
                    <div class="story-label"><b>B</b> In the app</div>
                    <h3>Juan records what he sees</h3>
                    <p>
                        He opens GAMEFOWL, picks Bruno from his list of birds, and starts a health check. On
                        the checklist he ticks four symptoms: <strong>Bloody droppings</strong> and
                        <strong>Loss of appetite</strong> under Digestive, <strong>Pale comb</strong> under
                        Physical, and <strong>Lethargy or depression</strong> under Behavioral. Then he taps
                        Submit.
                    </p>
                    <p>
                        He could also add the optional details (how long the signs have lasted, appetite,
                        activity, notes). They would be saved with the check but wouldn't affect the scores.
                    </p>
                </div>

                <div>
                    <div class="phone" role="img"
                        aria-label="Mockup of the symptom checklist for Bruno, with Bloody droppings, Loss of appetite, Pale comb, and Lethargy or depression ticked, and a Submit button showing 4 selected">
                        <div class="phone-screen">
                            <div class="ph-status">
                                <span>9:41</span>
                                <span class="ph-signal"><i style="height: 4px"></i><i style="height: 6px"></i><i style="height: 8px"></i><i style="height: 10px"></i></span>
                            </div>
                            <div class="ph-bar"><span class="ph-back">‹</span>Health check · Bruno</div>
                            <div class="ph-body">
                                <div class="ph-hint">Tick every sign you can see.</div>

                                <div class="ph-cat">Digestive</div>
                                <div class="ph-check is-on">Bloody droppings</div>
                                <div class="ph-check">Greenish watery droppings</div>
                                <div class="ph-check is-on">Loss of appetite</div>
                                <div class="ph-check">Watery white droppings</div>

                                <div class="ph-cat">Physical</div>
                                <div class="ph-check is-on">Pale comb</div>
                                <div class="ph-check">Ruffled feathers</div>
                                <div class="ph-check">Weight loss despite feeding</div>

                                <div class="ph-cat">Behavioral</div>
                                <div class="ph-check is-on">Lethargy or depression</div>
                                <div class="ph-check">Huddling together</div>
                            </div>
                            <div class="ph-cta">Submit · 4 selected</div>
                        </div>
                    </div>
                    <div class="mockup-note">Illustrative mockup, not a screenshot</div>
                </div>
            </li>

            <li class="story-step">
                <div class="story-label server"><b>C</b> On the server</div>
                <h3>The engine scores all five diseases</h3>
                <p>
                    The server confirms that Bruno belongs to Juan and that all four symptoms are valid, then
                    runs the formula for every active disease:
                </p>

                <div class="scores-key" aria-hidden="true">
                    <span><i class="key-fill"></i> match score</span>
                    <span><i class="key-cut"></i> 20% cut-off</span>
                </div>

                <ul class="scores">
                    <li class="score">
                        <div class="score-top">
                            <span class="score-name">Coccidiosis</span>
                            <span class="score-tag tag-shown">Shown · #1</span>
                            <span class="score-pct">50%</span>
                        </div>
                        <div class="score-track" aria-hidden="true"><span class="score-fill" style="width: 50%"></span></div>
                        <p class="score-math">
                            Bloody droppings <span class="w w5">5</span> + Pale comb <span class="w w4">4</span>
                            + Lethargy <span class="w w3">3</span> = 12 of 24
                        </p>
                    </li>
                    <li class="score">
                        <div class="score-top">
                            <span class="score-name">Fowl Cholera</span>
                            <span class="score-tag tag-shown">Shown · #2</span>
                            <span class="score-pct">25%</span>
                        </div>
                        <div class="score-track" aria-hidden="true"><span class="score-fill" style="width: 25%"></span></div>
                        <p class="score-math">
                            Lethargy <span class="w w3">3</span> + Loss of appetite <span class="w w3">3</span>
                            = 6 of 24
                        </p>
                    </li>
                    <li class="score is-hidden">
                        <div class="score-top">
                            <span class="score-name">Fowl Pox</span>
                            <span class="score-tag tag-hidden">Hidden: under 20%</span>
                            <span class="score-pct">13%</span>
                        </div>
                        <div class="score-track" aria-hidden="true"><span class="score-fill" style="width: 13%"></span></div>
                        <p class="score-math">Lethargy <span class="w w2">2</span> = 2 of 16 → 12.5%, rounded to 13%</p>
                    </li>
                    <li class="score is-hidden">
                        <div class="score-top">
                            <span class="score-name">Newcastle Disease</span>
                            <span class="score-tag tag-hidden">Hidden: under 20%</span>
                            <span class="score-pct">11%</span>
                        </div>
                        <div class="score-track" aria-hidden="true"><span class="score-fill" style="width: 11%"></span></div>
                        <p class="score-math">Loss of appetite <span class="w w3">3</span> = 3 of 28 → 10.7%, rounded to 11%</p>
                    </li>
                    <li class="score is-hidden">
                        <div class="score-top">
                            <span class="score-name">Infectious Coryza</span>
                            <span class="score-tag tag-hidden">Hidden: under 20%</span>
                            <span class="score-pct">10%</span>
                        </div>
                        <div class="score-track" aria-hidden="true"><span class="score-fill" style="width: 10%"></span></div>
                        <p class="score-math">Loss of appetite <span class="w w2">2</span> = 2 of 21 → 9.5%, rounded to 10%</p>
                    </li>
                </ul>

                <p>Two diseases clear the cut-off and are ranked: Coccidiosis first, Fowl Cholera second.</p>
            </li>

            <li class="story-step has-phone">
                <div>
                    <div class="story-label"><b>D</b> In the app</div>
                    <h3>Juan sees the results</h3>
                    <p>
                        <strong>Coccidiosis</strong> is listed first at 50%, with the three symptoms that
                        matched and the four it expected but didn't see (ruffled feathers, weight loss despite
                        feeding, watery white droppings, and huddling together), a useful prompt for Juan to
                        look for those too.
                    </p>
                    <p>
                        <strong>Fowl Cholera</strong> follows at 25%. Because it's rated severe, it carries
                        its vet warning. Each result includes its care advice, and the disclaimer from Section
                        5 sits underneath.
                    </p>
                </div>

                <div>
                    <div class="phone" role="img"
                        aria-label="Mockup of the results screen: 1, Coccidiosis, severe, 50% match, with matched and not-reported symptoms and care advice; 2, Fowl Cholera, severe, 25% match, with its vet warning; then the disclaimer">
                        <div class="phone-screen">
                            <div class="ph-status">
                                <span>9:42</span>
                                <span class="ph-signal"><i style="height: 4px"></i><i style="height: 6px"></i><i style="height: 8px"></i><i style="height: 10px"></i></span>
                            </div>
                            <div class="ph-bar"><span class="ph-back">‹</span>Results · Bruno</div>
                            <div class="ph-body">
                                <div class="ph-title">Possible conditions</div>

                                <div class="ph-result">
                                    <div class="ph-result-top">
                                        <span class="ph-rank">1</span>
                                        <span class="ph-name">Coccidiosis</span>
                                        <span class="sev sev-severe">Severe</span>
                                    </div>
                                    <div class="ph-score"><span class="ph-track"><span style="width: 50%"></span></span><b>50%</b> match</div>
                                    <div class="ph-line"><b>Matched:</b> Pale comb, Bloody droppings, Lethargy or depression</div>
                                    <div class="ph-line"><b>Not reported:</b> Ruffled feathers, Weight loss despite feeding, Watery white droppings, Huddling together</div>
                                    <div class="ph-line"><b>Care advice:</b> Keep litter dry and replace soiled bedding <span class="ph-more">+3 more</span></div>
                                </div>

                                <div class="ph-result">
                                    <div class="ph-result-top">
                                        <span class="ph-rank">2</span>
                                        <span class="ph-name">Fowl Cholera</span>
                                        <span class="sev sev-severe">Severe</span>
                                    </div>
                                    <div class="ph-score"><span class="ph-track"><span style="width: 25%"></span></span><b>25%</b> match</div>
                                    <div class="ph-warn">⚠ Fowl cholera can recur in the same facility through carrier birds. Seek veterinary guidance before restocking after an outbreak.</div>
                                </div>

                                <div class="ph-disclaimer">{{ \App\Http\Resources\HealthAssessmentResource::DISCLAIMER }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="mockup-note">Illustrative mockup, not a screenshot</div>
                </div>
            </li>

            <li class="story-step">
                <div class="story-label server"><b>E</b> Afterwards</div>
                <h3>The check is saved to Bruno's health history</h3>
                <p>
                    The check is stored permanently in Bruno's health history, exactly as Juan saw it. Because
                    the top match reached 50% (the system flags a bird once its latest check has a match of 50%
                    or more), Bruno's health status now reads <span class="status-pill">Needs attention</span>
                </p>
            </li>
        </ol>

        <div class="callout">
            <span class="callout-icon" aria-hidden="true">🔁</span>
            <p>
                <span class="callout-title">How this lines up with Section 3</span>
                Coccidiosis scores exactly as it did in Section 3: 12 ÷ 24 = 50%. The extra symptom Juan
                reported, loss of appetite, isn't on Coccidiosis' list, so it neither raises nor lowers that
                score. It <em>is</em> on Fowl Cholera's list, though: lethargy (3) plus loss of appetite (3)
                makes 6 of 24, lifting Fowl Cholera from 13% in Section 3 to 25% here, over the cut-off.
            </p>
        </div>

        <details class="tech">
            <summary><span aria-hidden="true">🧾</span> For the technically curious: the data the server sends back</summary>
            <div class="tech-body">
                <p>
                    The app receives the results as JSON. This is the real response for Juan's check on a
                    freshly installed system, shortened: the optional-detail fields and most of the care
                    advice are left out.
                </p>
<pre><code>{
  "success": true,
  "message": "Health assessment submitted successfully.",
  "data": {
    "id": 1,
    "gamefowl_id": 1,
    "submitted_symptoms": [
      { "id": 10, "name": "Pale comb" },
      { "id": 15, "name": "Bloody droppings" },
      { "id": 17, "name": "Loss of appetite" },
      { "id": 21, "name": "Lethargy or depression" }
    ],
    "results": [
      {
        "rank": 1,
        "possible_disease": { "id": 4, "name": "Coccidiosis" },
        "match_score": 50,
        "matched_symptoms": ["Pale comb", "Bloody droppings", "Lethargy or depression"],
        "missing_symptoms": ["Ruffled feathers", "Weight loss despite feeding",
                             "Watery white droppings", "Huddling together"],
        "severity_at_assessment": "severe",
        "vet_warning_at_assessment": null,
        "recommendations": [
          { "id": 5, "title": "Keep litter dry and replace soiled bedding", "category": "hygiene", … },
          …
        ]
      },
      {
        "rank": 2,
        "possible_disease": { "id": 5, "name": "Fowl Cholera" },
        "match_score": 25,
        "matched_symptoms": ["Loss of appetite", "Lethargy or depression"],
        …
        "severity_at_assessment": "severe",
        "vet_warning_at_assessment":
          "Fowl cholera can recur in the same facility through carrier birds. …",
        …
      }
    ],
    "disclaimer":
      "This assessment is generated from reported symptoms and is not a confirmed …",
    "created_at": "…"
  }
}</code></pre>
            </div>
        </details>
    </section>

    <!-- ══ 7. FAQ ════════════════════════════════════════ -->
    <section id="faq" class="doc-section">
        <p class="section-kicker">Section 7</p>
        <h2>Frequently asked questions</h2>

        <div class="faq">
            <div class="faq-item">
                <h3><span class="faq-q" aria-hidden="true">?</span>Can the system be wrong?</h3>
                <p>
                    Yes. It can only work with what the owner reports and what is in the knowledge base. A
                    missed or misread sign, a disease that shares symptoms with another, or a condition that
                    hasn't been added yet can all lead to a misleading list. That's why results are presented
                    as possible conditions with a disclaimer (Section 5), and why serious diseases point
                    owners to a veterinarian.
                </p>
            </div>

            <div class="faq-item">
                <h3><span class="faq-q" aria-hidden="true">?</span>Does a 50% match mean a 50% chance the bird has that disease?</h3>
                <p>
                    No. The score measures how much of a disease's weighted symptom list the owner reported:
                    resemblance, not probability. A bird can match a disease at 50% and not have it, or have a
                    disease that scored lower.
                </p>
            </div>

            <div class="faq-item">
                <h3><span class="faq-q" aria-hidden="true">?</span>Who decides the weights?</h3>
                <p>
                    Administrators. The starter weights were set from general, publicly documented
                    poultry-health knowledge: hallmark signs got 5, strongly associated signs 3 or 4, and
                    general signs 1 or 2. Administrators can change a weight, add a rule, or remove one at any
                    time through the admin tools. Changes apply to new checks straight away and never alter
                    past records.
                </p>
            </div>

            <div class="faq-item">
                <h3><span class="faq-q" aria-hidden="true">?</span>What happens if a disease is deactivated?</h3>
                <p>
                    It stops appearing in new results immediately, but nothing is deleted. Past checks that
                    listed it still show it exactly as the owner saw it, because every check is saved as a
                    snapshot, and an administrator can reactivate it later. Deactivating a symptom works the
                    same way: it disappears from the checklist, and its rules stop counting toward every
                    disease's score (both the matched weight and the total).
                </p>
            </div>

            <div class="faq-item">
                <h3><span class="faq-q" aria-hidden="true">?</span>Do the optional details (duration, appetite, activity, notes) change the score?</h3>
                <p>
                    No. Only the ticked symptoms are scored. The optional details are saved with the check for
                    the record, which is useful context when the owner or a vet looks back through the bird's
                    history.
                </p>
            </div>

            <div class="faq-item">
                <h3><span class="faq-q" aria-hidden="true">?</span>Is this artificial intelligence?</h3>
                <p>
                    Not in the machine-learning sense most people mean today: there is no trained model, no
                    learning from data, and no photo analysis. GAMEFOWL is a rule-based expert system, a
                    classic knowledge-based approach from the field of AI in which rules written by people are
                    applied the same way every time. The advantage is transparency: every result can be traced
                    back to specific rules and recalculated by hand, as this page does.
                </p>
            </div>
        </div>

        <div class="callout">
            <span class="callout-icon" aria-hidden="true">💻</span>
            <p>
                <span class="callout-title">Looking for the code-level walkthrough?</span>
                The technical documentation walks through <code>DiagnosticEngine.php</code> and the files around it,
                with real code, request and response payloads, and the unit tests.
                <a href="{{ route('docs.technical') }}">View Technical Documentation →</a>
            </p>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            // Knowledge tree: pointing at a symptom highlights every disease
            // it appears under.
            var tree = document.querySelector('.tree');

            if (tree) {
                var leaves = Array.prototype.slice.call(tree.querySelectorAll('.tree-leaf'));

                function clearTrace() {
                    tree.classList.remove('is-tracing');
                    leaves.forEach(function (leaf) {
                        leaf.classList.remove('is-linked');
                    });
                }

                tree.addEventListener('mouseover', function (event) {
                    var leaf = event.target.closest('.tree-leaf');

                    if (!leaf) {
                        clearTrace();
                        return;
                    }

                    var key = leaf.getAttribute('data-symptom');

                    tree.classList.add('is-tracing');
                    leaves.forEach(function (other) {
                        other.classList.toggle('is-linked', other.getAttribute('data-symptom') === key);
                    });
                });

                tree.addEventListener('mouseleave', clearTrace);
            }
        })();
    </script>
@endpush
