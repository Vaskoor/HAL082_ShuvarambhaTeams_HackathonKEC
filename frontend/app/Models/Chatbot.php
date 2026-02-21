<?php
namespace App\Models;

use App\Models\File;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chatbot extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'status',
    ];

    // Chatbot owner
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Files attached to chatbot
    // Websites where chatbot is published

       public function websites()
    {
        return $this->belongsToMany(Website::class, 'chatbot_publish', 'chatbot_id', 'website_id')
                    ->withPivot('published_at');
    }

    public function isPublishedOn($websiteId)
    {
        return $this->websites()->where('website_id', $websiteId)->exists();
    }
    // Conversations of this chatbot
    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function files()
{
    return $this->belongsToMany(File::class, 'chatbot_files', 'chatbot_id', 'file_id');
}



public function getWebsitesListAttribute()
{
    return $this->websites->pluck('url')->implode(', ');
}
}