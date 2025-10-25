<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property Collection<Proposal> $proposals
 */
class City extends Model
{
    protected $fillable = [
        'name',
    ];

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }
}
