<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;



use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Application\ChainsawIndividualApplication;
use App\Models\Application\AppChecklistEntry;
use App\Models\ApplicantAttachments\AttachmentsModel;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\GoogleDriveService;
use Inertia\Inertia;


class ReSubmissionController extends Controller
{
    public function uploadResubmission(Request $request, GoogleDriveService $driveService)
    {
        $request->validate([
            'checklist_entry_id' => 'required|integer|exists:tbl_app_checklist_entry,id',
            'files.*' => 'required|file|max:10240',
            'application_no' => 'required|string',
            'uploaded_by' => 'required'
        ]);

        try {
            $checklistEntryId = $request->checklist_entry_id;
            $files = $request->file('files');
            $applicationNo = $request->application_no;
            $encodedBy = $request->uploaded_by;

            $checklist = AppChecklistEntry::findOrFail($checklistEntryId);
            $application = ChainsawIndividualApplication::findOrFail($checklist->parent_id);

            $folderPath = 'CHAINSAW_PERMITTING/Company Applications/' . $applicationNo;

            /**
             * ✅ Requirement → Folder mapping using chklist_id
             */
            $requirementMap = [
                7  => 'Request Letter',
                11 => 'Secretary Certificate',
                8  => 'Official Receipt',
            ];

            $folderType = $requirementMap[$checklist->chklist_id] ?? 'Resubmissions';

            $uploadedFiles = [];

            /**
             * ✅ Get ORIGINAL filename from FIRST attachment
             */
            $originalAttachment = AttachmentsModel::where('checklist_entry_id', $checklistEntryId)
                ->orderBy('id')
                ->first();

            foreach ($files as $file) {

                $baseName = $originalAttachment
                    ? pathinfo($originalAttachment->file_name, PATHINFO_FILENAME)
                    : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                $extension = $file->getClientOriginalExtension();

                /**
                 * ✅ Count existing resubmissions for versioning
                 */
                $existingVersions = AttachmentsModel::where('checklist_entry_id', $checklistEntryId)
                    ->where('file_name', 'like', $baseName . '_v%')
                    ->count();

                $version = $existingVersions + 1;

                $resubmittedFileName = "{$baseName}_v{$version}.{$extension}";

                /**
                 * ✅ Upload to Google Drive (UPLOAD ONLY)
                 */
                $uploadResult = $driveService->storeResubmissionAttachment(
                    $file,
                    $folderPath,
                    $folderType,
                    $resubmittedFileName
                );

                if (!$uploadResult['status']) {
                    throw new \Exception($uploadResult['error']);
                }

                /**
                 * ✅ Save in tbl_application_attachments (ONLY HERE)
                 */
                $attachment = AttachmentsModel::create([
                    'application_id' => $application->id,
                    'uploaded_by' => $encodedBy,
                    'checklist_entry_id' => $checklistEntryId,
                    'file_id' => $uploadResult['file_id'],
                    'file_name' => $uploadResult['file_name'],
                    'file_url' => $uploadResult['file_url'],
                ]);

                $uploadedFiles[] = [
                    'file_name' => $uploadResult['file_name'],
                    'uploaded_at' => now()->toDateTimeString(),
                    'file_url' => $uploadResult['file_url'],
                ];
            }

            return response()->json([
                'checklist_entry_id' => $checklistEntryId,
                'files' => $uploadedFiles,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload resubmitted files.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    // public function uploadResubmission(Request $request, GoogleDriveService $driveService)
    // {
    //     $request->validate([
    //         'checklist_entry_id' => 'required|integer|exists:tbl_app_checklist_entry,id',
    //         'files.*' => 'required|file|max:10240',
    //         'application_no' => 'required|string',
    //         'uploaded_by' => 'required'
    //     ]);

    //     try {
    //         $checklistEntryId = $request->checklist_entry_id;
    //         $files = $request->file('files');
    //         $applicationNo = $request->application_no;
    //         $encodedBy = $request->uploaded_by;

    //         $checklist = AppChecklistEntry::with('chklist')->findOrFail($checklistEntryId);
    //         $application = ChainsawIndividualApplication::findOrFail($checklist->parent_id);

    //         $folderPath = 'CHAINSAW_PERMITTING/Company Applications/' . $applicationNo;

    //         // ✅ Requirement → Folder mapping
    //         $requirementMap = [
    //             7 => 'Request Letter',
    //             11 => 'Secretary Certificate',
    //         ];

    //         $folderType = $requirementMap[$checklist->chklist_id] ?? 'Resubmissions';

    //         $uploadedFiles = [];

    //         foreach ($files as $file) {

    //             /**
    //              * ✅ Get ORIGINAL filename from first attachment
    //              * Example: request_letter_L-2026-0001.pdf
    //              */
    //             $originalAttachment = AttachmentsModel::where('checklist_entry_id', $checklistEntryId)
    //                 ->orderBy('id')
    //                 ->first();

    //             $baseName = $originalAttachment
    //                 ? pathinfo($originalAttachment->file_name, PATHINFO_FILENAME)
    //                 : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

    //             $extension = $file->getClientOriginalExtension();

    //             /**
    //              * ✅ Count existing resubmissions for versioning
    //              */
    //             $existingVersions = AttachmentsModel::where('checklist_entry_id', $checklistEntryId)
    //                 ->where('file_name', 'like', $baseName . '_v%')
    //                 ->count();

    //             $version = $existingVersions + 1;

    //             $resubmittedFileName = "{$baseName}_v{$version}.{$extension}";

    //             /**
    //              * ✅ Upload to Google Drive
    //              */
    //             $uploadResult = $driveService->storeResubmissionAttachment(
    //                 $applicationNo,
    //                 $encodedBy,
    //                 $file,
    //                 $application->id,
    //                 $folderPath,
    //                 $folderType,
    //                 $checklistEntryId,
    //                 $resubmittedFileName
    //             );

    //             /**
    //              * ✅ Save to tbl_application_attachments
    //              */
    //             $attachment = AttachmentsModel::create([
    //                 'application_id' => $application->id,
    //                 'uploaded_by' => $encodedBy,
    //                 'checklist_entry_id' => $checklistEntryId,
    //                 'file_id' => $uploadResult['file_id'] ?? null,
    //                 'file_name' => $resubmittedFileName,
    //                 'file_url' => $uploadResult['file_url'] ?? null,
    //             ]);

    //             $uploadedFiles[] = [
    //                 'file_name' => $resubmittedFileName,
    //                 'uploaded_at' => now()->toDateTimeString(),
    //                 'file_url' => $uploadResult['file_url'] ?? null,
    //             ];
    //         }

    //         return response()->json([
    //             'checklist_entry_id' => $checklistEntryId,
    //             'uploaded_files' => $uploadedFiles,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Failed to upload resubmitted files.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    // public function uploadResubmission(Request $request, GoogleDriveService $driveService)
    // {
    //     // 1️⃣ Validate request
    //     $request->validate([
    //         'checklist_entry_id' => 'required|integer|exists:tbl_app_checklist_entry,id',
    //         'files.*' => 'required|file|max:10240', // max 10MB per file
    //         'application_no' => 'required|string',
    //     ]);

    //     try {
    //         $checklistEntryId = $request->input('checklist_entry_id');
    //         $files = $request->file('files');
    //         $applicationNo = $request->input('application_no');
    //         $encodedBy = $request->input('uploaded_by'); // Assuming you pass the user ID of the uploader

    //         // Get original checklist entry & parent application
    //         $checklist = AppChecklistEntry::findOrFail($checklistEntryId);
    //         $application = ChainsawIndividualApplication::findOrFail($checklist->parent_id);

    //         $folderPath = 'CHAINSAW_PERMITTING/Company Applications/' . $applicationNo;

    //         $uploadedFiles = [];

    //         foreach ($files as $file) {
    //             // Original file base name
    //             $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    //             $extension = $file->getClientOriginalExtension();

    //             // ✅ Determine version
    //             $existingCount = AttachmentsModel::where('checklist_entry_id', $checklistEntryId)
    //                 ->where('file_name', 'like', $originalName . '%')
    //                 ->count();
    //             $version = $existingCount + 1;

    //             $resubmittedFileName = "{$originalName}_v{$version}.{$extension}";

    //             // Upload to Google Drive
    //             $uploadResult = $driveService->storeResubmissionAttachment(
    //                 $applicationNo,
    //                 $encodedBy,
    //                 $file,
    //                 $application->id,
    //                 $folderPath,
    //                 $checklist->chklist->name ?? 'Resubmissions',
    //                 $checklistEntryId,
    //                 $resubmittedFileName // pass custom filename
    //             );

    //             // Insert into attachments table
    //             $attachment = AttachmentsModel::create([
    //                 'application_id' => $application->id,
    //                 'uploaded_by' => $encodedBy,
    //                 'checklist_entry_id' => $checklistEntryId,
    //                 'file_id' => $uploadResult['file_id'] ?? null,
    //                 'file_name' => $resubmittedFileName,
    //                 'file_url' => $uploadResult['file_url'] ?? null,
    //             ]);

    //             $uploadedFiles[] = [
    //                 'file_name' => $resubmittedFileName,
    //                 'uploaded_at' => now()->toDateTimeString(),
    //                 'google_drive_id' => $uploadResult['file_id'] ?? null,
    //             ];
    //         }

    //         return response()->json([
    //             'checklist_entry_id' => $checklistEntryId,
    //             'uploaded_files' => $uploadedFiles,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Failed to upload resubmitted files.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
}
