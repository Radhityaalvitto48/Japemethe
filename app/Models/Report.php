<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Report extends Model
{
    protected $fillable = [
        'generated_by',
        'report_type',
        'filter_criteria',
        'file_path',
        'status_report',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'filter_criteria' => 'array',
    ];

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
