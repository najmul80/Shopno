<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefreshToken extends Model
{
     use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'token', // This is the unique JTI or identifier for the refresh token
        'revoked',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'revoked' => 'boolean',
    ];

    /**
     * Get the user that owns the refresh token.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
