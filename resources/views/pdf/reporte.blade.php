<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1c2b3a; font-size: 10px; margin: 24px; }
        .cab { border-bottom: 2px solid #173a5f; padding-bottom: 8px; margin-bottom: 14px; }
        .cab table { width: 100%; border: 0; }
        .cab td { border: 0; padding: 0; vertical-align: middle; }
        .cab__logo { width: 120px; }
        .cab__logo img { height: 34px; }
        .marca { font-size: 13px; font-weight: bold; letter-spacing: 2px; color: #173a5f; }
        .titulo { font-size: 15px; font-weight: bold; margin-top: 3px; }
        .meta { font-size: 8px; color: #6b7a8b; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th {
            background: #173a5f; color: #fff; font-size: 8.5px; text-transform: uppercase;
            letter-spacing: .04em; padding: 6px 7px; text-align: left;
        }
        td { padding: 5px 7px; border-bottom: 1px solid #e9eef4; font-size: 9px; }
        tr:nth-child(even) td { background: #f6f8fb; }
        .totales { margin-top: 12px; font-size: 9px; }
        .totales span {
            display: inline-block; background: #eaf0f6; color: #173a5f; font-weight: bold;
            padding: 4px 9px; border-radius: 5px; margin-right: 6px;
        }
        .vacio { color: #9aa7b4; padding: 20px 0; text-align: center; }
    </style>
</head>
<body>
    <div class="cab">
        <table>
            <tr>
                <td>
                    @if (!empty($logo))
                        <div class="marca"><img src="{{ $logo }}" alt="SIGAM" style="height:30px"></div>
                    @else
                        <div class="marca">SIGAM</div>
                    @endif
                    <div class="titulo">{{ $titulo }}</div>
                    <div class="meta">
                        Generado por {{ $generado_por ?? 'Sistema' }} ·
                        {{ \Illuminate\Support\Carbon::parse($generado_at)->format('d/m/Y H:i') }} ·
                        {{ count($filas) }} registro(s)
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @if (count($filas))
        <table>
            <thead>
                <tr>@foreach ($columnas as $c)<th>{{ $c }}</th>@endforeach</tr>
            </thead>
            <tbody>
                @foreach ($filas as $fila)
                    <tr>@foreach ($fila as $celda)<td>{{ is_bool($celda) ? ($celda ? 'Sí' : 'No') : ($celda ?? '—') }}</td>@endforeach</tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="vacio">Sin datos para los filtros seleccionados.</div>
    @endif

    @if (!empty($totales))
        <div class="totales">
            @foreach ($totales as $k => $v)
                <span>{{ ucfirst(str_replace('_', ' ', $k)) }}: {{ is_numeric($v) ? number_format((float) $v, is_float($v + 0) && floor($v) != $v ? 2 : 0) : $v }}</span>
            @endforeach
        </div>
    @endif
</body>
</html>
