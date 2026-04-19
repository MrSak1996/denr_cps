<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\ApplicantAttachments\AttachmentsModel;
use App\Models\Application\AppChecklistEntry;
use Illuminate\Http\Request;
use App\Models\Payment\PaymentModel;
use Illuminate\Support\Facades\Storage;
use App\Services\GoogleDriveService;
use App\Models\Application\ChainsawIndividualApplication;
use Illuminate\Support\Facades\DB;


class PaymentController extends Controller
{
    public function insert_payment(Request $request, GoogleDriveService $driveService)
    {
        $id = $request->input('application_id', $request->input('id'));

        $application = ChainsawIndividualApplication::find($id);
        if (!$application) {
            return response()->json(['error' => 'Application not found.'], 404);
        }

        $application_id = $application->id;
        $application_no = $application->application_no;

        // ✅ Create Payment first (without attachment for now)
        $payment = PaymentModel::create([
            'application_id' => $application_id,
            'official_receipt' => $request->input('official_receipt'),
            'permit_fee' => $request->input('permit_fee'),
            'remarks' => $request->input('remarks'),
            'date_of_payment' => now()
        ]);

        // ✅ File configuration (like company_apply approach)
        $filesToUpload = [
            'or_copy' => [
                'folder_name' => 'Official Receipt',
                'requirement_id' => 8
            ],
        ];

        $applicantType = strtolower($request->input('applicant_type'));

        $folderPath = match ($applicantType) {
            'individual' => "CHAINSAW_PERMITTING/Individual Applications/{$application_no}",
            'company' => "CHAINSAW_PERMITTING/Company Applications/{$application_no}",
            'government' => "CHAINSAW_PERMITTING/Government Applications/{$application_no}",
            default => "CHAINSAW_PERMITTING/Other/{$application_no}",
        };

        $uploadResults = [];

        foreach ($filesToUpload as $inputName => $config) {

            if ($request->hasFile($inputName)) {

                // ✅ 1. Create checklist entry FIRST
                $checklist = AppChecklistEntry::create([
                    'parent_id' => $application_id,
                    'chklist_id' => $config['requirement_id'],
                    'uploaded_at' => now(),
                ]);

                // ✅ 2. Upload ONE file and pass checklist_entry_id
                $result = $driveService->storeSingleAttachment(
                    $application_no,
                    $request->input('uploaded_by'),
                    $request->file($inputName),
                    $application_id,
                    $folderPath,
                    $config['folder_name'],
                    $checklist->id   // 🔥 IMPORTANT
                );

                $uploadResults[$inputName] = $result;

                // ✅ 3. Update payment with attachment_id (optional but cleaner)
                if ($result['status'] ?? false) {
                    $payment->update([
                        'application_attachment_id' => $result['attachment_id']
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Payment inserted successfully',
            'application_id' => $application_id,
            'payment' => $payment,
            'google_drive' => $uploadResults,
        ], 201);
    }

    public function updatePaymentInformation(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            // Update the record
            $updateResult = DB::table('tbl_application_payment')
                ->where('application_id', 2) // use payload instead of route param
                ->update([
                    'official_receipt' => $request->input('official_receipt'),
                    'permit_fee' => $request->input('permit_fee'),
                    'date_of_payment' => now(),
                    'remarks' => $request->input('remarks'),
                    'updated_at' => now(),
                ]);
            DB::commit();

            return response()->json([
                'status' => $updateResult ? 'success' : 'error',
                'message' => $updateResult ? 'Payment info updated successfully' : 'No updates were made. Please check your data.',
            ], $updateResult ? 200 : 400);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
