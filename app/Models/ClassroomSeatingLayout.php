<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassroomSeatingLayout extends Model
{
    protected $fillable = ['class_assignment_id', 'rows', 'cols', 'cells', 'version', 'updated_by'];

    protected $casts = [
        'rows' => 'integer',
        'cols' => 'integer',
        'cells' => 'array',
        'version' => 'integer',
    ];

    public function classAssignment()
    {
        return $this->belongsTo(ClassAssignment::class);
    }
}
