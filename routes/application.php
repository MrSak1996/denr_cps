<?php

use App\Http\Controllers\Application\ApplicationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])
    ->prefix('applications')
    ->group(function () {

        Route::get('/index', [ApplicationController::class, 'index'])
            ->name('applications.index');

        Route::get('/create/business', function () {
            return Inertia::render('applications/forms/company_application_form');
        })->name('applications.create.business');

        // ✅ SINGLE CLEAN DYNAMIC ROUTE (CITIZEN)
        Route::get('/create/citizen/{application_id?}/{type?}/{step?}', function (
            $application_id = null,
            $type = 'individual',
            $step = 1
        ) {
            return Inertia::render('applications/forms/application_form_v2', [
                'application_id' => $application_id,
                'type' => $type,
                'step' => (int) $step,
            ]);
        })->name('applications.create.citizen');

       
    });
