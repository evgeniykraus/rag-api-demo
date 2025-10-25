<?php

namespace App\Http\Resources;

use App\Models\ProposalMetadata;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProposalMetadata */
class ProposalMetadataResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'correctness_score' => $this->correctness_score,
            'completeness_score' => $this->completeness_score,
            'actionable_score' => $this->actionable_score,
            'missing_points' => $this->missing_points,
            'tone_politeness_score' => $this->tone_politeness_score,
            'clarity_score' => $this->clarity_score,
            'jargon_flag' => $this->jargon_flag,
            'policy_compliance_score' => $this->policy_compliance_score,
            'risk_flags' => $this->risk_flags,
            'intent_tags' => $this->intent_tags,
            'entities' => [
                'locations' => $this->entities_locations,
                'objects' => $this->entities_objects,
            ],
            'resolution_likelihood' => $this->resolution_likelihood,
            'followup_needed' => $this->followup_needed,
            'next_steps' => $this->next_steps,
            'processed_at' => $this->processed_at,
        ];
    }
}
