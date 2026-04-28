<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Services\WithdrawService;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', function () {
    return view('index');
});

Route::post('/', function (Request $request) {

    $db = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=atm_test;charset=utf8',
        'root',
        ''
    );

    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $service = new WithdrawService($db);

    $result = $service->withdrawMoney(
        (int) $request->account_id,
        (float) $request->amount
    );

    return back()->with('result', $result);
});
