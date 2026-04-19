<?php

namespace App\Models\Application;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppChecklistEntry extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'tbl_app_checklist_entry';

    protected $fillable = [

    'id',
    'parent_id',
    'chklist_id',
    'answer',
    'remarks',
    'assessment',
    'created_at',
    'updated_at',
    ];

    
}
