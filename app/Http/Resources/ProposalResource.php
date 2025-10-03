<?php

namespace App\Http\Resources;

use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Proposal */
class ProposalResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'city' => CityResource::make($this->city),
            'category' => CategoryResource::make($this->category),
            'response' => $this->whenLoaded('response', fn() => $this->response->content),
            'metadata' => $this->whenLoaded('metadata', function () {
                return [
                    'correctness_score' => $this->metadata->correctness_score,
                    'completeness_score' => $this->metadata->completeness_score,
                    'actionable_score' => $this->metadata->actionable_score,
                    'missing_points' => $this->metadata->missing_points,
                    'tone_politeness_score' => $this->metadata->tone_politeness_score,
                    'clarity_score' => $this->metadata->clarity_score,
                    'jargon_flag' => $this->metadata->jargon_flag,
                    'policy_compliance_score' => $this->metadata->policy_compliance_score,
                    'risk_flags' => $this->metadata->risk_flags,
                    'intent_tags' => $this->metadata->intent_tags,
                    'entities' => [
                        'locations' => $this->metadata->entities_locations,
                        'objects' => $this->metadata->entities_objects,
                    ],
                    'resolution_likelihood' => $this->metadata->resolution_likelihood,
                    'followup_needed' => $this->metadata->followup_needed,
                    'next_steps' => $this->metadata->next_steps,
                    'processed_at' => $this->metadata->processed_at,
                ];
            }),
        ];
    }
}
