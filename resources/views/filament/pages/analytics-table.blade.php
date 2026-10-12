{{--
    Таблица отчёта. Последние $numbers столбцов — числа; полоска под первым столбцом
    показывает долю от лидера по первому числу. С $withPath в строке после названия идёт адрес страницы.
--}}
@php
    $withPath = $withPath ?? false;
    $numbers = $numbers ?? 2;
    $rows = collect($rows)->map(fn ($row) => array_values((array) $row));
    $textColumns = count($columns) - $numbers;
    $firstNumber = $textColumns + ($withPath ? 1 : 0);
    $max = max(1, (int) $rows->max(fn ($row) => $row[$firstNumber] ?? 0));
@endphp
<x-filament::section :heading="$heading" :description="$description">
    @if ($rows->isEmpty())
        <div class="an-empty">Пока нет данных за этот период.</div>
    @else
        <table class="an-table">
            <thead><tr>
                @foreach ($columns as $i => $column)
                    <th @class(['num' => $i >= $textColumns])>{{ $column }}</th>
                @endforeach
            </tr></thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>
                            {{ $row[0] ?? '—' }}
                            @if ($withPath)<a class="an-path" href="{{ $row[1] }}" target="_blank" rel="noopener">{{ $row[1] }}</a>@endif
                            <div class="an-bar" style="width: {{ round(($row[$firstNumber] ?? 0) / $max * 100) }}%"></div>
                        </td>
                        @foreach (array_slice($row, $withPath ? 2 : 1, $textColumns - 1) as $value)
                            <td>{{ $value ?? '—' }}</td>
                        @endforeach
                        @foreach (array_slice($row, $firstNumber) as $value)
                            <td class="num">{{ number_format((int) $value, 0, ',', ' ') }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</x-filament::section>
