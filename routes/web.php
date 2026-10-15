<?php

use Illuminate\Support\Facades\Route;
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
    Route::put('/cartoes/{cartao}', [CartaoCreditoController::class, 'update'])->name('cartoes.update');
    Route::delete('/cartoes/{cartao}', [CartaoCreditoController::class, 'destroy'])->name('cartoes.destroy');

    Route::get('/dividas', [DividaController::class, 'index'])->name('dividas.index');
    Route::post('/dividas', [DividaController::class, 'store'])->name('dividas.store');
    Route::get('/dividas/{divida}/relatorio', [DividaController::class, 'relatorioAmortizacao'])->name('dividas.relatorio');
    Route::post('/dividas/{divida}/juros', [HistoricoJurosController::class, 'store'])->name('dividas.juros.store');
    Route::post('/dividas/{divida}/parcelas', [ParcelaController::class, 'store'])->name('dividas.parcelas.store');
    Route::put('/dividas/{divida}', [DividaController::class, 'update'])->name('dividas.update');
    Route::delete('/dividas/{divida}', [DividaController::class, 'destroy'])->name('dividas.destroy');

    // Bloco G: rotas aninhadas com scoped binding -> filho de outro pai responde 404
    Route::scopeBindings()->group(function () {
        Route::put('/dividas/{divida}/juros/{historico}', [HistoricoJurosController::class, 'update'])->name('dividas.juros.update');
        Route::delete('/dividas/{divida}/juros/{historico}', [HistoricoJurosController::class, 'destroy'])->name('dividas.juros.destroy');

        Route::put('/dividas/{divida}/parcelas/{parcela}', [ParcelaController::class, 'update'])->name('dividas.parcelas.update');
        Route::delete('/dividas/{divida}/parcelas/{parcela}', [ParcelaController::class, 'destroy'])->name('dividas.parcelas.destroy');
        Route::patch('/dividas/{divida}/parcelas/{parcela}/reabrir', [ParcelaController::class, 'reabrir'])->name('dividas.parcelas.reabrir');

        Route::put('/cartoes/{cartao}/faturas/{fatura}', [FaturaController::class, 'update'])->name('cartoes.faturas.update');
        Route::delete('/cartoes/{cartao}/faturas/{fatura}', [FaturaController::class, 'destroy'])->name('cartoes.faturas.destroy');
        Route::patch('/cartoes/{cartao}/faturas/{fatura}/reabrir', [FaturaController::class, 'reabrir'])->name('cartoes.faturas.reabrir');
    });

    Route::patch('/parcelas/{parcela}/pagar', [ParcelaController::class, 'marcarPaga'])->name('parcelas.pagar');

    Route::post('/faturas', [FaturaController::class, 'store'])->name('faturas.store');
    Route::patch('/faturas/{fatura}/pagar', [FaturaController::class, 'marcarPaga'])->name('faturas.pagar');
});
