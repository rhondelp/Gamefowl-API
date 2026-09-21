<?php

/*
|--------------------------------------------------------------------------
| Expert-System Documentation Page
|--------------------------------------------------------------------------
|
| Settings and page data for the plain-language documentation page at
| /documentation (routes/web.php, resources/views/documentation/).
|
*/

return [

    /*
    | Shared password for the page. One static value for everyone — a
    | visibility gate so the page stays off casual public view, not a real
    | auth system (see the comment above the routes in routes/web.php).
    | Set DOCS_PASSWORD in .env; while it is empty the page cannot be
    | unlocked at all.
    */
    'password' => env('DOCS_PASSWORD'),

    /*
    | Frozen copy of the STARTER knowledge base from KnowledgeBaseSeeder.
    | The page draws its knowledge tree, example tables, and counts from
    | this array. Names, categories, severities, and weights must match the
    | seeder exactly — DocumentationPageTest fails if they drift, so update
    | this copy (and the page's worked examples) whenever the seeder
    | changes. 'about' is the page's own one-line summary of each disease.
    |
    | Order: symptoms and diseases as seeded; each disease's rules strongest
    | first.
    */
    'knowledge_base' => [

        // symptom name => category
        'symptoms' => [
            'Nasal discharge (runny nose)' => 'respiratory',
            'Sneezing' => 'respiratory',
            'Coughing' => 'respiratory',
            'Gasping or labored breathing' => 'respiratory',
            'Wet rales (rattling breath sounds)' => 'respiratory',
            'Foamy eye discharge' => 'respiratory',
            'Swelling of the face or wattles' => 'physical',
            'Wart-like scabs on comb or wattles' => 'physical',
            'Yellow patches inside the mouth' => 'physical',
            'Pale comb' => 'physical',
            'Ruffled feathers' => 'physical',
            'Weight loss despite feeding' => 'physical',
            'Lameness or swollen joints' => 'physical',
            'Greenish watery droppings' => 'digestive',
            'Bloody droppings' => 'digestive',
            'Watery white droppings' => 'digestive',
            'Loss of appetite' => 'digestive',
            'Twisted neck (torticollis)' => 'neurological',
            'Paralysis of legs or wings' => 'neurological',
            'Circling or stargazing' => 'neurological',
            'Lethargy or depression' => 'behavioral',
            'Sudden death without prior signs' => 'behavioral',
            'Huddling together' => 'behavioral',
        ],

        'diseases' => [
            [
                'name' => 'Infectious Coryza',
                'severity' => 'moderate',
                'about' => 'A contagious bacterial infection of the nose and face — the "cold" or "sipon" of gamefowl raising.',
                // symptom name => weight (1–5)
                'rules' => [
                    'Nasal discharge (runny nose)' => 5,
                    'Swelling of the face or wattles' => 5,
                    'Foamy eye discharge' => 4,
                    'Sneezing' => 3,
                    'Wet rales (rattling breath sounds)' => 2,
                    'Loss of appetite' => 2,
                ],
            ],
            [
                'name' => 'Fowl Pox',
                'severity' => 'moderate',
                'about' => 'A slow-spreading virus carried mainly by mosquitoes; causes wart-like scabs on the comb and wattles, or patches inside the mouth.',
                'rules' => [
                    'Wart-like scabs on comb or wattles' => 5,
                    'Yellow patches inside the mouth' => 4,
                    'Gasping or labored breathing' => 3,
                    'Weight loss despite feeding' => 2,
                    'Lethargy or depression' => 2,
                ],
            ],
            [
                'name' => 'Newcastle Disease',
                'severity' => 'critical',
                'about' => 'A highly contagious, often fatal virus that attacks breathing, digestion, and the nervous system. It must be reported to authorities.',
                'rules' => [
                    'Greenish watery droppings' => 5,
                    'Twisted neck (torticollis)' => 5,
                    'Paralysis of legs or wings' => 4,
                    'Gasping or labored breathing' => 4,
                    'Circling or stargazing' => 4,
                    'Loss of appetite' => 3,
                    'Sudden death without prior signs' => 3,
                ],
            ],
            [
                'name' => 'Coccidiosis',
                'severity' => 'severe',
                'about' => 'An intestinal parasite spread through droppings in wet litter; most dangerous to young birds.',
                'rules' => [
                    'Bloody droppings' => 5,
                    'Pale comb' => 4,
                    'Ruffled feathers' => 3,
                    'Huddling together' => 3,
                    'Watery white droppings' => 3,
                    'Weight loss despite feeding' => 3,
                    'Lethargy or depression' => 3,
                ],
            ],
            [
                'name' => 'Fowl Cholera',
                'severity' => 'severe',
                'about' => 'A contagious bacterial disease that can kill suddenly, or cause swollen wattles and joints over time.',
                'rules' => [
                    'Greenish watery droppings' => 4,
                    'Sudden death without prior signs' => 4,
                    'Lameness or swollen joints' => 4,
                    'Swelling of the face or wattles' => 4,
                    'Lethargy or depression' => 3,
                    'Loss of appetite' => 3,
                    'Twisted neck (torticollis)' => 2,
                ],
            ],
        ],
    ],
];
