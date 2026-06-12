<?php

namespace App\Models\Chainsaw;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class ChainsawBrand extends Model
{
     use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'chainsaw_brands';

    protected $fillable = [
        'id',
        'supplier_id',
        'brand_name',
        'model_name',
        'quantity',
        'created_at',
        'updated_at',
    ];
}
