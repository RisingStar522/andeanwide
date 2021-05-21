<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Orden No. {{ Str::padLeft($order->id, 6, '0') }}</title>
    <style>
        body {
            font-size: 12px;
        }

        .container {
            max-width: 1200px;
        }

        .data-box {
            text-align: center;
        }

        .order-title {
            text-align: center;
            text-transform: uppercase;
            font-weight: 900;
            font-size: 1.5rem;
            margin: 0 0 0.5rem 0;
        }

        .order-subtitle {
            text-align: center;
            text-transform: uppercase;
            font-weight: 600;
            font-size: 1.15rem;
            margin: 0.25rem 0 0.15rem 0;
        }
        .order-date {
            text-align: center;
            font-size: 0.75rem;
            margin: 0;
            text-transform: uppercase;
        }

        .unstyled-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .title {
            display: block;
            font-weight: 600;
            text-transform: uppercase;
            color: #333;
            fony-size: 1rem;
        }

        .subtitle {
            display: block;
            color: #333;
            font-size: 0.85rem;
        }

        .section-title {
            font-weight: 900;
            font-size: 1.05rem;
            color: #333;
            text-align: center;
            text-transform: uppercase;
            margin: 1.5rem 0 0.75rem 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2 class="order-title">ORDEN NO. {{ Str::padLeft($order->id, 6, '0') }}</h2>
        <h3 class="order-subtitle">Andean Wide SPA</h3>
        <p class="order-date">Estado: {{ $status }}</p>
        <p class="order-date">Fecha de Creación: {{ $order->created_at }}</p>
        <div class="data-box">
            <h4 class="section-title">Orden</h4>
            <ul class="unstyled-list">
                <li>
                    <span class="title">Orden No.</span>
                    <span class="subtitle">
                        {{ Str::padLeft($order->id, 6, '0') }}
                    </span>
                </li>
                <li>
                    <span class="title">Monto a Pagar:</span>
                    <span class="subtitle">{{ number_format($order->payment_amount, 0) }} {{ $order->pair->base->symbol }}</span>
                </li>
                <li>
                    <span class="title">Costo Transacción:</span>
                    <span class="subtitle">{{ number_format($order->total_cost, 0) }} {{ $order->pair->base->symbol }}</span>
                </li>
                <li>
                    <span class="title">Importe Convertido:</span>
                    <span class="subtitle">{{ number_format($order->sended_amount, 0) }} {{ $order->pair->base->symbol }}</span>
                </li>
                <li>
                    <span class="title">Tipo Cambio:</span>
                    <span class="subtitle">{{ number_format($order->rate, $order->pair->decimals) }} {{ $order->pair->base->symbol }}/{{ $order->pair->quote->symbol }}</span>
                </li>
                <li>
                    <span class="title">Monto a Recibir:</span>
                    <span class="subtitle">{{ number_format($order->received_amount, 0) }} {{ $order->pair->quote->symbol }}</span>
                </li>
            </ul>
        </div>
        <div class="data-box">
            <h4 class="section-title">BENEFICIARIO</h4>
            <ul class="unstyled-list">
                <li>
                    <span class="title">Nombres: </span>
                    <span class="subtitle">
                        {{ $order->recipient->name }} {{ $order->recipient->lastname }}
                    </span>
                </li>
                <li>
                    <span class="title">Identificación: </span>
                    <span class="subtitle">
                        {{ $order->recipient->document_type }} {{ $order->recipient->dni }}
                    </span>
                </li>
                <li>
                    <span class="title">Cuenta: </span>
                    <span class="subtitle">
                        {{ $order->recipient->bank->name }} Cuenta: {{ $order->recipient->account_type }} {{ $order->recipient->bank_account }}
                    </span>
                </li>
                <li>
                    <span class="title">Teléfono: </span>
                    <span class="subtitle">
                        {{ $order->recipient->phone }}
                    </span>
                </li>
                <li>
                    <span class="title">Correo Electrónico: </span>
                    <span class="subtitle">
                        {{ $order->recipient->email }}
                    </span>
                </li>
                <li>
                    <span class="title">Dirección: </span>
                    <span class="subtitle">
                        {{ $order->recipient->address }}, {{ $order->recipient->country->name }}
                    </span>
                </li>
            </ul>
        </div>
        @if($order->remitter)
        <div class="data-box">
            <h4 class="section-title">Remitente</h4>
            <ul class="unstyled-list">
                <li>
                    <span class="title">Nombres: </span>
                    <span class="subtitle">
                        {{ $order->remitter->fullname }}
                    </span>
                </li>
                <li>
                    <span class="title">Identificación: </span>
                    <span class="subtitle">
                        {{ $order->remitter->document_type }} {{ $order->remitter->dni }}
                    </span>
                </li>
                <li>
                    <span class="title">Teléfono: </span>
                    <span class="subtitle">
                        {{ $order->remitter->phone }}
                    </span>
                </li>
                <li>
                    <span class="title">Correo Eléctronico: </span>
                    <span class="subtitle">
                        {{ $order->remitter->email }}
                    </span>
                </li>
                <li>
                    <span class="title">Dirección: </span>
                    <span class="subtitle">
                        {{ $order->remitter->address }}
                    </span>
                    <span class="subtitle">
                        {{ $order->remitter->city }}
                    </span>
                    <span class="subtitle">
                        {{ $order->remitter->state }}
                    </span>
                    <span class="subtitle">
                        {{ $order->remitter->country->name }}
                    </span>
                </li>
            </ul>
        </div>
        @endif

    </div>
</body>

</html>
