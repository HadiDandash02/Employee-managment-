<?php

namespace Tests\Unit;

use App\Models\Employee;
use Tests\TestCase;

/**
 * Week 4 Day 1: unit tests for the Employee model accessors.
 *
 * Extends Laravel's TestCase (not plain PHPUnit) because the "date" cast on date_of_birth needs the app.
 * No database: the models are only built in memory, never saved.
 */
class EmployeeTest extends TestCase
{
    public function test_full_name_combines_first_and_last_name(): void
    {
        $employee = new Employee(['first_name' => 'Jane', 'last_name' => 'Doe']);

        $this->assertSame('Jane Doe', $employee->full_name);
    }

    public function test_age_is_calculated_from_date_of_birth(): void
    {
        // A fixed "today", so the test gives the same result on any day.
        $this->travelTo('2026-10-29');

        $this->assertSame(35, (new Employee(['date_of_birth' => '1990-10-30']))->age); // birthday tomorrow
        $this->assertSame(36, (new Employee(['date_of_birth' => '1990-10-29']))->age); // birthday today
        $this->assertNull((new Employee)->age); // no date of birth
    }
}
