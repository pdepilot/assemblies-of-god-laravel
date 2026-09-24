<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit/CommunicationHub');

pest()->extend(TestCase::class)
    ->in('Unit/SundaySchool');

pest()->extend(TestCase::class)
    ->in('Unit/Portal');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

use Illuminate\Http\UploadedFile;

function memberAdminFormPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'full_name' => 'Jane Doe',
        'phone' => '08012345678',
        'phone_alt' => '08012345679',
        'email' => 'jane.doe@example.com',
        'gender' => 'female',
        'date_of_birth' => '1990-01-15',
        'marital_status' => 'single',
        'address_line1' => '12 Church Road',
        'address_line2' => 'Off Wetheral Road',
        'city' => 'Owerri',
        'state' => 'Imo',
        'postal_code' => '460001',
        'country' => 'Nigeria',
        'occupation' => 'Teacher',
        'department' => 'Member',
        'status' => 'active',
        'joined_date' => '2026-07-20',
        'photo' => UploadedFile::fake()->image('member.jpg', 200, 200),
    ], $overrides);
}

function something()
{
    // ..
}
