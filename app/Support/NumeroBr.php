<?php

namespace App\Support;

use Illuminate\Http\Request;

class NumeroBr
{
    /**
     * Converte um numero digitado no formato brasileiro ("1.234,56", "1,5") para o
     * formato aceito pela validacao "numeric" ("1234.56", "1.5"). Valores que ja
     * estao com ponto decimal ("1.5", "0.015") sao mantidos.
     */
    public static function normalizar(mixed $valor): mixed
    {
        if (! is_string($valor)) {
            return $valor;
        }

        $valor = trim(str_replace(['R$', ' '], '', $valor));

        if (str_contains($valor, ',')) {
            // virgula e o separador decimal; pontos sao separadores de milhar
            return str_replace(['.', ','], ['', '.'], $valor);
        }

        if (preg_match('/^[1-9]\d{0,2}(\.\d{3})+$/', $valor)) {
            // "5.000" sem virgula: ponto como separador de milhar
            return str_replace('.', '', $valor);
        }

        return $valor;
    }

    /**
     * Normaliza, na propria requisicao, os campos informados (antes de validar).
     *
     * @param  array<int, string>  $campos
     */
    public static function normalizarCampos(Request $request, array $campos): void
    {
        foreach ($campos as $campo) {
            if ($request->has($campo)) {
                $request->merge([$campo => self::normalizar($request->input($campo))]);
            }
        }
    }
}
