<?php

// Servis
use App\Services\WithdrawService;


// DB
$db = new PDO(
    'mysql:host=127.0.0.1;port=3306;dbname=atm_test;charset=utf8',
    'root',
    ''
);

// Xetalar exception kimi qaytarilsin
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Neticeni saxlamaq ucun
$result = null;

// Form gonderilende
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accountId = (int) $_POST['account_id'];
    $amount = (float) $_POST['amount'];

    // Servisi cagiririq
    $service = new WithdrawService($db);
    $result = $service->withdrawMoney($accountId, $amount);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ATM Test</title>
</head>
<body>

<h2>ATM idarə edilməsi - API yaradılması </h2>

<form method="POST" action="/">
    @csrf

    <label>Account ID:</label><br>
    <input type="number" name="account_id" value="1"><br><br>

    <label>Məbləğ:</label><br>
    <input type="number" name="amount" value="125"><br><br>

    <button type="submit">Təsdiq</button>
</form>

@if(session('result'))
    <h3>Nəticəni göstər:</h3>
    <pre>{{ print_r(session('result'), true) }}</pre>
@endif
<h1 class="h1">Tapşırıq ( Texnologiyalar : PHP / Laravel 8 / MySql</h1>

<table>
    <tr>
        <th>Suallar</th>
        <th>Məntiqi həll yanaşmam</th>
    </tr>

    <tr>
        <td>1. İkili valyuta dəstəyi yaradın</td>
        <td>AZN və USD kimi valyutalar üçün ayrıca struktur qurulur.</td>
    </tr>

    <tr>
        <td>2. Əskinazları təyin edin: 200, 100, 50, 20, 10, 5 və s.</td>
        <td>Hər valyutaya AZN / USD uyğun nominallar bazada saxlanılır.</td>
    </tr>

    <tr>
        <td>3. Hər valyuta üzrə əskinaz qalığını idarə edin.</td>
        <td>Məsələn AZN 100-lükdən 10 ədəd, 20-likdən 30 ədəd.</td>
    </tr>

    <tr>
        <td>4. Hesabları təyin edin.</td>
        <td>İstifadəçi hesabı, balans və valyuta məlumatı saxlanılır.</td>
    </tr>

    <tr>
        <td>5. Hesabdan pul çıxarışı üçün servis yazın.</td>
        <td>WithdrawService yaradılır və çıxarış məntiqi orada yazılır.</td>
    </tr>
    <tr>
        <td>6. Pul çıxarışı zamanı minimum sayda əskinaz verilməlidir.</td>
        <td>Minimum əskinaz sayını təyin etmık üçün alqoritm təyin edilir.</td>
    </tr>
     <tr>
        <td>7. ATM-də uyğun əskinaz kombinasiyası yoxdursa, əməliyyat icra olunmamalıdır.</td>
        <td>Mümkün olan ən uyğun əskinaz seçilməlidir</td>
    </tr>
     <tr>
        <td>8. Eyni vaxtda paralel çıxarış sorğuları zamanı balans və ATM qalığı düzgün
qorunmalıdır.</td>
        <td>DB:: transaction və lockForUpdate məntiqi qurulur</td>
    </tr>
     <tr>
        <td>9. Tarixçə yaradın.</td>
        <td>transaction ilə tarixçə yaradılır</td>
    </tr>
     <tr>
        <td>10. Xüsusi istifadəçilərin əməliyyat silməsinə icazə verin.</td>
        <td>------</td>
    </tr>
     <tr>
        <td>11. Audit Trail yaradın.</td>
        <td>Uğursuz əməliyyatlar log-a yazılır</td>
    </tr>
     <tr>
        <td>12. Performans ölçümü nəzərə alınmalıdır.</td>
        <td>Performans üçün index-ləşdirmək lazımdır. </td>
    </tr>
     <tr>
        <td>13. Rate Limiting və Retry Protection nəzərə alınmalıdır.</td>
        <td>------</td>
    </tr>


</table>

<style>
    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        border: 1px solid black;
        padding: 10px;
        text-align: left;
        vertical-align: top;
    }

    .h1
    {
        text-align: center;
    }
</style>

</body>
</html>