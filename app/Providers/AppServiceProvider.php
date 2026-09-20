<?php

namespace App\Providers;

use App\Models\CartaoCredito;
use App\Models\Divida;
use App\Models\Fatura;
use App\Models\Parcela;
use App\Policies\CartaoCreditoPolicy;
use App\Policies\DividaPolicy;
use App\Policies\FaturaPolicy;
use App\Policies\ParcelaPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Divida::class, DividaPolicy::class);
        Gate::policy(CartaoCredito::class, CartaoCreditoPolicy::class);
        Gate::policy(Fatura::class, FaturaPolicy::class);
        Gate::policy(Parcela::class, ParcelaPolicy::class);

        Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
            if ($src !== null) {
                return [
                    'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src) ? 'template-customizer-core-css' : (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src) ? 'template-customizer-theme-css' : '')
                ];
            }
            return [];
        });
    }
}
