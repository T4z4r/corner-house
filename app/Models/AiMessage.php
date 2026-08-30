<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AiMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_conversation_id',
        'role',
        'content',
        'intent',
        'flagged',
    ];

    protected $casts = [
        'flagged' => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }

    public function faqArticle(): HasOne
    {
        return $this->hasOne(KnowledgeBaseArticle::class, 'source_message_id');
    }
}
