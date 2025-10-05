<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $category_id
 * @property string $content
 * @property int $city_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property ProposalVector $vector
 * @property City $city
 * @property Category $category
 * @property ProposalResponse $response
 * @property ProposalMetadata $metadata
 * @property Collection<Attachment> $attachments
 */
class Proposal extends Model
{
    protected $table = 'proposals';
    public $timestamps = true;
    protected $guarded = [];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function response(): HasOne
    {
        return $this->hasOne(ProposalResponse::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function vector(): HasOne
    {
        return $this->hasOne(ProposalVector::class);
    }

    public function metadata(): HasOne
    {
        return $this->hasOne(ProposalMetadata::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}


