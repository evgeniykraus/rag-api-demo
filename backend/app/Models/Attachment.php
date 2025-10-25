<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Storage;

/**
 * @property int $id
 * @property int $proposal_id
 * @property string $original_name
 * @property string $description
 * @property string $filename
 * @property string $path
 * @property string $url
 * @property string $mime_type
 * @property int $size
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Proposal $proposal
 */
class Attachment extends Model
{
    protected $fillable = [
        'proposal_id',
        'original_name',
        'description',
        'filename',
        'path',
        'mime_type',
        'size',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * Получить полный URL к файлу
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }

    /**
     * Проверить, является ли файл изображением
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
}
