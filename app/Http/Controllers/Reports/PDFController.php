<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Response;

use Milon\Barcode\DNS1D;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Element\Table;

use Carbon\Carbon;

class PDFController extends Controller
{
    /**
     * Convert a number to words (supports 0-999)
     */



    public function generateTable(Request $request, int $id)
    {
        // Get application info
        $application = DB::table('tbl_application_checklist as ac')
            ->leftJoin('tbl_chainsaw_information as ci', 'ci.application_id', '=', 'ac.id')
            ->leftJoin('tbl_application_payment as ap', 'ap.application_id', '=', 'ac.id')
            ->where('ac.id', $id)
            ->select([
                'ac.permit_no as permit_number',
                'ac.authorized_representative',
                DB::raw("CONCAT_WS(' ', ac.applicant_firstname, ac.applicant_middlename, ac.applicant_lastname) AS applicant_name"),
                'ac.applicant_complete_address',
                'ci.engine_serial_no',
                'ac.company_address',
                'ci.permit_chainsaw_no',
                'ci.issued_date',
                'ci.permit_validity',
                'ci.issued_by',
                'ci.quantity',
                'ci.supplier_name',
                'ci.supplier_address',
                'ci.purpose',
                'ci.other_details',
                'ac.approved_date',
                'ci.permit_validity as expiry_date',
                'ap.official_receipt',
                'ap.date_of_payment',
            ])
            ->first();

        if (!$application) {
            abort(404, 'Application not found');
        }

        // Get brands and models
        $rows = DB::table('chainsaw_brands as cb')
            ->leftJoin('chainsaw_models as cm', 'cm.brand_id', '=', 'cb.id')
            ->where('cb.application_id', $id)
            ->select('cb.brand_name', 'cm.model', 'cm.quantity')
            ->get();

        // Group by brand
        $brands = $rows->groupBy('brand_name')->map(function ($items) {
            return [
                'brand_name' => $items->first()->brand_name,
                'models' => $items->map(function ($item) {
                    return [
                        'model' => $item->model,
                        'quantity' => $item->quantity,
                    ];
                })->filter(fn($m) => $m['model'] !== null)->values()
            ];
        })->values();

        // Calculate total quantity from all models
        $totalQuantity = $rows->sum('quantity');
        $quantityInWords = ucfirst($this->numberToWords((int)$totalQuantity));
        $quantityText = "{$quantityInWords} ({$totalQuantity}) unit" . ($totalQuantity > 1 ? 's' : '') . "";
        $model_qty = "{$totalQuantity}";

        // Pass data to PDF view
        $pdf = Pdf::loadView('pdf.table-template', [
            'permit_number' => $application->permit_number,
            'name' => $application->authorized_representative ?? $application->applicant_name,
            'address' => $application->applicant_complete_address,
            'complete_address' => $application->company_address ?? $application->applicant_complete_address,
            'quantity' => $quantityText,
            'model_qty' => $model_qty,
            'brands' => $brands,
            'permit_chainsaw_no' => $application->permit_chainsaw_no,
            'engine_serial_no' => $application->engine_serial_no,
            'supplier_name' => $application->supplier_name,
            'supplier_address' => $application->supplier_address,
            'purpose' => $application->purpose,
            'others' => $application->other_details,
            'issued_by' => $application->issued_by,
            'issued_date' => $application->issued_date ? \Carbon\Carbon::parse($application->issued_date)->format('F d, Y') : null,
            'permit_validity' => $application->permit_validity ? \Carbon\Carbon::parse($application->permit_validity)->format('F d, Y') : null,
            'expiry_date' => $application->expiry_date ? \Carbon\Carbon::parse($application->expiry_date)->format('F d, Y') : null,
            'or_number' => $application->official_receipt,
            'or_date' => $application->date_of_payment ? \Carbon\Carbon::parse($application->date_of_payment)->format('F d, Y') : null,
        ]);

        return $pdf->stream('permit.pdf');
    }


    public function getChainsawBrandsWithModels($applicationId)
    {
        $rows = DB::table('chainsaw_brands as cb')
            ->leftJoin('chainsaw_models as cm', 'cm.brand_id', '=', 'cb.id')
            ->where('cb.application_id', $applicationId)
            ->select(
                'cb.id as brand_id',
                'cb.brand_name',
                'cm.id as model_id',
                'cm.model',
                'cm.quantity'
            )
            ->orderBy('cb.id')
            ->get();

        // Group into Vue-friendly structure
        $brands = [];

        foreach ($rows as $row) {
            if (!isset($brands[$row->brand_id])) {
                $brands[$row->brand_id] = [
                    'brand_name'   => $row->brand_name,
                    'quantity'     => $row->quantity,
                    'models' => []
                ];
            }

            if ($row->model_id) {
                $brands[$row->brand_id]['models'][] = [
                    'model'    => $row->model,
                    'quantity' => $row->quantity
                ];
            }
        }

        // Reset array keys
        return response()->json(array_values($brands));
    }

    /**
     * Preview permit (for testing purposes)
     */
    // public function printPermit($id)
    // {
    //     $rows = DB::table('chainsaw_permits_to_sell')
    //         ->where('application_id', $id)
    //         ->get();

    //     $modelCount = $rows->whereNotNull('model')->count();
    //     $supplierCount = $rows->pluck('supplier_name')->unique()->count();

    //     if ($modelCount == 1 && $supplierCount == 1) {
    //         return $this->generatePermitDocx($id);
    //     }

    //     if ($modelCount > 1 && $supplierCount == 1) {
    //         return $this->generatePermitDocxMultipleBrandsModels($id);
    //     }

    //     if ($modelCount > 1 && $supplierCount > 1) {
    //         return $this->generatePermitDocxMultipleSuppliers($id);
    //     }
    //     // update the download counter
    //     //TABLE: tbl_application_checklist
    //     //field:download_counter
    // }




    private function generateBarcode($application): string
    {
        $directory = storage_path('app/barcodes');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filePath = "{$directory}/{$application->permit_number}.png";
        // dd($filePath);

        $barcodeContent = sprintf(
            'PTS-%s',
            'DENR-IV-A',
        );

        $dns1d = new DNS1D();

        $barcode = $dns1d->getBarcodePNG(
            $barcodeContent,
            'C128',
            4,
            120
        );

        file_put_contents($filePath, base64_decode($barcode));
        
        return $filePath;
    }
    public function printPermit($id)
    {
        $userId = auth()->id();

        $download = DB::table('tbl_application_downloads')
            ->where('application_id', $id)
            ->where('user_id', $userId)
            ->first();

        // if ($download && $download->download_count >= 3) {
        //     return response()->json([
        //         'message' => 'Download limit reached (max 3).'
        //     ], 403);
        // }

        // 👉 Your existing logic
        $rows = DB::table('chainsaw_permits_to_sell as s')
            ->leftJoin('chainsaw_brands as b', 'b.supplier_id', '=', 's.id')
            ->where('application_id', $id)
            ->get();

        $modelCount = DB::table('chainsaw_permits_to_sell as s')
            ->join('chainsaw_brands as b', 'b.supplier_id', '=', 's.id')
            ->where('s.application_id', $id)
            ->whereNotNull('b.model_name')
            ->distinct('b.model_name')
            ->count('b.model_name');

        $supplierCount = DB::table('chainsaw_permits_to_sell')
            ->where('application_id', $id)
            ->distinct('supplier_name')
            ->count('supplier_name');

        if ($modelCount == 1 && $supplierCount == 1) {
		
            $result = $this->generatePermitDocx($id);
        } elseif ($modelCount > 1 && $supplierCount == 1) {

            $result = $this->generatePermitDocxMultipleBrandsModels($id);
        } elseif ($modelCount > 1 && $supplierCount > 1) {

            $result = $this->generatePermitDocxMultipleSuppliers($id);
        } else {
            dd('No brand & model found for application id: ', $id);
        }

        // ✅ Increment or insert


        return $result;
    }

    public function generatePermitDocxMultipleBrandsModels($id)
    {
        $e = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

        /*
    |--------------------------------------------------------------------------
    | MAIN APPLICATION
    |--------------------------------------------------------------------------
    */
        $application = DB::table('tbl_application_checklist as ac')
            ->leftJoin('chainsaw_permits_to_sell as ps', 'ps.application_id', '=', 'ac.id')
            ->leftJoin('tbl_application_payment as ap', 'ap.application_id', '=', 'ac.id')
            ->where('ac.id', $id)
            ->select([
				'ac.company_name',
				'ac.application_type',
                'ac.permit_no as permit_number',
                'ac.authorized_representative',
                DB::raw("CONCAT_WS(' ', ac.applicant_firstname, ac.applicant_middlename, ac.applicant_lastname) AS applicant_name"),
                'ac.company_address',
                'ac.applicant_complete_address',
                'ac.date_approved_red',

                'ps.serial_no as engine_serial_no',
                'ps.purpose',
                'ps.issued_by',
                'ps.issued_date',
                'ps.valid_until',

                'ap.official_receipt',
                'ap.date_of_payment',
            ])
            ->first();

        if (!$application) {
            abort(404, 'Application not found');
        }
	

        /*
    |--------------------------------------------------------------------------
    | BRANDS + MODELS DATA
    |--------------------------------------------------------------------------
    */
        $rows = DB::table('chainsaw_permits_to_sell as s')
            ->leftJoin('chainsaw_brands as b', 'b.supplier_id', '=', 's.id')
            ->where('s.application_id', $id)
            ->select(
                's.supplier_name',
                's.supplier_address',
                'b.brand_name',
                'b.model_name as model',
                'b.quantity',
                's.permit_to_sell_no'
            )
            ->get();

        /*
    |--------------------------------------------------------------------------
    | SAFELY COMPUTE SUPPLIER INFO (FIXED)
    |--------------------------------------------------------------------------
    */
        $supplierText = $rows->pluck('supplier_name')->filter()->unique()->implode(' and ');

        $supplierAddressText = $rows->pluck('supplier_address')
            ->filter()
            ->unique()
            ->implode(', ');

        /*
    |--------------------------------------------------------------------------
    | QUANTITY COMPUTATION
    |--------------------------------------------------------------------------
    */
        $totalQuantity = $rows->sum('quantity');

        $quantityInWords = ucfirst($this->numberToWords((int) $totalQuantity));
        $quantityText = "{$quantityInWords} ({$totalQuantity}) unit" . ($totalQuantity > 1 ? 's' : '');

        /*
    |--------------------------------------------------------------------------
    | BRAND SUMMARY
    |--------------------------------------------------------------------------
    */
        $brandText = $rows->pluck('brand_name')->filter()->unique()->implode(' and ');

        /*
    |--------------------------------------------------------------------------
    | TEMPLATE LOADING
    |--------------------------------------------------------------------------
    */
		
             $template = new \PhpOffice\PhpWord\TemplateProcessor(
            storage_path('app/templates/chainsaw-permit-MULTIPLE-MODELS-ONE-SUPPLIER-GOVERNMENT.docx')
        	);
        
		
       
		

        /*
    |--------------------------------------------------------------------------
    | PAGE 1 VALUES
    |--------------------------------------------------------------------------
    */
        $template->setValue('permit_number', $application->permit_number);
        $template->setValue('name', $e(strtoupper($application->company_name)));
        $template->setValue(
            'authorized',
            strtoupper($e($application->authorized_representative ?: $application->applicant_name))
        );

        $template->setValue(
            'address',
            strtoupper($e($application->applicant_complete_address ?: $application->company_address))
        );

        $template->setValue('quantity', $e($quantityText));
        $template->setValue('brand', $brandText);
        $template->setValue('model', 'See Annex "A"');

        $template->setValue('engine_serial_no', $application->engine_serial_no);

        $template->setValue('supplier_name', $e($supplierText));
        $template->setValue('supplier_address', $e($supplierAddressText));

        $template->setValue('purpose', $application->purpose);
        $template->setValue('or_number', $application->official_receipt);
        $template->setValue('issued_by', $application->issued_by);

         $issuedDate = $application->date_approved_red
            ? Carbon::parse($application->date_approved_red)
            : null;

        $template->setValue(
            'issued_on',
            $issuedDate ? $issuedDate->format('F d, Y') : ''
        );

        $template->setValue(
            'valid_until',
            $issuedDate ? $issuedDate->copy()->addYear()->format('F d, Y') : ''
        );

        $template->setValue(
            'issued_on',
            $application->date_approved_red
                ? Carbon::parse($application->date_approved_red)->format('F d, Y')
                : ''
        );

        $template->setValue(
            'expiry_date',
            $application->valid_until
                ? Carbon::parse($application->valid_until)->format('F d, Y')
                : ''
        );

        $template->setValue(
            'date_approved_red',
            $application->date_approved_red
                ? Carbon::parse($application->date_approved_red)->format('F d, Y h:i A')
                : ''
        );

        $template->setValue(
            'or_date',
            $application->date_of_payment
                ? Carbon::parse($application->date_of_payment)->format('F d, Y')
                : ''
        );

         $barcodePath = $this->generateBarcode($application);

        if (!file_exists($barcodePath)) {
            dd('Barcode file not found', $barcodePath);
        }

        $template->setImageValue(
            'barcode',
            [
                'path' => $barcodePath,
                'width' => 250,
                'height' => 60,
            ]
        );

        /*
    |--------------------------------------------------------------------------
    | ANNEX TABLE
    |--------------------------------------------------------------------------
    */
        $phpWord = new \PhpOffice\PhpWord\PhpWord();

        $table = new \PhpOffice\PhpWord\Element\Table([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50
        ]);

        $arialFont = [
            'name' => 'Arial',
            'size' => 10
        ];

        // Header
        $table->addRow();
        $table->addCell(2000)->addText('Brand', $arialFont);
        $table->addCell(2000)->addText('Model', $arialFont);
        $table->addCell(1500)->addText('No of Units', $arialFont);
        $table->addCell(2500)->addText('Permit Sell Chainsaw No', $arialFont);
        $table->addCell(2000)->addText('Date of Issuance', $arialFont);
        $table->addCell(2000)->addText('Date of Expiry', $arialFont);

        // Rows
        foreach ($rows as $row) {
            $table->addRow();

            $table->addCell(2000)->addText($row->brand_name, $arialFont);
            $table->addCell(2000)->addText($row->model, $arialFont);
            $table->addCell(1500)->addText($row->quantity, $arialFont);
            $table->addCell(2500)->addText($row->permit_to_sell_no, $arialFont);

            $table->addCell(2000)->addText(
                $application->issued_date
                    ? Carbon::parse($application->issued_date)->format('F d, Y')
                    : '',
                $arialFont
            );

            $table->addCell(2000)->addText(
                $application->valid_until
                    ? Carbon::parse($application->valid_until)->format('F d, Y')
                    : '',
                $arialFont
            );
        }

        $template->setComplexBlock('annex_table', $table);
	

        /*
    |--------------------------------------------------------------------------
    | SAVE FILE
    |--------------------------------------------------------------------------
    */
        $fileName = "chainsaw-permit-{$application->permit_number}.docx";
        $tempFile = storage_path($fileName);

        $template->saveAs($tempFile);
		

        /*
    |--------------------------------------------------------------------------
    | WORD PROTECTION
    |--------------------------------------------------------------------------
    */
        $zip = new \ZipArchive();
        $zip->open($tempFile);

        $settingsXml = $zip->getFromName('word/settings.xml');

        $protectionXml = '
        <w:documentProtection 
            w:edit="forms" 
            w:enforcement="1" 
            w:cryptProviderType="rsaAES" 
            w:cryptAlgorithmClass="hash" 
            w:cryptAlgorithmType="typeAny" 
            w:cryptAlgorithmSid="14" 
            w:cryptSpinCount="100000" 
            w:hash="DENR123"
        />';

        $settingsXml = str_replace(
            '</w:settings>',
            $protectionXml . '</w:settings>',
            $settingsXml
        );

        $zip->addFromString('word/settings.xml', $settingsXml);
        $zip->close();

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }


    public function generatePermitDocxMultipleSuppliers($id)
    {
        /*
    |--------------------------------------------------------------------------
    | Escape Helper
    |--------------------------------------------------------------------------
    */

        $e = function ($value) {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        /*
    |--------------------------------------------------------------------------
    | Get Application Information
    |--------------------------------------------------------------------------
    */

        $application = DB::table('tbl_application_checklist as ac')
            ->leftJoin('chainsaw_permits_to_sell as ps', 'ps.application_id', '=', 'ac.id')
            ->leftJoin('tbl_application_payment as ap', 'ap.application_id', '=', 'ac.id')
            ->where('ac.id', $id)
            ->select([
                'ac.company_name',
                'ac.application_type',
                'ac.permit_no as permit_number',
                'ac.authorized_representative',
                DB::raw("CONCAT_WS(' ', ac.applicant_firstname, ac.applicant_middlename, ac.applicant_lastname) AS applicant_name"),
                'ac.company_address',
                'ac.applicant_complete_address',
                'ac.date_approved_red',

                'ps.serial_no as engine_serial_no',
                'ps.purpose',
                'ps.issued_by',
                'ps.issued_date',
                'ps.valid_until',

                'ap.official_receipt',
                'ap.date_of_payment',
            ])
            ->first();

        if (!$application) {
            abort(404, 'Application not found');
        }

        /*
    |--------------------------------------------------------------------------
    | Get Chainsaw Data
    |--------------------------------------------------------------------------
    */

        $rows = DB::table('chainsaw_permits_to_sell as s')
            ->leftJoin('chainsaw_brands as b', 'b.supplier_id', '=', 's.id')

            ->where('application_id', $id)
            ->select(
                's.supplier_name',
                's.supplier_address',
                'b.brand_name',
                'b.model_name as model',
                'b.quantity',
                's.permit_to_sell_no'
            )
            ->get();

        /*
    |--------------------------------------------------------------------------
    | Compute Quantity
    |--------------------------------------------------------------------------
    */

        $totalQuantity = $rows->sum('quantity');

        $quantityInWords = ucfirst($this->numberToWords((int)$totalQuantity));

        $quantityText = "{$quantityInWords} ({$totalQuantity}) unit" . ($totalQuantity > 1 ? 's' : '');

        /*
    |--------------------------------------------------------------------------
    | Supplier and Brand Summary
    |--------------------------------------------------------------------------
    */

        $supplierText = $e($rows->pluck('supplier_name')->unique()->implode(' and '));
        $supplierAddressText = $e($rows->pluck('supplier_address')->unique()->implode(' and '));

        $brandText = $e($rows->pluck('brand_name')->unique()->implode(', '));

        /*
    |--------------------------------------------------------------------------
    | Load Word Template
    |--------------------------------------------------------------------------
    */

        $template = new TemplateProcessor(
            storage_path('app/templates/Sample-Template-MULTIPLE-MODELS-MULTIPLE-SUPPLIERS.docx')
        );

        /*
    |--------------------------------------------------------------------------
    | Page 1 Values
    |--------------------------------------------------------------------------
    */

        $template->setValue('permit_number', $e($application->permit_number));

        $template->setValue('name', $e($application->company_name));
        $template->setValue(
            'authorized',
            strtoupper($e($application->authorized_representative ?: $application->applicant_name))
        );

        $template->setValue(
            'address',
            strtoupper($e($application->applicant_complete_address ?: $application->company_address))
        );

        $template->setValue('quantity', $e($quantityText));

        $template->setValue('brand', $brandText);

        $template->setValue('model', 'See Annex "A"');

        $template->setValue('supplier_name', $supplierText);

        $template->setValue('supplier_address', $supplierAddressText);

        $template->setValue('engine_serial_no', $e($application->engine_serial_no));

        $template->setValue('purpose', $e($application->purpose));

        $template->setValue('issued_by', $e($application->issued_by));

        $template->setValue('or_number', $e($application->official_receipt));
     
         $barcodePath = $this->generateBarcode($application);

        if (!file_exists($barcodePath)) {
            dd('Barcode file not found', $barcodePath);
        }

        $template->setImageValue(
            'barcode',
            [
                'path' => $barcodePath,
                'width' => 250,
                'height' => 60,
            ]
        );

        /*
    |--------------------------------------------------------------------------
    | Dates
    |--------------------------------------------------------------------------
    */

        $template->setValue(
            'issued_date',
            $application->issued_date
                ? $e(Carbon::parse($application->issued_date)->format('F d, Y'))
                : ''
        );

         $issuedDate = $application->date_approved_red
            ? Carbon::parse($application->date_approved_red)
            : null;

        $template->setValue(
            'issued_on',
            $issuedDate ? $issuedDate->format('F d, Y') : ''
        );

        $template->setValue(
            'valid_until',
            $issuedDate ? $issuedDate->copy()->addYear()->format('F d, Y') : ''
        );
		
		

        $template->setValue(
            'expiry_date',
            $application->valid_until
                ? $e(Carbon::parse($application->valid_until)->format('F d, Y'))
                : ''
        );

        $template->setValue(
            'date_approved_red',
            'Date: ' . Carbon::parse($application->date_approved_red)
                ->format('Y.m.d') . '' . Carbon::parse($application->date_approved_red)
                ->format('h:i:s P')
        );

        $template->setValue(
            'or_date',
            $application->date_of_payment
                ? $e(Carbon::parse($application->date_of_payment)->format('F d, Y'))
                : ''
        );

        /*
    |--------------------------------------------------------------------------
    | Build Annex Table
    |--------------------------------------------------------------------------
    */

        $phpWord = new PhpWord();

        $table = new Table([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50
        ]);

        $arialFont = [
            'name' => 'Arial',
            'size' => 10
        ];

        /*
    |--------------------------------------------------------------------------
    | Table Header
    |--------------------------------------------------------------------------
    */

        $table->addRow();

        $table->addCell(2000)->addText('Supplier', $arialFont);
        $table->addCell(3000)->addText('Supplier Address', $arialFont);
        $table->addCell(2000)->addText('Brand', $arialFont);
        $table->addCell(2000)->addText('Model', $arialFont);
        $table->addCell(1500)->addText('Units', $arialFont);
        $table->addCell(2000)->addText('Permit Sell No', $arialFont);

        /*
    |--------------------------------------------------------------------------
    | Table Data
    |--------------------------------------------------------------------------
    */

        foreach ($rows as $row) {

            $table->addRow();

            $table->addCell(2000)->addText($e($row->supplier_name), $arialFont);
            $table->addCell(3000)->addText($e($row->supplier_address), $arialFont);
            $table->addCell(2000)->addText($e($row->brand_name), $arialFont);
            $table->addCell(2000)->addText($e($row->model), $arialFont);
            $table->addCell(1500)->addText($e($row->quantity), $arialFont);
            $table->addCell(2000)->addText($e($row->permit_to_sell_no), $arialFont);
        }

        /*
    |--------------------------------------------------------------------------
    | Inject Annex Table
    |--------------------------------------------------------------------------
    */

        $template->setComplexBlock('annex_table', $table);

        /*
    |--------------------------------------------------------------------------
    | Save File
    |--------------------------------------------------------------------------
    */

        $fileName = "chainsaw-permit-" . $application->permit_number . ".docx";

        $tempFile = storage_path($fileName);

        $template->saveAs($tempFile);

        /*
    |--------------------------------------------------------------------------
    | Apply Word Protection
    |--------------------------------------------------------------------------
    */

        $zip = new \ZipArchive();
        $zip->open($tempFile);

        $settingsXml = $zip->getFromName('word/settings.xml');

        $protectionXml = '
        <w:documentProtection 
            w:edit="forms" 
            w:enforcement="1" 
            w:cryptProviderType="rsaAES" 
            w:cryptAlgorithmClass="hash" 
            w:cryptAlgorithmType="typeAny" 
            w:cryptAlgorithmSid="14" 
            w:cryptSpinCount="100000" 
            w:hash="DENR123"
        />';

        $settingsXml = str_replace(
            '</w:settings>',
            $protectionXml . '</w:settings>',
            $settingsXml
        );

        $zip->addFromString('word/settings.xml', $settingsXml);
        $zip->close();

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }

    function formatMultiline($text)
    {
        return str_replace(
            ["\r\n", "\r", "\n"],
            '</w:t><w:br/><w:t>',
            htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );
    }

    public function generatePermitDocx($id)
    {
        /*
        |--------------------------------------------------------------------------
        | Escape Helper
        |--------------------------------------------------------------------------
        */
        $e = function ($value) {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        /*
        |--------------------------------------------------------------------------
        | Get Application Information
        |--------------------------------------------------------------------------
        */

        $application = DB::table('tbl_application_checklist as ac')
            ->leftJoin('chainsaw_permits_to_sell as ps', 'ps.application_id', '=', 'ac.id')
            ->leftJoin('tbl_application_payment as ap', 'ap.application_id', '=', 'ac.id')
            ->where('ac.id', $id)
            ->select([
                'ac.id',
                'ac.company_name',
                'ac.application_type',
                'ac.permit_no as permit_number',
                'ac.authorized_representative',
                DB::raw("CONCAT_WS(' ', ac.applicant_firstname, ac.applicant_middlename, ac.applicant_lastname) AS applicant_name"),
                'ac.company_address',
                'ac.applicant_complete_address',
                'ac.date_approved_red',

                'ps.serial_no as engine_serial_no',
                'ps.purpose',
                'ps.issued_by',
                'ps.issued_date',
                'ps.valid_until',
                'ps.permit_to_sell_no',

                'ap.official_receipt',
                'ap.date_of_payment',
            ])
            ->first();

        if (!$application) {
            abort(404, 'Application not found');
        }

        /*
        |--------------------------------------------------------------------------
        | Get Chainsaw Data
        |--------------------------------------------------------------------------
        */

        $rows = DB::table('chainsaw_permits_to_sell as s')
            ->leftJoin('chainsaw_brands as b', 'b.supplier_id', '=', 's.id')

            ->where('s.application_id', $id)
            ->select(
                's.supplier_name',
                's.supplier_address',
                'b.brand_name',
                'b.model_name as model',
                'b.quantity',
                'permit_to_sell_no'
            )
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Compute Quantity
        |--------------------------------------------------------------------------
        */

        $totalQuantity = $rows ? $rows->quantity : 0;

        $quantityInWords = ucfirst($this->numberToWords((int)$totalQuantity));

        $quantityText = "{$quantityInWords} ({$totalQuantity}) unit" . ($totalQuantity > 1 ? 's' : '');

        /*
        |--------------------------------------------------------------------------
        | Escape Supplier / Brand Data
        |--------------------------------------------------------------------------
        */

        $supplierText = $rows ? $e($rows->supplier_name) : '';
        $supplierAddressText = $rows ? $e("\t".$rows->supplier_address) : '';

        $brandText = $rows ? $e($rows->brand_name) : '';
        $modelText = $rows ? $e($rows->model) : '';

        /*
        |--------------------------------------------------------------------------
        | Load Word Template
        |--------------------------------------------------------------------------
        */
        if($application->application_type == 'Individual'){
            $template = new TemplateProcessor(
               storage_path('app/templates/chainsaw-permit-basic.docx')
            );
        } else if($application->application_type == 'Company'){
            $template = new TemplateProcessor(
                storage_path('app/templates/chainsaw-permit-company.docx')
            );
        } else if($application->application_type == 'Government') {
			
            $template = new TemplateProcessor(
               storage_path('app/templates/chainsaw-permit-government.docx')
            );
        }

  

        /*
        |--------------------------------------------------------------------------
        | Page 1 Values
        |--------------------------------------------------------------------------
        */
        $address = strtoupper($application->applicant_complete_address ?: $application->company_address);

        $template->setValue('permit_number', $e($application->permit_number));
        $template->setValue('name', $e($application->company_name));
        $template->setValue('authorized', $e($application->authorized_representative ?: $application->applicant_name) );
		$template->setValue('address', $e($address));
        $template->setValue('quantity', $e($quantityText));
        $template->setValue('brand', $brandText);
        $template->setValue('model', $modelText);

        $template->setValue('supplier_name', $supplierText);
        $template->setValue('supplier_address', $supplierAddressText);

        $template->setValue('engine_serial_no', $e($application->engine_serial_no));
        $template->setValue('permit_to_sell_no', $e($rows->permit_to_sell_no));

        // $template->setValue('purpose', $e($application->purpose));
        $template->setValue('purpose', $this->formatMultiline($application->purpose));
        $template->setValue('issued_by', $e($application->issued_by));

        $barcodePath = $this->generateBarcode($application);

        if (!file_exists($barcodePath)) {
            dd('Barcode file not found', $barcodePath);
        }

        $template->setImageValue(
            'barcode',
            [
                'path' => $barcodePath,
                'width' => 250,
                'height' => 60,
            ]
        );

        $template->setValue(
            'issued_date',
            $application->issued_date
                ? $e(Carbon::parse($application->issued_date)->format('F d, Y'))
                : ''
        );
        $template->setValue(
            'issued_on',
            $application->date_approved_red
                ? $e(Carbon::parse($application->date_approved_red)->format('F d, Y'))
                : Carbon::now()->format('F d, Y')
        );




        $template->setValue(
            'date_approved_red',
            Carbon::parse($application->date_approved_red)
                ->format('Y.m.d') . ' ' . Carbon::parse($application->date_approved_red)->format('h:i:s P')
        );

        $template->setValue(
            'expiry_date',
            $application->valid_until
                ? $e(Carbon::parse($application->valid_until)->format('F d, Y'))
                : Carbon::now()->format('F d, Y')
        );


        $template->setValue(
            'date_expired',
            $application->date_approved_red
                ? $e(Carbon::parse($application->date_approved_red)->addYear()->format('F d, Y'))
                : Carbon::now()->format('F d, Y')
        );

        $template->setValue('or_number', $e($application->official_receipt));

        $template->setValue(
            'or_date',
            $application->date_of_payment
                ? $e(Carbon::parse($application->date_of_payment)->format('F d, Y'))
                : ''
        );

        /*
        |--------------------------------------------------------------------------
        | Save File
        |--------------------------------------------------------------------------
        */

        $fileName = "chainsaw-permit-" . $application->permit_number . ".docx";
        $tempFile = storage_path($fileName);

        $template->saveAs($tempFile);

        /*
        |--------------------------------------------------------------------------
        | Apply Word Protection
        |--------------------------------------------------------------------------
        */

        $zip = new \ZipArchive();
        $zip->open($tempFile);

        $settingsXml = $zip->getFromName('word/settings.xml');

        $protectionXml = '
        <w:documentProtection 
            w:edit="forms" 
            w:enforcement="1" 
            w:cryptProviderType="rsaAES" 
            w:cryptAlgorithmClass="hash" 
            w:cryptAlgorithmType="typeAny" 
            w:cryptAlgorithmSid="14" 
            w:cryptSpinCount="100000" 
            w:hash="DENR123"
        />';

        $settingsXml = str_replace(
            '</w:settings>',
            $protectionXml . '</w:settings>',
            $settingsXml
        );

        $zip->addFromString('word/settings.xml', $settingsXml);
        $zip->close();

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }

    private function numberToWords(int $number): string
    {
        $dictionary = [
            0 => 'zero',
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine',
            10 => 'ten',
            11 => 'eleven',
            12 => 'twelve',
            13 => 'thirteen',
            14 => 'fourteen',
            15 => 'fifteen',
            16 => 'sixteen',
            17 => 'seventeen',
            18 => 'eighteen',
            19 => 'nineteen',
            20 => 'twenty',
            30 => 'thirty',
            40 => 'forty',
            50 => 'fifty',
            60 => 'sixty',
            70 => 'seventy',
            80 => 'eighty',
            90 => 'ninety'
        ];

        if ($number < 21) {
            return $dictionary[$number];
        }

        if ($number < 100) {
            $tens = ((int) ($number / 10)) * 10;
            $units = $number % 10;
            return $dictionary[$tens] . ($units ? '-' . $dictionary[$units] : '');
        }

        if ($number < 1000) {
            $hundreds = (int) ($number / 100);
            $remainder = $number % 100;
            return $dictionary[$hundreds] . ' hundred' . ($remainder ? ' and ' . $this->numberToWords($remainder) : '');
        }

        // fallback for numbers >= 1000
        return (string) $number;
    }
}
