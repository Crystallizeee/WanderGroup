<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('trip.{tripId}', function ($user, $tripId) {
    if ($user->trips()->where('trip_id', $tripId)->exists()) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'initials' => $user->initials,
        ];
    }
    return false;
});
