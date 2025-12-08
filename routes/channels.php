<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Calculator;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Canal privado para colaboración en Calculator
 * Permite a cualquier usuario autenticado sumarse al canal del calculator.
 * TODO: Endurecer reglas cuando exista modelo de permisos/compartidos.
 */
Broadcast::channel('calculator.{id}', function ($user, $id) {
    return (bool) $user && Calculator::find($id);
});
