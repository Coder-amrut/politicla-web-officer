<?php

namespace App\Models;

use App\Enums\GeographyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Geography extends Model
{
    protected $table = 'geographies';

    protected $fillable = [
        'uuid',
        'project_id',
        'geography_type_id',
        'parent_id',
        'name',
        'code',
        'latitude',
        'longitude',
        'status',
    ];

    protected $casts = [
        'latitude'  => 'float',
        'longitude' => 'float',
        'status'    => GeographyStatus::class,
    ];

    /**
     * The project this geography belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * The type / tier of this geographic unit (e.g., STATE, DISTRICT, etc.).
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(GeographyType::class, 'geography_type_id');
    }

    /**
     * Alias for type() for explicit naming.
     */
    public function geographyType(): BelongsTo
    {
        return $this->belongsTo(GeographyType::class, 'geography_type_id');
    }

    /**
     * Parent geographic node (e.g. State is parent of District).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Geography::class, 'parent_id');
    }

    /**
     * Immediate child geographic nodes.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Geography::class, 'parent_id');
    }
}
