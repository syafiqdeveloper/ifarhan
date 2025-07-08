<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dischargesummary extends Model
{
    use HasFactory;

    public function createdby()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedby()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function draftsaveby()
    {
        return $this->belongsTo(User::class, 'draftsaveby', 'id');
    }
}
