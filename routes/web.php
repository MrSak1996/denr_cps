<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

// Controllers
use App\Http\Controllers\Application\ApplicationController;
use App\Http\Controllers\Assessment\AssessmentController;
use App\Http\Controllers\Chainsaw\ChainsawController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Reports\PDFController;
use App\Http\Controllers\Dashboard\{
    PENROController,
    TSDController,
    FUSController,
    ARDTSController,
    RegionalExecutiveController,
    RPSChiefDashboardController
};
use App\Http\Controllers\UserManagement\UserController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/', fn() => Inertia::render('Welcome'))->name('home');

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARDS
    |--------------------------------------------------------------------------
    */
    Route::prefix('dashboard')->group(function () {
        Route::get('/', fn() => Inertia::render('Dashboard'))->name('dashboard');

        Route::get('rps-chief', fn() => Inertia::render('RPSChiefDashboard'))->name('rps.chief.dashboard');
        Route::get('cenro', fn() => Inertia::render('CENRODashboard'))->name('cenro.dashboard');
        Route::get('penro', fn() => Inertia::render('PENRODashboard'))->name('penro.dashboard');

        Route::get('penro-technical', fn() => Inertia::render('PENROTechnicalDashboard'))->name('penro.technical.dashboard');
        Route::get('penro-rps-chief', fn() => Inertia::render('PENRORPSChiefDashboard'))->name('penro.rps.chief.dashboard');
        Route::get('penro-tsd-chief', fn() => Inertia::render('PENROTSDChiefDashboard'))->name('penro.tsd.chief.dashboard');

        Route::get('tsd-chief', fn() => Inertia::render('TSDChiefDashboard'))->name('tsd.chief.dashboard');
        Route::get('rts', fn() => Inertia::render('RegionalTechnicalStaff'))->name('rts.dashboard');
        Route::get('fus', fn() => Inertia::render('FUSDashboard'))->name('fus.dashboard');
        Route::get('lpdd-chief', fn() => Inertia::render('LPDDChiefDashboard'))->name('lpdd.chief.dashboard');
        Route::get('ardts', fn() => Inertia::render('ARDTSDashboard'))->name('ardts.dashboard');
        Route::get('regional-executive', fn() => Inertia::render('RegionalExecutiveDashboard'))->name('regional.executive.dashboard');
        Route::get('rps-staff', fn() => Inertia::render('RPSStaffDashboard'))->name('rps.staff.dashboard');
    });

    /*
    |--------------------------------------------------------------------------
    | APPLICATION VIEWS
    |--------------------------------------------------------------------------
    */
    Route::prefix('applications')->group(function () {

        Route::get('pending_application', fn() => Inertia::render('applications/pending_application'))
            ->name('applications.pending_application');

        Route::get('{id}/view', [ApplicationController::class, 'view'])->name('applications.view');
        Route::get('{id}/edit', [ApplicationController::class, 'edit'])->name('applications.edit');

        Route::post('updateStatus', [ApplicationController::class, 'updateStatus'])->name('applications.updateStatus');

        // FLOW ACTIONS
        Route::post('receive', [RPSChiefDashboardController::class, 'receivedApplication'])->name('applications.rpschief.receive');
        Route::post('endorse', [RPSChiefDashboardController::class, 'endorseApplication'])->name('applications.rpschief.endorse');
        Route::post('return', [ApplicationController::class, 'returnApplication'])->name('applications.return');

        // PENRO
        Route::post('penro/receive', [AssessmentController::class, 'receive'])->name('applications.penro.receive');
        Route::post('penro/return', [PENROController::class, 'returnApplication'])->name('applications.penro.return');
        Route::post('penro/endorse', [PENROController::class, 'endorseToFUS'])->name('applications.penro.endorse');

        // TSD
        Route::post('tsd/receive', [TSDController::class, 'receivedApplication'])->name('applications.tsd.receive');
        Route::post('tsd/endorse', [TSDController::class, 'endorseApplication'])->name('applications.tsd.endorse');
        Route::post('tsd/return', [TSDController::class, 'returnApplication'])->name('applications.tsd.return');

        // FUS
        Route::post('fus/receive', [AssessmentController::class, 'receive'])->name('applications.fus.receive');
        Route::post('fus/endorse', [FUSController::class, 'endorseApplication'])->name('applications.fus.endorse');
        Route::post('fus/return', [FUSController::class, 'returnApplication'])->name('applications.fus.return');

        // ARDTS / RED
        Route::post('ardts/receive', [AssessmentController::class, 'receive'])->name('applications.ardts.receive');
        Route::post('ardts/endorse', [ARDTSController::class, 'endorseApplication'])->name('applications.ardts.endorse');
        Route::post('ardts/return', [ARDTSController::class, 'returnApplication'])->name('applications.ardts.return');

        Route::post('red/receive', [AssessmentController::class, 'receive'])->name('applications.red.receive');
        Route::post('red/return', [RegionalExecutiveController::class, 'returnApplication'])->name('applications.red.return');

        /*
        |--------------------------------------------------------------------------
        | UPDATE DATA
        |--------------------------------------------------------------------------
        */
        Route::put('{id}/update-applicant-data', [ApplicationController::class, 'updateIndividualApplicant'])
            ->name('applications.update.individual.data');

        Route::post('{id}/update-company-data', [ApplicationController::class, 'updateCompanyApplicant'])
            ->name('applications.update.company.data');

        Route::post('{id}/update-company-payment-data', [ApplicationController::class, 'updateCompanyPayemnt'])
            ->name('applications.update.company.payment.data');

        Route::put('{id}/update-chainsaw-info', [ChainsawController::class, 'updateChainsawInformation'])
            ->name('applications.update.chainsaw.info');

        Route::put('{id}/update-payment-info', [PaymentController::class, 'updatePaymentInformation'])
            ->name('applications.update.payment.info');

        Route::put('{id}/submit-application', [ApplicationController::class, 'submitApplication'])
            ->name('applications.submit.application');
    });

    /*
    |--------------------------------------------------------------------------
    | USER MANAGEMENT
    |--------------------------------------------------------------------------
    */
    Route::prefix('user-management')->group(function () {
        Route::get('index', fn() => Inertia::render('user-management/index'))->name('user-management.index');
        Route::get('{id}/edit', [UserController::class, 'edit_user'])->name('user-management.edit');
        Route::put('{id}', [UserController::class, 'update'])->name('user-management.update');
    });

    /*
    |--------------------------------------------------------------------------
    | UTILITIES
    |--------------------------------------------------------------------------
    */
    Route::get('/generateApplicationNumber', [ApplicationController::class, 'generateApplicationNumber']);

    Route::get('/signature/boss', function () {
        $filePath = 'private/signatures/red_signature.png';

        abort_unless(Storage::exists($filePath), 404);

        return Response::make(Storage::get($filePath), 200, [
            'Content-Type' => Storage::mimeType($filePath),
            'Content-Disposition' => 'inline; filename="boss_signature.png"',
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | PERMITS / PDF
    |--------------------------------------------------------------------------
    */
    Route::prefix('permit')->group(function () {
        Route::get('print/{id}', [PDFController::class, 'printPermit'])->name('permit.print');
        Route::get('{id}/preview', [PDFController::class, 'generateTable'])->name('permit.preview');
    });

    Route::get('/chainsaw-permit/{id}/word', [PDFController::class, 'generatePermitDocx'])
        ->name('chainsaw.permit.word');

    Route::get('/chainsaw-permit-multiple-brands-models/{id}/word',
        [PDFController::class, 'generatePermitDocxMultipleBrandsModels'])
        ->name('chainsaw.permit.multiple.word');
});

/*
|--------------------------------------------------------------------------
| OTHER ROUTES
|--------------------------------------------------------------------------
*/
require __DIR__ . '/application.php';
require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';