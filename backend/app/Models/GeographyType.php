<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeographyType extends Model
{
    public $timestamps = false;

    protected $table = 'geography_types';

    protected $fillable = [
        'name',
        'display_name',
        'level_order',
    ];

    protected $casts = [
        'level_order' => 'integer',
    ];

    /**
     * Geographies of this type.
     */
    public function geographies(): HasMany
    {
        return $this->hasMany(Geography::class, 'geography_type_id');
    }
}
