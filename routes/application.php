<?php

use App\Http\Controllers\Application\ApplicationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])
    ->prefix('applications')
    ->group(function () {

        Route::get('/index', [ApplicationController::class, 'index'])
            ->name('applications.index');

        Route::get('/create/business/{application_id?}/{type?}/{step?}', function (
            $application_id = null,
            $type = 'Company',
            $step = 1
        ) {
            return Inertia::render('applications/forms/company_application_form', [
                'application_id' => $application_id,
                'type' => $type,
                'step' => (int) $step,
            ]);
        })->name('applications.create.business');

        // ✅ SINGLE CLEAN DYNAMIC ROUTE (CITIZEN)
        Route::get('/create/citizen/{application_id?}/{type?}/{step?}', function (
            $application_id = null,
            $type = 'Individual',
            $step = 1
        ) {
            return Inertia::render('applications/forms/application_form_v2', [
                'application_id' => $application_id,
                'type' => $type,
                'step' => (int) $step,
            ]);
        })->name('applications.create.citizen');


        // EDIT APPLICATION
        Route::get('/edit/{application_id}/{type}', function (
            $application_id,
            $type
        ) {
            $step = 4; // default start step (you can later compute last saved step)

            $view = $type === 'Company'
                ? 'applications/forms/company_application_form'
                : 'applications/forms/application_form_v2';

            return Inertia::render($view, [
                'application_id' => $application_id,
                'type' => $type,
                'step' => $step,
            ]);
        })->name('applications.edit');
    });
