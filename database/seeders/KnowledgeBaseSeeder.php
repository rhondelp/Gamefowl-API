<?php

namespace Database\Seeders;

use App\Models\Disease;
use App\Models\DiseaseSymptomRule;
use App\Models\Recommendation;
use App\Models\Symptom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class KnowledgeBaseSeeder extends Seeder
{
    /**
     * Starter knowledge base built from general, publicly documented
     * poultry veterinary knowledge. Intended as realistic seed data for
     * engine testing — not a substitute for professional diagnosis.
     */
    public function run(): void
    {
        $this->resetKnowledgeBase();

        $symptoms = $this->seedSymptoms();
        $diseases = $this->seedDiseases();
        $recommendations = $this->seedRecommendations();

        $this->seedRules($diseases, $symptoms);
        $this->seedDiseaseRecommendations($diseases, $recommendations);
    }

    private function resetKnowledgeBase(): void
    {
        DiseaseSymptomRule::query()->delete();
        DB::table('disease_recommendations')->delete();
        Symptom::query()->delete();
        Recommendation::query()->delete();
        Disease::query()->delete();
    }

    /**
     * @return array<string, Symptom>
     */
    private function seedSymptoms(): array
    {
        $symptoms = [
            ['key' => 'nasal_discharge', 'name' => 'Nasal discharge (runny nose)', 'category' => 'respiratory', 'severity' => 'moderate', 'description' => 'Clear or foul-smelling fluid dripping from the nostrils.'],
            ['key' => 'sneezing', 'name' => 'Sneezing', 'category' => 'respiratory', 'severity' => 'mild', 'description' => 'Frequent sneezing or head shaking.'],
            ['key' => 'coughing', 'name' => 'Coughing', 'category' => 'respiratory', 'severity' => 'moderate', 'description' => 'Persistent cough or clicking sounds when breathing.'],
            ['key' => 'gasping', 'name' => 'Gasping or labored breathing', 'category' => 'respiratory', 'severity' => 'severe', 'description' => 'Open-mouth breathing with stretched neck.'],
            ['key' => 'rales', 'name' => 'Wet rales (rattling breath sounds)', 'category' => 'respiratory', 'severity' => 'moderate', 'description' => 'Rattling or gurgling sounds coming from the windpipe.'],
            ['key' => 'foamy_eyes', 'name' => 'Foamy eye discharge', 'category' => 'respiratory', 'severity' => 'moderate', 'description' => 'Bubbles or foam forming at the corner of the eyes; eyes may stick shut.'],
            ['key' => 'facial_swelling', 'name' => 'Swelling of the face or wattles', 'category' => 'physical', 'severity' => 'moderate', 'description' => 'Puffy, swollen face or enlarged wattles, often on one side.'],
            ['key' => 'comb_scabs', 'name' => 'Wart-like scabs on comb or wattles', 'category' => 'physical', 'severity' => 'moderate', 'description' => 'Dry, crusty nodules or scabs on unfeathered skin that later flake off.'],
            ['key' => 'mouth_patches', 'name' => 'Yellow patches inside the mouth', 'category' => 'physical', 'severity' => 'severe', 'description' => 'Cheesy or yellowish patches on the tongue, mouth, or upper throat.'],
            ['key' => 'pale_comb', 'name' => 'Pale comb', 'category' => 'physical', 'severity' => 'moderate', 'description' => 'Comb losing its bright red color and turning pale or whitish.'],
            ['key' => 'ruffled_feathers', 'name' => 'Ruffled feathers', 'category' => 'physical', 'severity' => 'mild', 'description' => 'Feathers standing up, dull, or unkempt appearance.'],
            ['key' => 'weight_loss', 'name' => 'Weight loss despite feeding', 'category' => 'physical', 'severity' => 'moderate', 'description' => 'Noticeable keel-bone prominence and muscle wasting.'],
            ['key' => 'lameness', 'name' => 'Lameness or swollen joints', 'category' => 'physical', 'severity' => 'moderate', 'description' => 'Difficulty walking, limping, or hot swollen foot/leg joints.'],
            ['key' => 'greenish_droppings', 'name' => 'Greenish watery droppings', 'category' => 'digestive', 'severity' => 'severe', 'description' => 'Bright green, loose droppings caused by lack of feed intake and infection.'],
            ['key' => 'bloody_droppings', 'name' => 'Bloody droppings', 'category' => 'digestive', 'severity' => 'severe', 'description' => 'Red-tinged or bloody loose droppings, sometimes with tissue.'],
            ['key' => 'watery_white_droppings', 'name' => 'Watery white droppings', 'category' => 'digestive', 'severity' => 'moderate', 'description' => 'Whitish, watery feces pasted around the vent.'],
            ['key' => 'loss_of_appetite', 'name' => 'Loss of appetite', 'category' => 'digestive', 'severity' => 'moderate', 'description' => 'Refusing feed or eating far less than usual.'],
            ['key' => 'twisted_neck', 'name' => 'Twisted neck (torticollis)', 'category' => 'neurological', 'severity' => 'severe', 'description' => 'Neck twisted sideways or upward; head pulled toward the back.'],
            ['key' => 'paralysis', 'name' => 'Paralysis of legs or wings', 'category' => 'neurological', 'severity' => 'severe', 'description' => 'Weakness or inability to move legs and/or wings.'],
            ['key' => 'circling', 'name' => 'Circling or stargazing', 'category' => 'neurological', 'severity' => 'severe', 'description' => 'Walking in circles or looking skyward uncontrollably.'],
            ['key' => 'lethargy', 'name' => 'Lethargy or depression', 'category' => 'behavioral', 'severity' => 'moderate', 'description' => 'Unusually quiet, sleepy, sitting fluffed up and inactive.'],
            ['key' => 'sudden_death', 'name' => 'Sudden death without prior signs', 'category' => 'behavioral', 'severity' => 'severe', 'description' => 'Bird found dead with no previously observed symptoms.'],
            ['key' => 'huddling', 'name' => 'Huddling together', 'category' => 'behavioral', 'severity' => 'mild', 'description' => 'Birds clustering tightly together, often near a heat source.'],
        ];

        return collect($symptoms)->mapWithKeys(function (array $row) {
            return [$row['key'] => Symptom::create([
                'name' => $row['name'],
                'description' => $row['description'],
                'category' => $row['category'],
                'severity' => $row['severity'],
            ])];
        })->all();
    }

    /**
     * @return array<string, Disease>
     */
    private function seedDiseases(): array
    {
        $diseases = [
            [
                'key' => 'coryza',
                'name' => 'Infectious Coryza',
                'description' => 'Acute, highly contagious bacterial respiratory disease of chickens caused by Avibacterium paragallinarum. Spreads quickly within a flock through airborne droplets and contaminated water.',
                'severity' => 'moderate',
                'general_info' => 'Commonly called "cold" or "sipon" in gamefowl raising. Affects the upper respiratory tract; morbidity is high but mortality is usually low in adult birds unless complicated. Recovered birds can remain carriers.',
                'recommended_action' => 'Isolate affected birds immediately in a warm, dry, well-ventilated area. Provide clean water with electrolytes. Consult a veterinarian for appropriate antibiotic treatment (e.g., sulfa-based drugs) and follow the full course.',
                'prevention_info' => 'Maintain good ventilation and low ammonia levels. Avoid mixing birds of different ages or sources. Practice all-in/all-out management and disinfect housing between batches.',
            ],
            [
                'key' => 'fowl_pox',
                'name' => 'Fowl Pox',
                'description' => 'Slow-spreading viral disease transmitted mainly by mosquitoes and wound contact. The dry form causes wart-like scabs on the comb, face, and wattles; the wet form causes cheesy patches in the mouth and throat that can obstruct breathing.',
                'severity' => 'moderate',
                'general_info' => 'The dry form is rarely fatal and birds recover within weeks; the wet form is more serious because lesions interfere with eating and breathing. Lesions may temporarily sideline fighting condition.',
                'recommended_action' => 'Apply povidone-iodine or another mild antiseptic to visible scabs to prevent secondary infection. Soften mouth lesions if feeding is affected. Isolate affected birds and control mosquitoes around the pen.',
                'prevention_info' => 'Follow a fowl pox vaccination program (commonly wing-web method). Reduce mosquito breeding sites such as standing water near the pens.',
            ],
            [
                'key' => 'newcastle',
                'name' => 'Newcastle Disease',
                'description' => 'Highly contagious and often fatal viral disease affecting the respiratory tract, digestive system, and nervous system. Spreads rapidly through air, droppings, and contaminated equipment.',
                'severity' => 'critical',
                'general_info' => 'One of the most devastating poultry diseases worldwide. Signs range from gasping and greenish watery droppings to twisted neck, paralysis, and sudden death. Survivors of the nervous form rarely return to normal performance.',
                'recommended_action' => 'Isolate suspected birds at once and stop all movement of birds, equipment, and visitors in and out of the farm. Contact a licensed veterinarian immediately — there is no treatment for Newcastle disease.',
                'prevention_info' => 'Strictly follow the recommended vaccination schedule for Newcastle disease. Enforce strict biosecurity: disinfect footwear, equipment, and vehicles; keep wild birds away from the pens.',
                'vet_warning' => 'Newcastle disease is a notifiable disease. Report suspected outbreaks to your veterinarian and the Bureau of Animal Industry immediately. Do not sell or transfer suspect birds.',
            ],
            [
                'key' => 'coccidiosis',
                'name' => 'Coccidiosis',
                'description' => 'Intestinal parasitic disease caused by Eimeria organisms that damage the gut lining. Spread through droppings in wet litter; most damaging to young birds under stress.',
                'severity' => 'severe',
                'general_info' => 'Bloody or watery droppings, ruffled feathers, huddling, and rapid weight loss are typical. Outbreaks follow wet litter, overcrowding, or dirty water. Heavy infections can kill quickly, especially in chicks and stag-age birds.',
                'recommended_action' => 'Start anticoccidial medication through the drinking water as advised by a veterinarian. Replace wet litter with dry bedding and provide vitamins/electrolytes to aid recovery.',
                'prevention_info' => 'Keep litter dry and avoid overcrowding. Use coccidiostats in feed or follow a coccidiosis vaccination program. Regularly clean drinkers and never let water spill into bedding.',
            ],
            [
                'key' => 'fowl_cholera',
                'name' => 'Fowl Cholera',
                'description' => 'Contagious bacterial disease caused by Pasteurella multocida. The acute form kills birds suddenly with few signs; the chronic form causes swollen wattles, joint infections, and labored breathing.',
                'severity' => 'severe',
                'general_info' => 'Rodents, wild birds, and contaminated water are common sources. Acute outbreaks show greenish-yellow diarrhea and unexpected deaths in the best-conditioned birds. Recovered birds frequently become carriers.',
                'recommended_action' => 'Remove visibly sick birds from the flock and consult a veterinarian promptly for antibiotic sensitivity testing and treatment. Clean and disinfect the entire premises including feeders and drinkers.',
                'prevention_info' => 'Implement rodent control and exclude wild birds. Provide only clean, treated drinking water. Vaccination may be considered where fowl cholera is a persistent problem.',
                'vet_warning' => 'Fowl cholera can recur in the same facility through carrier birds. Seek veterinary guidance before restocking after an outbreak.',
            ],
        ];

        return collect($diseases)->mapWithKeys(function (array $row) {
            return [$row['key'] => Disease::create(Arr::only($row, [
                'name', 'description', 'severity', 'general_info', 'recommended_action', 'prevention_info', 'vet_warning',
            ]))];
        })->all();
    }

    private function seedRecommendations(): array
    {
        $recommendations = [
            ['key' => 'isolate', 'title' => 'Isolate affected birds immediately', 'content' => 'Move sick birds to a separate isolation pen away from the main flock to slow the spread of disease. Handle healthy birds first and sick birds last during chores.', 'category' => 'isolation'],
            ['key' => 'electrolytes', 'title' => 'Provide clean water with electrolytes', 'content' => 'Offer fresh drinking water supplemented with electrolytes and vitamins to help birds rehydrate and recover, especially those with diarrhea or fever.', 'category' => 'nutrition'],
            ['key' => 'ventilation', 'title' => 'Improve coop ventilation and reduce ammonia', 'content' => 'Ensure good airflow without direct drafts. Ammonia buildup irritates the respiratory tract and worsens respiratory diseases.', 'category' => 'environment'],
            ['key' => 'disinfect', 'title' => 'Disinfect housing, feeders, and drinkers', 'content' => 'Clean and disinfect all equipment and the surrounding area. Many pathogens survive in manure, shared waterers, and dirty equipment.', 'category' => 'hygiene'],
            ['key' => 'dry_litter', 'title' => 'Keep litter dry and replace soiled bedding', 'content' => 'Wet litter promotes parasite oocysts and bacterial growth. Remove damp spots daily and top up with clean, dry bedding.', 'category' => 'hygiene'],
            ['key' => 'vaccination', 'title' => 'Follow the recommended vaccination schedule', 'content' => 'Vaccinate against major viral diseases such as Newcastle disease and fowl pox according to a veterinarian-approved program for your area.', 'category' => 'vaccination'],
            ['key' => 'consult_vet', 'title' => 'Consult a licensed veterinarian before medicating', 'content' => 'Do not give antibiotics or other medicines without proper diagnosis and dosage guidance. Wrong drugs or doses waste money and worsen resistance.', 'category' => 'medication'],
            ['key' => 'antiseptic_lesions', 'title' => 'Apply antiseptic to visible lesions', 'content' => 'For birds with scabs or wounds, clean the area and apply povidone-iodine to prevent secondary bacterial infection.', 'category' => 'medication'],
            ['key' => 'daily_monitoring', 'title' => 'Monitor the flock twice daily and record new cases', 'content' => 'Check each bird morning and evening for changes in appetite, droppings, posture, and breathing. Early detection greatly improves outcomes.', 'category' => 'monitoring'],
            ['key' => 'mosquito_control', 'title' => 'Reduce mosquito breeding sites around pens', 'content' => 'Drain or cover standing water near the housing area. Mosquitoes transmit fowl pox between birds.', 'category' => 'environment'],
        ];

        return collect($recommendations)->mapWithKeys(function (array $row) {
            return [$row['key'] => Recommendation::create(Arr::only($row, ['title', 'content', 'category']))];
        })->all();
    }

    private function seedRules(array $diseases, array $symptoms): void
    {
        $rules = [
            'coryza' => [
                'nasal_discharge' => 5,
                'facial_swelling' => 5,
                'foamy_eyes' => 4,
                'sneezing' => 3,
                'rales' => 2,
                'loss_of_appetite' => 2,
            ],
            'fowl_pox' => [
                'comb_scabs' => 5,
                'mouth_patches' => 4,
                'gasping' => 3,
                'weight_loss' => 2,
                'lethargy' => 2,
            ],
            'newcastle' => [
                'greenish_droppings' => 5,
                'twisted_neck' => 5,
                'paralysis' => 4,
                'gasping' => 4,
                'circling' => 4,
                'loss_of_appetite' => 3,
                'sudden_death' => 3,
            ],
            'coccidiosis' => [
                'bloody_droppings' => 5,
                'pale_comb' => 4,
                'ruffled_feathers' => 3,
                'huddling' => 3,
                'watery_white_droppings' => 3,
                'weight_loss' => 3,
                'lethargy' => 3,
            ],
            'fowl_cholera' => [
                'greenish_droppings' => 4,
                'sudden_death' => 4,
                'lameness' => 4,
                'facial_swelling' => 4,
                'lethargy' => 3,
                'loss_of_appetite' => 3,
                'twisted_neck' => 2,
            ],
        ];

        foreach ($rules as $diseaseKey => $symptomWeights) {
            $disease = $diseases[$diseaseKey] ?? null;
            if (! $disease instanceof Disease) {
                continue;
            }

            foreach ($symptomWeights as $symptomKey => $weight) {
                DiseaseSymptomRule::create([
                    'disease_id' => $disease->id,
                    'symptom_id' => $symptoms[$symptomKey]->id,
                    'weight' => $weight,
                ]);
            }
        }
    }

    private function seedDiseaseRecommendations(array $diseases, array $recommendations): void
    {
        $links = [
            'coryza' => ['isolate', 'electrolytes', 'ventilation', 'daily_monitoring'],
            'fowl_pox' => ['antiseptic_lesions', 'mosquito_control', 'isolate', 'daily_monitoring'],
            'newcastle' => ['isolate', 'consult_vet', 'vaccination', 'disinfect', 'daily_monitoring'],
            'coccidiosis' => ['dry_litter', 'electrolytes', 'consult_vet', 'daily_monitoring'],
            'fowl_cholera' => ['isolate', 'disinfect', 'consult_vet', 'daily_monitoring'],
        ];

        foreach ($links as $diseaseKey => $recommendationKeys) {
            $disease = $diseases[$diseaseKey] ?? null;
            if (! $disease instanceof Disease) {
                continue;
            }

            foreach ($recommendationKeys as $key) {
                $disease->recommendations()->attach($recommendations[$key]->id);
            }
        }
    }
}
