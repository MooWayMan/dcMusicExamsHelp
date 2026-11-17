{{-- resources/views/exports/payment-document.blade.php --}}
{{--
    A payment advice (Paul paid someone on Trinity's behalf) or a receipt (Paul
    was paid exam fees). Laid out like Paul's old INVOICES_TCL_LM.numbers
    sheets. Every figure comes from App\Services\PaymentDocuments.
--}}
@php
    $isReceipt = $document->kind === \App\Models\PaymentDocument::KIND_RECEIPT;
    $money = fn (int $pence) => \App\Services\PaymentDocuments::money($pence);
    $paidOn = $document->paid_on?->format('j F Y');
    if ($isReceipt) {
        $statement = 'Received'
            .($totals['received'] !== null ? ' '.$money($totals['received']) : '')
            .($document->method ? ' by '.strtolower($document->method) : '')
            .($paidOn ? ' on '.$paidOn : '')
            .($document->reference ? ' (ref '.$document->reference.')' : '');
    } else {
        $statement = 'Paid'
            .($document->method ? ' by '.strtolower($document->method) : '')
            .($paidOn ? ' on '.$paidOn : '')
            .' to '.$document->to_name;
    }
@endphp
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 36px 44px; }
        body { font-family: Helvetica, Arial, sans-serif; color: #1f2937; font-size: 11px; }
        .brand { color: #4b5a9a; font-weight: bold; font-size: 14px; letter-spacing: 0.5px; margin-bottom: 18px; }
        .layout { width: 100%; }
        .layout td { vertical-align: top; }
        .side { width: 150px; color: #4b5a9a; font-weight: bold; line-height: 1.8; }
        .side .label { color: #374151; font-weight: normal; font-size: 16px; line-height: 1.2; margin-bottom: 12px; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        h2 { font-size: 15px; margin: 0 0 14px; }
        .meta { margin-bottom: 12px; line-height: 1.6; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.lines th { background: #8ea0d4; color: #ffffff; text-align: left; padding: 6px 8px; font-size: 11px; }
        table.lines td { padding: 6px 8px; border-bottom: 1px dotted #c7cbd4; }
        .num { text-align: right; white-space: nowrap; }
        table.sum { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.sum td { padding: 6px 8px; }
        table.sum tr.rule td { border-top: 1px solid #111111; }
        .strong { font-weight: bold; }
        .thanks { margin-top: 24px; line-height: 1.8; }
    </style>
</head>
<body>
    <div class="brand">TRINITY COLLEGE LONDON / {{ strtoupper($rep['name']) }}</div>

    <table class="layout">
        <tr>
            <td class="side">
                <div class="label">{{ $isReceipt ? 'RECEIPT FROM:' : 'PAYMENT FROM:' }}</div>
                TEL: {{ $rep['phone'] }}<br>
                {{ $rep['name'] }}<br>
                @foreach ($rep['address'] as $line){{ $line }}<br>@endforeach
                {{ $rep['email'] }}<br><br>
                On behalf of:<br>
                {{ $rep['on_behalf_of'] }}
            </td>
            <td>
                <h1>{{ \App\Services\PaymentDocuments::KINDS[$document->kind] }} {{ $document->number }}</h1>
                <h2>{{ $document->title ? $document->title.' – ' : '' }}{{ $statement }}</h2>

                <div class="meta">
                    <strong>Date:</strong> {{ $document->issued_on->format('j F Y') }}<br>
                    @if ($document->order)
                        <strong>Trinity order:</strong> {{ $document->order->trinity_order_number }}<br>
                    @endif
                    <strong>To:</strong> {{ $document->to_name }}
                    @if ($document->to_detail)<br>{!! nl2br(e($document->to_detail)) !!}@endif
                </div>

                <table class="lines">
                    <tr>
                        <th>Description</th>
                        <th>Date</th>
                        <th>Detail</th>
                        <th class="num">Qty</th>
                        <th class="num">Unit price</th>
                        <th class="num">Cost</th>
                    </tr>
                    @foreach ($document->lines as $line)
                        <tr>
                            <td>{{ $line->description }}</td>
                            <td>{{ $line->line_date?->format('d/m/Y') }}</td>
                            <td>{{ $line->detail }}</td>
                            <td class="num">{{ rtrim(rtrim(number_format((float) $line->quantity, 2), '0'), '.') }}</td>
                            <td class="num">{{ $money((int) round((float) $line->unit_price * 100)) }}</td>
                            <td class="num">{{ $money($service->linePence($line->quantity, $line->unit_price)) }}</td>
                        </tr>
                    @endforeach
                </table>

                <table class="sum">
                    <tr class="rule">
                        <td class="strong">Total</td>
                        <td class="num strong">{{ $money($totals['total']) }}</td>
                    </tr>
                    @if ($totals['received'] !== null)
                        <tr>
                            <td>Received</td>
                            <td class="num">{{ $money($totals['received']) }}</td>
                        </tr>
                        <tr class="rule">
                            <td class="strong">{{ $service->balanceLabel($totals['balance']) }}</td>
                            <td class="num strong">{{ $totals['balance'] === 0 ? '' : $money($totals['balance']) }}</td>
                        </tr>
                    @endif
                </table>

                @if ($document->notes)
                    <p>{!! nl2br(e($document->notes)) !!}</p>
                @endif

                <div class="thanks">
                    Many thanks<br>
                    {{ $rep['name'] }}<br>
                    on behalf of {{ $rep['on_behalf_of'] }}
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
