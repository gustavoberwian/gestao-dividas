{{-- Placeholder simples ate a tela final ser desenhada com o layout do Vuexy (secao 7 da SPEC.md) --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Relatorio de amortizacao - {{ $divida->descricao }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <p>Credor: {{ $divida->credor }}</p>
            <p>Valor principal: {{ number_format($divida->valor_principal, 2, ',', '.') }}</p>
            <p>Saldo devedor atual: {{ number_format($divida->saldo_devedor_atual, 2, ',', '.') }}</p>

            <table>
                <thead>
                    <tr>
                        <th>Mes de referencia</th>
                        <th>Taxa de juros</th>
                        <th>Saldo devedor calculado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($historico as $registro)
                        <tr>
                            <td>{{ $registro->mes_referencia->format('m/Y') }}</td>
                            <td>{{ number_format($registro->taxa_juros * 100, 2, ',', '.') }}%</td>
                            <td>{{ number_format($registro->saldo_devedor_calculado, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
