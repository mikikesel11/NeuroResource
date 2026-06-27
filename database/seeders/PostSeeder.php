<?php

namespace Database\Seeders;

use App\Domains\Content\Models\Post;
use App\Domains\Content\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Seed sample published blog posts with tags and an author. Sample content —
     * replace before launch. Idempotent.
     */
    public function run(): void
    {
        $author = User::query()->first() ?? User::factory()->create();

        $tags = collect(['Focus', 'Sensory', 'Regulation', 'Beginner Friendly'])
            ->mapWithKeys(fn (string $name) => [
                $name => Tag::updateOrCreate(
                    ['slug' => str($name)->slug()->value()],
                    ['name' => ['en' => $name]],
                ),
            ]);

        $posts = [
            [
                'title' => 'Working With Your Brain, Not Against It',
                'excerpt' => 'Small shifts that respect how your Attention actually works — instead of fighting it.',
                'reading_minutes' => 4,
                'tags' => ['Focus', 'Regulation', 'Beginner Friendly'],
                'body' => <<<'MD'
                    Most productivity advice assumes one kind of Brain. This is not that.

                    The goal is **Regulation, not force**. When you work *with* your
                    Attention, the same task costs less Energy.

                    - Start with the smallest honest step.
                    - Make the next action **visible**.
                    - Build in Rest before you need it.

                    None of this is about doing more. It is about doing what matters with
                    less friction.
                    MD,
            ],
            [
                'title' => 'Lowering Sensory Load at Home',
                'excerpt' => 'Practical, low-cost ways to make your space calmer for a NeuroDivergent nervous system.',
                'reading_minutes' => 5,
                'tags' => ['Sensory'],
                'body' => <<<'MD'
                    Your environment is doing more than you think. Light, sound, and clutter
                    all add to your **Sensory Load**.

                    A few changes go a long way:

                    - Swap harsh overhead Light for softer, warmer lamps.
                    - Keep a pair of Earplugs within reach.
                    - Give frequently-used items a single, predictable home.

                    Calmer surroundings free up Energy for everything else.
                    MD,
            ],
            [
                'title' => 'What Executive Function Actually Means',
                'excerpt' => 'A plain-language look at the mental Tools we use to start, switch, and finish.',
                'reading_minutes' => 6,
                'tags' => ['Focus', 'Regulation'],
                'body' => <<<'MD'
                    **Executive Function** is the set of mental Tools that help you start a
                    task, switch between tasks, and see one through to the end.

                    When those Tools are stretched thin, the problem is rarely Motivation —
                    it is Capacity. Naming that difference is the first kindness.

                    The rest of this Blog is full of small, concrete supports for exactly
                    these moments.
                    MD,
            ],
        ];

        foreach ($posts as $i => $row) {
            $post = Post::updateOrCreate(
                ['slug' => str($row['title'])->slug()->value()],
                [
                    'title' => ['en' => $row['title']],
                    'excerpt' => ['en' => $row['excerpt']],
                    'body' => ['en' => $row['body']],
                    'status' => 'published',
                    'reading_minutes' => $row['reading_minutes'],
                    'author_id' => $author->id,
                    'published_at' => now()->subDays(count($posts) - $i),
                ],
            );
            $post->tags()->sync($tags->only($row['tags'])->pluck('id'));
        }
    }
}
