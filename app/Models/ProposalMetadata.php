<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalMetadata extends Model
{
    protected $fillable = [
        'proposal_id',
        'correctness_score',
        'completeness_score',
        'actionable_score',
        'missing_points',
        'tone_politeness_score',
        'clarity_score',
        'jargon_flag',
        'policy_compliance_score',
        'risk_flags',
        'intent_tags',
        'entities_locations',
        'entities_objects',
        'resolution_likelihood',
        'followup_needed',
        'next_steps',
        'status',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'correctness_score' => 'decimal:2',
        'completeness_score' => 'decimal:2',
        'actionable_score' => 'decimal:2',
        'tone_politeness_score' => 'decimal:2',
        'clarity_score' => 'decimal:2',
        'policy_compliance_score' => 'decimal:2',
        'resolution_likelihood' => 'decimal:2',
        'jargon_flag' => 'boolean',
        'followup_needed' => 'boolean',
        'missing_points' => 'array',
        'risk_flags' => 'array',
        'intent_tags' => 'array',
        'entities_locations' => 'array',
        'entities_objects' => 'array',
        'next_steps' => 'array',
        'processed_at' => 'datetime',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}
