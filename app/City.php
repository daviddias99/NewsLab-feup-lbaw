<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class City extends Content
{
    // Don't add create and update timestamps in database.
    public $timestamps  = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'location';

    /**
     * The country of the city
     */
    public function country()
    {
        return $this->belongsTo('App\Country');
    }
}