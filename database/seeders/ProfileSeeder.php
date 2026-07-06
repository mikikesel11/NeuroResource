<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Profile\Models\Profile;
use Illuminate\Database\Seeder;

class ProfileSeeder extends Seeder
{
    /**
     * Seed the single featured Profile and its Certifications.
     *
     * NOTE: This is sample/placeholder content — replace the name, biography,
     * and certifications with the real featured person's details. Idempotent:
     * safe to run repeatedly.
     */
    public function run(): void
    {
        $profile = Profile::updateOrCreate(
            ['name' => 'Alex Morgan'],
            [
                'headline' => ['en' => 'NeuroDivergence Coach & Advocate'],
                'bio' => ['en' => <<<'MD'
                    I am a Coach and Advocate working with NeuroDivergent people to build
                    Tools for Focus, Regulation, and Rest.

                    My work centers on Executive Function, Sensory Load, and gentle,
                    sustainable routines — never on masking or "fixing" who you are.
                    Everything here is made to be Calm, Clear, and Accessible.
                    MD],
            ]
        );

        // Replace existing certifications so the seed stays idempotent.
        $profile->certifications()->delete();

        $profile->certifications()->createMany([
            [
                'name' => 'Certified Autism Specialist',
                'issuer' => 'IBCCES',
                'issued_on' => '2023-03-01',
                'credential_url' => 'https://example.org/credentials/autism-specialist',
                'sort_order' => 1,
            ],
            [
                'name' => 'ADHD Coach Certification',
                'issuer' => 'ADD Coach Academy',
                'issued_on' => '2022-09-01',
                'credential_url' => 'https://example.org/credentials/adhd-coach',
                'sort_order' => 2,
            ],
            [
                'name' => 'Trauma-Informed Care Certificate',
                'issuer' => 'Crisis Prevention Institute',
                'issued_on' => '2024-01-01',
                'expires_on' => '2027-01-01',
                'credential_url' => 'https://example.org/credentials/trauma-informed',
                'sort_order' => 3,
            ],
        ]);
    }
}
