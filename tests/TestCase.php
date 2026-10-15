<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Trava de seguranca: o RefreshDatabase recria as tabelas, entao os testes so podem
     * rodar em um banco de testes (nome terminado em "_test"). Evita apagar o banco de
     * desenvolvimento se as variaveis do .env vazarem para o processo de testes.
     * Fica em setUpTraits (e nao em um hook do trait) porque roda antes do RefreshDatabase.
     */
    protected function setUpTraits()
    {
        $conexao = config('database.default');
        $banco = (string) config("database.connections.{$conexao}.database");

        if (! str_ends_with($banco, '_test')) {
            throw new RuntimeException(
                "Testes abortados: o banco \"{$banco}\" não é de testes (o nome deve terminar em \"_test\"). Confira o .env.testing."
            );
        }

        return parent::setUpTraits();
    }
}
