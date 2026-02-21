<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Website extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'name',
    ];

    public function chatbots()
    {
        return $this->belongsToMany(Chatbot::class, 'chatbot_publish', 'website_id', 'chatbot_id')
                    ->withPivot('published_at');
    }


    
     public function tokens()
    {
        return $this->hasMany(WebsiteToken::class);
    }
}