<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $with = ['images', 'offers', 'video'];

    public function images() {
        return $this->hasMany(Image::class);
    }

    public function video() {
        return $this->hasOne(Video::class);
    }

    public function offers() {
        return $this->hasMany(Offer::class)
            ->orderBy('price', 'desc')
            ->orderBy('created_at', 'desc');
    }
}
