<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiInteraction extends Model
{
    // This allows the Service to save data to these columns
    protected $fillable = [
        'task_name',
        'prompt',
        'response',
        'model'
    ];
}