<?php
// app/Models/Traits/BelongsToUser.php

namespace App\Models\Traits;

trait BelongsToUser
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, $userId = null)
    {
        $userId = $userId ?? auth()->id();
        return $query->where('user_id', $userId);
    }
}