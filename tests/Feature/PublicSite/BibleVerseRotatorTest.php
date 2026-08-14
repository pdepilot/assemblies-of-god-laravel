<?php

use App\Services\PublicSite\BibleVerseReadService;

test('bible verse service returns random kjv verses from full bible dataset', function () {
    $service = app(BibleVerseReadService::class);

    $first = $service->randomVerses(8);
    $second = $service->randomVerses(8);

    expect($first)->toHaveCount(8);
    expect($second)->toHaveCount(8);

    foreach ($first as $verse) {
        expect($verse)->toHaveKeys(['quote', 'reference']);
        expect($verse['quote'])->not->toBeEmpty();
        expect($verse['reference'])->toContain('(KJV)');
        expect($verse['quote'])->not->toContain('[');
    }

    $firstRefs = collect($first)->pluck('reference')->all();
    $secondRefs = collect($second)->pluck('reference')->all();
    expect($firstRefs)->not->toEqual($secondRefs);
});

test('homepage scripture rotator uses random bible verses', function () {
    $html = $this->get(route('public.home'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('id="agScriptureRotator"');
    expect($html)->toContain('ag-scripture-card');
    expect($html)->toContain('(KJV)');
});
