<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ChatbotPublish extends Pivot
{
    protected $table = 'chatbot_publish';

    protected $primaryKey = ['chatbot_id', 'website_id'];

    public $incrementing = false;

    protected $dates = ['published_at'];

    protected $fillable = [
        'chatbot_id',
        'website_id',
        'published_at',
    ];
}