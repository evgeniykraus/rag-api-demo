<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $proposal_id
 * @property bool $content
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ProposalResponse extends Model
{
    protected $table = 'proposal_responses';
    protected $primaryKey = 'proposal_id';
    public $timestamps = true;
    protected $guarded = [];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class, 'proposal_id');
    }
}



