<?php

namespace App\Models\Chainsaw;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class ChainsawInformation extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'tbl_chainsaw_information';

    protected $fillable = [
        'id',
        'application_id',
        'application_attachment_id',
        'permit_chainsaw_no',
        'brand',
        'model',
        'engine_serial_no',
        'quantity',
        'supplier_name',
        'supplier_address',
        'purpose',
        'permit_validity',
        'other_details',
        'issued_by',
        'issued_date',
        'updated_at',
        'created_at'
    ];
    
}
