<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Country extends Content
{
    // Don't add create and update timestamps in database.
    public $timestamps  = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'country';

    /**
     * Cities of the country
     */
    public function cities()
    {
        return $this->hasMany('App\City');
    }
}