<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeBaseArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'title',
        'content',
        'status',
        'priority',
        'starts_at',
        'ends_at',
        'source',
        'show_on_website',
        'source_message_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'priority' => 'integer',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'show_on_website' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sourceMessage(): BelongsTo
    {
        return $this->belongsTo(AiMessage::class, 'source_message_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isVisibleOnWebsite(): bool
    {
        return $this->show_on_website;
    }
}
