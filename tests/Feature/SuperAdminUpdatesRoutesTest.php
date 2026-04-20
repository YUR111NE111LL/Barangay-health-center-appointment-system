<?php

use Illuminate\Support\Facades\Route;

it('registers super admin global update routes', function (): void {
    expect(Route::has('super-admin.updates.index'))->toBeTrue();
    expect(Route::has('super-admin.updates.create'))->toBeTrue();
    expect(Route::has('super-admin.updates.store'))->toBeTrue();
    expect(Route::has('super-admin.updates.edit'))->toBeTrue();
    expect(Route::has('super-admin.updates.update'))->toBeTrue();
    expect(Route::has('super-admin.updates.destroy'))->toBeTrue();
    expect(Route::has('super-admin.updates.sync-github'))->toBeTrue();
});
