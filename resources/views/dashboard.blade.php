<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Placeholder simples ate a tela final ser desenhada com o layout do Vuexy (RF008, secao 6.8) --}}
            <ul>
                <li>Renda mensal: {{ number_format($rendaMensal, 2, ',', '.') }}</li>
                <li>Total em faturas abertas: {{ number_format($totalFaturasAbertas, 2, ',', '.') }}</li>
                <li>Total em parcelas de dividas do mes: {{ number_format($totalParcelasDividasDoMes, 2, ',', '.') }}</li>
                <li>Saldo disponivel: {{ number_format($saldoDisponivel, 2, ',', '.') }}</li>
            </ul>

            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <x-welcome />
            </div>
        </div>
    </div>
</x-app-layout>
