<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int $parent_id
 * @property Collection<Proposal> $proposals
 * @property Collection<Category> $children
 * @property Category $parent
 */
class Category extends Model
{
    protected $table = 'categories';
    public $timestamps = false;
    protected $guarded = [];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }
}


