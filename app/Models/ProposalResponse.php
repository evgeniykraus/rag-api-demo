<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $proposal_id
 * @property bool $is_auto_response
 * @property string|null $updated_at
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



