<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $with = ['user'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function asset() {
        return $this->belongsTo(Asset::class);
    }

}
