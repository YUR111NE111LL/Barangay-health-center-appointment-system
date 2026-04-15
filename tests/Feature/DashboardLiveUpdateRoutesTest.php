<?php

use Illuminate\Support\Facades\Route;

it('registers backend dashboard live JSON routes', function (): void {
    expect(Route::has('backend.dashboard.live.summary'))->toBeTrue();
    expect(Route::has('backend.dashboard.live.admin'))->toBeTrue();
    expect(Route::has('backend.dashboard.live.nurse'))->toBeTrue();
    expect(Route::has('backend.dashboard.live.staff'))->toBeTrue();
});
