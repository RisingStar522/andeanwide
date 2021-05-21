<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromQuery;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class OrderExport implements FromQuery, WithColumnFormatting, WithMapping, WithHeadings
{

    protected $from;
    protected $to;

    public function __construct($from, $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function query()
    {
        if (isset($this->from) && isset($this->to)) {
            return Order::query()
                ->whereYear('created_at', '>=' ,$this->from->year)
                ->whereMonth('created_at', '>=', $this->from->month)
                ->whereDay('created_at', '>=', $this->from->day)
                ->whereYear('created_at', '<=' ,$this->to->year)
                ->whereMonth('created_at', '<=', $this->to->month)
                ->whereDay('created_at', '<=', $this->to->day);
        }
        return Order::query()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
    }

    public function headings(): array
    {
        return [
            'Id',
            'Id Usuario',
            'Usuario',
            'Correo Electronico',
            'Monto a Pagar',
            'Costo de la Transacción',
            'Costo Prioridad',
            'Impuesto',
            'Costo Total',
            'Monto a Convertir',
            'Tasa',
            'Par',
            'Monto a Recibir',
            'Estado',
            'Remitente',
            'Beneficiario',
            'Proposito',
            'Prioridad',
            'Fecha de Creación',
            'Fecha de Payout'
        ];
    }

    public function map($order): array
    {
        return [
            'AW' . Str::padLeft($order->id, 6, '0'),
            $order->user_id,
            isset($order->user) ? $order->user->name : null,
            isset($order->user) ? $order->user->email : null,
            $order->payment_amount,
            $order->transaction_cost,
            $order->priority_cost,
            $order->tax,
            $order->total_cost,
            $order->sended_amount,
            $order->rate,
            isset($order->pair) ? $order->pair->name : null,
            $order->received_amount,
            $order->status,
            isset($order->remitter) ? $order->remitter->fullname : null,
            isset($order->recipient) ? $order->recipient->firstname . ' ' . $order->recipient->lastname : null,
            $order->purpose,
            $order->priority->label,
            Date::dateTimeToExcel($order->created_at),
            // isset($order->payed_at) ? Date::dateTimeToExcel($order->payed_at): null
        ];
    }

    public function columnFormats(): array
    {
        return [
            'S' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            // 'T' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}
