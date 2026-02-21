<?php
namespace App\Models;

use App\Models\Website;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WebsiteToken extends Model
{
    protected $fillable = [
        'website_id',
        'token',
    ];

    protected $hidden = [
        'token', // never expose hashed token in JSON
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function website()
    {
        return $this->belongsTo(Website::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Token Generator (Professional Way)
    |--------------------------------------------------------------------------
    */

    public static function generateForWebsite($websiteId)
    {
        $plainToken = Str::random(60);

        $token = self::create([
            'website_id' => $websiteId,
            'token' => hash('sha256', $plainToken),
        ]);

        return $plainToken; // Return plain token ONCE
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Token
    |--------------------------------------------------------------------------
    */

    public static function findByPlainToken($plainToken)
    {
        return self::where('token', hash('sha256', $plainToken))->first();
    }
}



