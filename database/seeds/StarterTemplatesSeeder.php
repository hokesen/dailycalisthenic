<?php

namespace Database\Seeders;

use App\Models\SessionTemplate;
use App\Repositories\ExerciseRepository;
use Illuminate\Database\Seeder;

class StarterTemplatesSeeder extends Seeder
{
    public function __construct(
        protected ExerciseRepository $exerciseRepository
    ) {}

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only seed if no system templates exist
        if (SessionTemplate::whereNull('user_id')->count() > 0) {
            $this->command->info('System templates already exist. Skipping...');

            return;
        }

        $this->createQuickMorningPractice();
        $this->createPullDayBasics();
        $this->createCoreAndBalance();
        $this->createStrengthBuilder();

        $this->command->info('Created 4 starter templates');
    }

    protected function createQuickMorningPractice(): void
    {
        $template = SessionTemplate::create([
            'user_id' => null,
            'name' => 'Quick Morning Practice',
            'default_rest_seconds' => 15,
            'is_public' => true,
        ]);

        $exercises = [
            ['name' => 'Cat-Cow Stretch', 'duration' => 45, 'rest' => 10],
            ['name' => 'Squat', 'duration' => 60, 'rest' => 15],
            ['name' => 'Wall Push-Up', 'duration' => 60, 'rest' => 15],
            ['name' => 'Plank', 'duration' => 30, 'rest' => 15],
            ['name' => 'Glute Bridge', 'duration' => 60, 'rest' => 15],
            ['name' => 'Child\'s Pose', 'duration' => 45, 'rest' => 0],
        ];

        $this->attachExercises($template, $exercises);
    }

    protected function createPullDayBasics(): void
    {
        $template = SessionTemplate::create([
            'user_id' => null,
            'name' => 'Pull Day Basics',
            'default_rest_seconds' => 30,
            'is_public' => true,
        ]);

        $exercises = [
            ['name' => 'Wrist Mobility', 'duration' => 45, 'rest' => 10],
            ['name' => 'Dead Hang', 'duration' => 30, 'rest' => 30],
            ['name' => 'Scapular Pull', 'duration' => 45, 'rest' => 30],
            ['name' => 'Australian Pull-Up', 'duration' => 60, 'rest' => 30],
            ['name' => 'Negative Pull-Up', 'duration' => 60, 'rest' => 30],
            ['name' => 'Dead Hang', 'duration' => 30, 'rest' => 0],
        ];

        $this->attachExercises($template, $exercises);
    }

    protected function createCoreAndBalance(): void
    {
        $template = SessionTemplate::create([
            'user_id' => null,
            'name' => 'Core & Balance',
            'default_rest_seconds' => 20,
            'is_public' => true,
        ]);

        $exercises = [
            ['name' => 'Cat-Cow Stretch', 'duration' => 45, 'rest' => 10],
            ['name' => 'Dead Bug', 'duration' => 60, 'rest' => 20],
            ['name' => 'Plank', 'duration' => 45, 'rest' => 20],
            ['name' => 'Side Plank', 'duration' => 30, 'rest' => 20],
            ['name' => 'Hollow Body Hold', 'duration' => 30, 'rest' => 20],
            ['name' => 'Arch Hold', 'duration' => 30, 'rest' => 20],
            ['name' => 'Lying Leg Raise', 'duration' => 60, 'rest' => 20],
            ['name' => 'Child\'s Pose', 'duration' => 45, 'rest' => 0],
        ];

        $this->attachExercises($template, $exercises);
    }

    protected function createStrengthBuilder(): void
    {
        $template = SessionTemplate::create([
            'user_id' => null,
            'name' => 'Strength Builder',
            'default_rest_seconds' => 30,
            'is_public' => true,
        ]);

        $exercises = [
            ['name' => 'Wrist Mobility', 'duration' => 45, 'rest' => 10],
            ['name' => 'Push Ups', 'duration' => 60, 'rest' => 30],
            ['name' => 'Pull-Up', 'duration' => 60, 'rest' => 30],
            ['name' => 'Dip', 'duration' => 60, 'rest' => 30],
            ['name' => 'Bulgarian Split Squat', 'duration' => 60, 'rest' => 30],
            ['name' => 'Plank', 'duration' => 60, 'rest' => 20],
            ['name' => 'Hanging Leg Raise', 'duration' => 45, 'rest' => 20],
            ['name' => 'Bridge Hold', 'duration' => 45, 'rest' => 0],
        ];

        $this->attachExercises($template, $exercises);
    }

    protected function attachExercises(SessionTemplate $template, array $exercises): void
    {
        foreach ($exercises as $index => $exerciseData) {
            $exercise = $this->exerciseRepository->findByName($exerciseData['name']);

            if (! $exercise) {
                $this->command->warn("Exercise not found: {$exerciseData['name']}");

                continue;
            }

            // Materialize default exercise if needed
            if ($exercise->id < 0) {
                $exercise = $this->exerciseRepository->materialize($exercise->id);
            }

            $template->exercises()->attach($exercise->id, [
                'order' => $index + 1,
                'duration_seconds' => $exerciseData['duration'],
                'rest_after_seconds' => $exerciseData['rest'],
            ]);
        }
    }
}
