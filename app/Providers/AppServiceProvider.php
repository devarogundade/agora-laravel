<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        date_default_timezone_set('Africa/Lagos');

        Relation::morphMap([
            'asset' => 'App\Models\Asset',
            'image' => 'App\Models\Image',
            'user' => 'App\Models\User',
            'offer' => 'App\Models\Offer',
        ]);
    }
}
