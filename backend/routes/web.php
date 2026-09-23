<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Backend ini murni REST API — lihat routes/api.php untuk endpoint yang
| dipakai. Frontend (Vue SPA) berjalan terpisah di folder /frontend.
|
*/

Route::get('/', function () {
    return response()->json(['message' => 'Local Bowls API']);
});
