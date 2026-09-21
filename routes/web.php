<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\language\LanguageController;
use App\Http\Controllers\pages\Page2;
use App\Http\Controllers\pages\MiscError;
use App\Http\Controllers\authentications\LoginBasic;
use App\Http\Controllers\authentications\RegisterBasic;
use App\Http\Controllers\CartaoCreditoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DividaController;
use App\Http\Controllers\FaturaController;
use App\Http\Controllers\HistoricoJurosController;
use App\Http\Controllers\ParcelaController;
use App\Http\Controllers\PerfilController;

// Raiz do sistema: manda para o dashboard (autenticado) ou para o login (visitante).
// A HomePage/Page2/telas de auth "basic" abaixo sao demos do starter-kit do Vuexy,
// mantidas registradas como referencia mas fora do menu do produto final.
Route::get('/', function () {
    return redirect(auth()->check() ? '/dashboard' : '/login');
})->name('pages-home');

Route::get('/page-2', [Page2::class, 'index'])->name('pages-page-2');

// locale
Route::get('/lang/{locale}', [LanguageController::class, 'swap']);
Route::get('/pages/misc-error', [MiscError::class, 'index'])->name('pages-misc-error');

// authentication (demos estaticas do starter-kit, nao usar - login/registro reais ficam em /login e /register)
Route::get('/auth/login-basic', [LoginBasic::class, 'index'])->name('auth-login-basic');
Route::get('/auth/register-basic', [RegisterBasic::class, 'index'])->name('auth-register-basic');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::put('/perfil/renda', [PerfilController::class, 'atualizarRenda'])->name('perfil.renda.atualizar');

    Route::get('/cartoes', [CartaoCreditoController::class, 'index'])->name('cartoes.index');
    Route::get('/cartoes/{cartao}', [CartaoCreditoController::class, 'show'])->name('cartoes.show');
    Route::post('/cartoes', [CartaoCreditoController::class, 'store'])->name('cartoes.store');

    Route::get('/dividas', [DividaController::class, 'index'])->name('dividas.index');
    Route::post('/dividas', [DividaController::class, 'store'])->name('dividas.store');
    Route::get('/dividas/{divida}/relatorio', [DividaController::class, 'relatorioAmortizacao'])->name('dividas.relatorio');
    Route::post('/dividas/{divida}/juros', [HistoricoJurosController::class, 'store'])->name('dividas.juros.store');
    Route::post('/dividas/{divida}/parcelas', [ParcelaController::class, 'store'])->name('dividas.parcelas.store');

    Route::patch('/parcelas/{parcela}/pagar', [ParcelaController::class, 'marcarPaga'])->name('parcelas.pagar');

    Route::post('/faturas', [FaturaController::class, 'store'])->name('faturas.store');
    Route::patch('/faturas/{fatura}/pagar', [FaturaController::class, 'marcarPaga'])->name('faturas.pagar');
});
