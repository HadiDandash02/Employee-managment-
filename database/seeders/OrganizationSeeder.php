<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Skill;
use Illuminate\Database\Seeder;

/**
 * Sample companies → branches → departments, and skills.
 *
 * Uses firstOrCreate, so it is safe to run again and never touches employees:
 *   php artisan db:seed --class=OrganizationSeeder
 */
class OrganizationSeeder extends Seeder
{
    private const STRUCTURE = [
        'Qualizone Holding' => [
            'Head Office' => ['city' => 'Beirut', 'departments' => ['Engineering', 'Finance', 'HR', 'Marketing']],
            'Tripoli Branch' => ['city' => 'Tripoli', 'departments' => ['Sales', 'Support']],
        ],
        'Qualizone Services' => [
            'Main Branch' => ['city' => 'Riyadh', 'departments' => ['Sales', 'Operations']],
        ],
    ];

    private const SKILLS = ['PHP', 'Laravel', 'JavaScript', 'SQL', 'Communication', 'Leadership', 'Excel', 'Customer Service'];

    public function run(): void
    {
        foreach (self::STRUCTURE as $companyName => $branches) {
            $company = Company::firstOrCreate(['name' => $companyName]);

            foreach ($branches as $branchName => $branch) {
                $branchModel = $company->branches()->firstOrCreate(['name' => $branchName], ['city' => $branch['city']]);

                foreach ($branch['departments'] as $departmentName) {
                    $branchModel->departments()->firstOrCreate(['name' => $departmentName]);
                }
            }
        }

        foreach (self::SKILLS as $skill) {
            Skill::firstOrCreate(['name' => $skill]);
        }
    }
}
