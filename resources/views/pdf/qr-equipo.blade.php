<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>QR {{ $equipo->codigo_activo }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            color: #173a5f;
        }
        .etiqueta {
            width: 100%;
            padding: 20px 18px;
            text-align: center;
        }
        .marca {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 2px;
            color: #173a5f;
        }
        .sub {
            font-size: 8px;
            color: #6b7a8b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }
        .barra {
            height: 4px;
            background: #1f9e86;
            width: 46px;
            margin: 4px auto 14px;
        }
        .qr {
            margin: 0 auto 12px;
        }
        .qr img { width: 190px; height: 190px; }
        .codigo {
            font-size: 16px;
            font-weight: bold;
            color: #173a5f;
        }
        .descripcion {
            font-size: 10px;
            color: #42566b;
            margin: 3px 0 8px;
        }
        .url {
            font-size: 7px;
            color: #9aa7b4;
            word-wrap: break-word;
        }
        .pie {
            margin-top: 10px;
            font-size: 7px;
            color: #9aa7b4;
        }
    </style>
</head>
<body>
    <div class="etiqueta">
        @if (!empty($logo))
            <img src="{{ $logo }}" alt="SIGAM" style="height:26px;margin-bottom:2px">
        @else
            <div class="marca">SIGAM</div>
        @endif
        <div class="sub">Identificación de activo</div>
        <div class="barra"></div>

        <div class="qr"><img src="{{ $qr }}" alt="QR"></div>

        <div class="codigo">{{ $equipo->codigo_activo }}</div>
        <div class="descripcion">{{ $equipo->descripcion }}</div>
        <div class="url">{{ $url }}</div>

        <div class="pie">Escanee con un usuario autenticado para abrir el expediente.</div>
    </div>
</body>
</html>
