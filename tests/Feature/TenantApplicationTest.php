<?php

use Illuminate\Support\Facades\Route;

it('registers tenant application routes', function (): void {
    expect(Route::has('landing'))->toBeTrue();
    expect(Route::has('tenant-applications.create'))->toBeTrue();
    expect(Route::has('tenant-applications.store'))->toBeTrue();
    expect(Route::has('super-admin.tenant-applications.index'))->toBeTrue();
    expect(Route::has('super-admin.tenant-applications.show'))->toBeTrue();
    expect(Route::has('super-admin.tenant-applications.approve'))->toBeTrue();
    expect(Route::has('super-admin.tenant-applications.reject'))->toBeTrue();
});
