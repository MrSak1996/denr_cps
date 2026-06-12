<?php

namespace App\Models\Chainsaw;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChainsawPermittoSell extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'chainsaw_permits_to_sell';

    protected $fillable = [
        'id',
        'application_id',
        'supplier_name',
        'supplier_address',
        'purpose',
        'permit_to_sell_no',
        'brand_name',
        'model',
        'quantity', 
        'serial_no',
        'issued_date',
        'valid_until',
        'issued_by',
        'created_at',
        'updated_at'
        
    ];
}
