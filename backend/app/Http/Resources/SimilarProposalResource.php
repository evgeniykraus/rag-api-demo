<?php

namespace App\Http\Resources;

use App\Models\Proposal;
use Illuminate\Http\Request;

/** @mixin Proposal */
class SimilarProposalResource extends ProposalResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'similarity' => $this->similarity,
        ]);
    }
}
