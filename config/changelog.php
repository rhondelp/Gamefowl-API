<?php

/*
|--------------------------------------------------------------------------
| App Release Changelog
|--------------------------------------------------------------------------
|
| Source of truth for the "Changelog" section on the public landing page
| (resources/views/welcome.blade.php). The view loops over 'releases', so
| adding a release means adding one block here — no HTML is touched.
|
| SCOPE — read before adding anything:
|
| This is a PUBLIC, USER-FACING changelog for the GAMEFOWL Android app.
| An entry is added when an APK is actually released to users, describing
| what changed *for them*.
|
| It is NOT a backend development log. Backend milestones (the API work
| recorded in MILESTONE_REPORTS.md and ticked off under "Development
| Status" in README.md) are internal engineering history and do not belong
| here. A backend milestone shipping is not, by itself, a release.
|
| Ordering: NEWEST FIRST — the view renders the array as-is and tags the
| first element as the latest release.
|
| Entry shape:
|   version    string  Semantic version, 'v' prefixed (e.g. 'v1.0.0')
|   date       string  Release date, ISO-8601 (Y-m-d); formatted by the view
|   summary    string  One line — what this release is, readable on its own
|   highlights array   3–6 short user-facing bullets ("what's new for you")
|
| Example of the next entry to add once the first build ships:
|
|   [
|       'version' => 'v1.0.0',
|       'date' => '2026-10-01',
|       'summary' => 'First public release of the GAMEFOWL Android app.',
|       'highlights' => [
|           'Register your birds and keep their profiles in one place',
|           'Check a bird for early signs of illness from the symptoms you observe',
|           'Review every past health check on a per-bird timeline',
|       ],
|   ],
|
| Highlight strings may contain inline <code>/<em> markup and are rendered
| unescaped by the view. They are developer-authored constants, never user
| input — keep it that way.
|
*/

return [

    /*
     * Empty until the first APK is released. The landing page renders a
     * styled empty state while this is empty, naming 'upcoming_version'
     * below as what's next.
     */
    'releases' => [],

    /*
     * The planned first public version, shown in the changelog's empty
     * state. Once that release ships it moves into 'releases' above and
     * this becomes the *next* planned version.
     */
    'upcoming_version' => 'v1.0.0',

];
