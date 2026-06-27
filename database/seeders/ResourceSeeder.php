<?php

namespace Database\Seeders;

use App\Domains\Content\Models\Tag;
use App\Domains\Resources\Models\Resource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ResourceSeeder extends Seeder
{
    /**
     * Seed sample Resources (free, email-gated, and an external link) with tags
     * and placeholder files. Sample content — replace before launch. Idempotent.
     */
    public function run(): void
    {
        $tags = collect(['Focus', 'Sensory', 'Regulation', 'Beginner Friendly'])
            ->mapWithKeys(fn (string $name) => [
                $name => Tag::updateOrCreate(
                    ['slug' => str($name)->slug()->value()],
                    ['name' => ['en' => $name]],
                ),
            ]);

        $this->makeFile('resources/focus-regulation-checklist.txt', "Focus & Regulation Checklist\n\nA gentle daily checklist. Replace with your real resource.\n");
        $this->makeFile('resources/sensory-friendly-workspace.txt', "Sensory-Friendly Workspace Guide\n\nPlaceholder content. Replace with your real resource.\n");

        $resources = [
            [
                'attrs' => [
                    'title' => ['en' => 'Focus & Regulation Checklist'],
                    'summary' => ['en' => 'A printable daily Checklist to support Focus and gentle Regulation.'],
                    'type' => 'printable',
                    'file_path' => 'resources/focus-regulation-checklist.txt',
                    'access' => 'free',
                    'published_at' => now(),
                ],
                'tags' => ['Focus', 'Regulation', 'Beginner Friendly'],
            ],
            [
                'attrs' => [
                    'title' => ['en' => 'Sensory-Friendly Workspace Guide'],
                    'summary' => ['en' => 'A short Guide to lowering Sensory Load where you work. Free with your Email.'],
                    'type' => 'pdf',
                    'file_path' => 'resources/sensory-friendly-workspace.txt',
                    'access' => 'email',
                    'published_at' => now(),
                ],
                'tags' => ['Sensory', 'Focus'],
            ],
            [
                'attrs' => [
                    'title' => ['en' => 'Curated NeuroDivergent Reading List'],
                    'summary' => ['en' => 'A hand-picked Reading List — books and articles we return to often.'],
                    'type' => 'link',
                    'external_url' => 'https://example.org/neurodivergent-reading-list',
                    'access' => 'free',
                    'published_at' => now(),
                ],
                'tags' => ['Beginner Friendly'],
            ],
        ];

        foreach ($resources as $row) {
            $resource = Resource::updateOrCreate(
                ['slug' => str($row['attrs']['title']['en'])->slug()->value()],
                $row['attrs'],
            );
            $resource->tags()->sync($tags->only($row['tags'])->pluck('id'));
        }
    }

    private function makeFile(string $path, string $contents): void
    {
        if (! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, $contents);
        }
    }
}
