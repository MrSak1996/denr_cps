<?php

use App\Http\Controllers\Application\ApplicationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;


Route::middleware(['auth', 'verified'])
    ->prefix('applications')
    ->group(function () {

        Route::get('/index', [ApplicationController::class, 'index'])
            ->name('applications.index');

        /*
        |--------------------------------------------------------------------------
        | CREATE - CITIZEN
        |--------------------------------------------------------------------------
        */
        Route::get('/create/citizen/{application_id?}', function (
            $application_id = null
        ) {
            return Inertia::render('applications/forms/application_form_v2', [
                'application_id' => $application_id,
                'type' => 'Individual',
                'step' => 1,
                'mode' => 'create',
            ]);
        })->name('applications.create.citizen');

        /*
        |--------------------------------------------------------------------------
        | CREATE - BUSINESS
        |--------------------------------------------------------------------------
        */
        Route::get('/create/business/{application_id?}', function (
            $application_id = null
        ) {
            return Inertia::render('applications/forms/company_application_form', [
                'application_id' => $application_id,
                'type' => 'Company',
                'step' => 1,
                'mode' => 'create',
            ]);
        })->name('applications.create.business');

        /*
        |--------------------------------------------------------------------------
        | EDIT APPLICATION
        |--------------------------------------------------------------------------
        */
        Route::get('/edit/{application_id}/{type}', function (
            Request $request,
            $application_id,
            $type
        ) {
            $view = $type === 'Company'
                ? 'applications/forms/company_application_form'
                : 'applications/forms/application_form_v2';

            return Inertia::render($view, [
                'application_id' => $application_id,
                'type' => $type,
                'step' => (int) $request->query('step', 1), // 👈 important fix
                'mode' => 'edit',
            ]);
        })->name('applications.edit');
    });
