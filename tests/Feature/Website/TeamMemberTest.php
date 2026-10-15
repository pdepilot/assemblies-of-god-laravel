<?php

use App\Models\Admin;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

function teamMemberRow(array $overrides = []): int
{
    return (int) DB::table('site_team_members')->insertGetId($overrides + [
        'site' => 'ag',
        'member_type' => 'member',
        'full_name' => 'Rev Onyenkazi Chinedu',
        'role_title' => 'Assistant Pastor',
        'bio' => 'Serving faithfully.',
        'photo_path' => 'uploads/website/team/existing-photo.jpg',
        'sort_order' => 3,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function teamEditor(): Admin
{
    return Admin::factory()->create(['role' => 'content_editor']);
}

test('editing an existing team member preserves photo, site and fields', function () {
    /** @var \Tests\TestCase $this */
    $id = teamMemberRow();

    $this->actingAs(teamEditor(), 'admin')
        ->get(route('website.team.edit', $id))
        ->assertOk()
        ->assertSee('Grid Member')
        ->assertSee('Featured Leader');

    $this->actingAs(teamEditor(), 'admin')
        ->put(route('website.team.update', $id), [
            'full_name' => 'Rev Onyenkazi Chinedu',
            'role_title' => 'Assistant Pastor',
            'bio' => 'Updated bio.',
            'member_type' => 'featured',
            'sort_order' => 3,
            'is_active' => '1',
        ])
        ->assertRedirect(route('website.team.index'))
        ->assertSessionHasNoErrors();

    $row = DB::table('site_team_members')->where('id', $id)->first();
    expect($row->site)->toBe('ag')
        ->and($row->member_type)->toBe('featured')
        ->and($row->full_name)->toBe('Rev Onyenkazi Chinedu')
        ->and($row->role_title)->toBe('Assistant Pastor')
        ->and($row->bio)->toBe('Updated bio.')
        ->and($row->photo_path)->toBe('uploads/website/team/existing-photo.jpg')
        ->and((int) $row->sort_order)->toBe(3)
        ->and((bool) $row->is_active)->toBeTrue();
});

test('saving member type Pastor returns a validation error and leaves the record unchanged', function () {
    /** @var \Tests\TestCase $this */
    $id = teamMemberRow();
    $before = (array) DB::table('site_team_members')->where('id', $id)->first();

    $this->actingAs(teamEditor(), 'admin')
        ->from(route('website.team.edit', $id))
        ->put(route('website.team.update', $id), [
            'full_name' => 'Changed Name',
            'role_title' => 'Assistant Pastor',
            'member_type' => 'Pastor',
            'sort_order' => 1,
            'is_active' => '1',
        ])
        ->assertRedirect(route('website.team.edit', $id))
        ->assertSessionHasErrors(['member_type' => 'Member type must be Featured Leader or Grid Member.']);

    expect((array) DB::table('site_team_members')->where('id', $id)->first())->toBe($before);
});

test('invalid member type is rejected on create', function (string $type) {
    /** @var \Tests\TestCase $this */
    $this->actingAs(teamEditor(), 'admin')
        ->from(route('website.team.create'))
        ->post(route('website.team.store'), [
            'full_name' => 'New Leader',
            'member_type' => $type,
        ])
        ->assertRedirect(route('website.team.create'))
        ->assertSessionHasErrors('member_type');

    expect(DB::table('site_team_members')->count())->toBe(0);
})->with(['Pastor', 'leader', 'featured; DROP', 'x']);

test('member type is normalised and defaults to member when blank', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAs(teamEditor(), 'admin')
        ->post(route('website.team.store'), [
            'full_name' => 'Deacon One',
            'member_type' => '  FEATURED ',
        ])
        ->assertRedirect(route('website.team.index'));

    $this->actingAs(teamEditor(), 'admin')
        ->post(route('website.team.store'), [
            'full_name' => 'Deacon Two',
            'member_type' => '',
        ])
        ->assertRedirect(route('website.team.index'));

    expect(DB::table('site_team_members')->where('full_name', 'Deacon One')->value('member_type'))->toBe('featured')
        ->and(DB::table('site_team_members')->where('full_name', 'Deacon Two')->value('member_type'))->toBe('member')
        ->and(DB::table('site_team_members')->pluck('site')->unique()->all())->toBe(['ag']);
});

test('uploaded photo is retained when member is updated without a new upload', function () {
    /** @var \Tests\TestCase $this */
    $this->actingAs(teamEditor(), 'admin')
        ->post(route('website.team.store'), [
            'full_name' => 'Photo Member',
            'member_type' => 'member',
            'photo' => UploadedFile::fake()->image('leader.jpg', 120, 120),
        ])
        ->assertRedirect(route('website.team.index'));

    $row = DB::table('site_team_members')->where('full_name', 'Photo Member')->first();
    $photoPath = (string) $row->photo_path;
    $file = public_path('site/'.$photoPath);

    try {
        expect($photoPath)->toStartWith('uploads/website/team/')
            ->and(is_file($file))->toBeTrue();

        $this->actingAs(teamEditor(), 'admin')
            ->put(route('website.team.update', $row->id), [
                'full_name' => 'Photo Member',
                'role_title' => 'Pastor',
                'member_type' => 'member',
            ])
            ->assertRedirect(route('website.team.index'));

        $updated = DB::table('site_team_members')->where('id', $row->id)->first();
        expect($updated->photo_path)->toBe($photoPath)
            ->and($updated->role_title)->toBe('Pastor')
            ->and(is_file($file))->toBeTrue();
    } finally {
        if (is_file($file)) {
            @unlink($file);
        }
    }
});

test('sdtg team members cannot be edited or reassigned through the AG admin', function () {
    /** @var \Tests\TestCase $this */
    $id = teamMemberRow(['site' => 'sdtg', 'full_name' => 'SDTG Lead', 'photo_path' => null]);
    $before = (array) DB::table('site_team_members')->where('id', $id)->first();

    $this->actingAs(teamEditor(), 'admin')
        ->get(route('website.team.edit', $id))
        ->assertRedirect(route('website.team.index'))
        ->assertSessionHasErrors('member');

    $this->actingAs(teamEditor(), 'admin')
        ->put(route('website.team.update', $id), [
            'full_name' => 'Hijacked',
            'member_type' => 'featured',
        ])
        ->assertRedirect(route('website.team.index'))
        ->assertSessionHasErrors('member');

    expect((array) DB::table('site_team_members')->where('id', $id)->first())->toBe($before);

    $this->actingAs(teamEditor(), 'admin')
        ->get(route('website.team.index'))
        ->assertOk()
        ->assertDontSee('SDTG Lead');
});

test('write service keeps sdtg members as grid members and rejects cross-site updates', function () {
    $service = app(\App\Services\Website\TeamSectionWriteService::class);

    $sdtg = $service->saveMember(['site' => 'sdtg', 'full_name' => 'SDTG Member', 'member_type' => 'featured']);
    expect($sdtg['member_type'])->toBe('member')
        ->and($sdtg['site'])->toBe('sdtg');

    expect(fn () => $service->saveMember([
        'id' => $sdtg['id'],
        'site' => 'ag',
        'full_name' => 'Moved',
    ]))->toThrow(InvalidArgumentException::class, 'Team member not found.');

    expect(fn () => $service->saveMember([
        'site' => 'ag',
        'full_name' => 'Bad Type',
        'member_type' => 'Pastor',
    ]))->toThrow(InvalidArgumentException::class, 'Member type must be Featured Leader or Grid Member.');

    expect(DB::table('site_team_members')->where('id', $sdtg['id'])->value('full_name'))->toBe('SDTG Member');
});
