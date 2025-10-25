<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Pgvector\Laravel\Vector as PgVector;

/**
 * @property int $proposal_id
 * @property PgVector $embedding
 */
class ProposalVector extends Model
{
    protected $fillable = [
        'embedding',
        'proposal_id',
    ];
    protected $table = 'proposal_vectors';
    protected $primaryKey = 'proposal_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'embedding' => PgVector::class,
    ];
}

