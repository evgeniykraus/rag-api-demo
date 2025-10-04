<?php

namespace App\Http\Resources;

use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

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
            'metadata' => $this->whenLoaded('metadata', fn() => ProposalMetadataResource::make($this->metadata)),
            'is_analyzing' => Cache::has("proposal:analyzing:{$this->id}"),
        ];
    }
}
