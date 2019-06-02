<?php

use Sloveniangooner\SearchableSelect\Http\Controllers\SearchableSelectController;

Route::get('/{resource}', SearchableSelectController::class."@index");
