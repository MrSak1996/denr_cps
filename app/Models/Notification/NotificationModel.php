<?php

namespace App\Models\Notification;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class NotificationModel extends Model
{
     use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'tbl_email_notif'; 

    protected $fillable = [
        'id',
        'application_id',
        'permit_no',
        'email',
        'expires_at','created_at','updated_at'
    ];
}
