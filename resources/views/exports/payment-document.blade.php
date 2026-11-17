{{-- resources/views/exports/payment-document.blade.php --}}
{{--
    A payment advice (Paul paid someone on Trinity's behalf) or a receipt (Paul
    was paid exam fees). Every figure comes from App\Services\PaymentDocuments.
    dompdf: layout is tables only (no flex/grid); the footer is position:fixed.
--}}
@php
    $isReceipt = $document->kind === \App\Models\PaymentDocument::KIND_RECEIPT;
    $money = fn (int $pence) => \App\Services\PaymentDocuments::money($pence);
    $kindLabel = $isReceipt ? 'RECEIPT' : 'PAYMENT ADVICE';
    $qty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');
@endphp
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body { margin: 0; font-family: Helvetica, Arial, sans-serif; color: #1f2937; font-size: 10.5px; }
        .page { padding: 44px 52px 110px; }
        table { border-collapse: collapse; }
        .w100 { width: 100%; }
        .top td { vertical-align: top; }
        .name { font-size: 19px; font-weight: bold; color: #1e2a4a; }
        .role { font-size: 10px; color: #5b6b8c; margin-top: 3px; }
        .contact { font-size: 10px; color: #4b5563; line-height: 1.6; margin-top: 12px; }
        .doc-title { font-size: 22px; font-weight: bold; letter-spacing: 2px; color: #1e2a4a; text-align: right; }
        .meta { margin-top: 12px; margin-left: auto; }
        .meta td { padding: 2px 0 2px 14px; font-size: 10.5px; }
        .meta .k { color: #6b7280; text-align: right; }
        .meta .v { font-weight: bold; text-align: right; color: #1f2937; }
        .rule { height: 4px; background: #2f5bd3; margin: 26px 0 22px; }
        .boxes td.box { width: 50%; vertical-align: top; padding: 12px 14px; background: #f5f7fb; border: 1px solid #e3e8f2; }
        .boxes td.gap { width: 14px; }
        .label { font-size: 8.5px; font-weight: bold; letter-spacing: 1.2px; color: #6b7280; text-transform: uppercase; margin-bottom: 6px; }
        .box-main { font-size: 12px; font-weight: bold; color: #1e2a4a; }
        .box-sub { font-size: 10px; color: #4b5563; line-height: 1.5; margin-top: 3px; }
        .subject { font-size: 13px; font-weight: bold; color: #1e2a4a; margin: 24px 0 8px; }
        .lines th { background: #1e2a4a; color: #ffffff; font-size: 9.5px; font-weight: bold; text-align: left; padding: 8px 9px; }
        .lines td { padding: 8px 9px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .lines tr.alt td { background: #f8fafc; }
        .num { text-align: right; white-space: nowrap; }
        .totals { margin-top: 14px; margin-left: auto; width: 46%; }
        .totals td { padding: 7px 9px; font-size: 11px; }
        .totals .k { color: #4b5563; }
        .totals tr.sep td { border-top: 1px solid #d1d5db; }
        .totals tr.grand td { background: #1e2a4a; color: #ffffff; font-weight: bold; font-size: 12.5px; }
        .notes { margin-top: 26px; padding: 12px 14px; border-left: 3px solid #2f5bd3; background: #f5f7fb; font-size: 10.5px; line-height: 1.5; }
        .sign { margin-top: 30px; font-size: 10.5px; line-height: 1.7; color: #374151; }
        .footer { position: fixed; bottom: 34px; left: 52px; right: 52px; border-top: 1px solid #d1d5db; padding-top: 9px; font-size: 8.5px; color: #6b7280; }
        .footer td { vertical-align: top; }
    </style>
</head>
<body>
<div class="footer">
    <table class="w100">
        <tr>
            <td>{{ $isReceipt ? 'Received' : 'Paid' }} on behalf of {{ $rep['on_behalf_of'] }}</td>
            <td style="text-align: right;">{{ $rep['name'] }} · {{ $rep['email'] }} · {{ $rep['phone'] }}</td>
        </tr>
    </table>
</div>

<div class="page">
    <table class="w100 top">
        <tr>
            <td style="width: 55%;">
                <div class="name">{{ $rep['name'] }}</div>
                <div class="role">{{ $rep['role'] }}</div>
                <div class="contact">
                    {{ implode(', ', $rep['address']) }}<br>
                    {{ $rep['phone'] }} · {{ $rep['email'] }}
                </div>
            </td>
            <td style="width: 45%;">
                <div class="doc-title">{{ $kindLabel }}</div>
                <table class="meta">
                    <tr><td class="k">Number</td><td class="v">{{ $document->number }}</td></tr>
                    <tr><td class="k">Date</td><td class="v">{{ $document->issued_on->format('j F Y') }}</td></tr>
                    @if ($document->order)
                        <tr><td class="k">Trinity order</td><td class="v">{{ $document->order->trinity_order_number }}</td></tr>
                    @endif
                    @if ($document->reference)
                        <tr><td class="k">Reference</td><td class="v">{{ $document->reference }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    <table class="w100 boxes">
        <tr>
            <td class="box">
                <div class="label">{{ $isReceipt ? 'Received from' : 'Paid to' }}</div>
                <div class="box-main">{{ $document->to_name }}</div>
                @if ($document->to_detail)
                    <div class="box-sub">{!! nl2br(e($document->to_detail)) !!}</div>
                @endif
            </td>
            <td class="gap"></td>
            <td class="box">
                <div class="label">{{ $isReceipt ? 'Payment received' : 'Payment' }}</div>
                @if ($isReceipt && $totals['received'] !== null)
                    <div class="box-main">{{ $money($totals['received']) }}</div>
                @else
                    <div class="box-main">{{ $money($totals['total']) }}</div>
                @endif
                <div class="box-sub">
                    @if ($document->method){{ $document->method }}@endif
                    @if ($document->method && $document->paid_on) · @endif
                    @if ($document->paid_on){{ $document->paid_on->format('j F Y') }}@endif
                </div>
            </td>
        </tr>
    </table>

    @if ($document->title)
        <div class="subject">{{ $document->title }}</div>
    @else
        <div style="height: 22px;"></div>
    @endif

    <table class="w100 lines">
        <tr>
            <th>Description</th>
            <th style="width: 72px;">Date</th>
            <th style="width: 90px;">Detail</th>
            <th class="num" style="width: 40px;">Qty</th>
            <th class="num" style="width: 66px;">Unit price</th>
            <th class="num" style="width: 70px;">Amount</th>
        </tr>
        @foreach ($document->lines as $i => $line)
            <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
                <td>{{ $line->description }}</td>
                <td>{{ $line->line_date?->format('d/m/Y') }}</td>
                <td>@if ($line->start_time && $line->end_time){{ $line->start_time }}–{{ $line->end_time }}@if ($line->detail)<br>@endif @endif{{ $line->detail }}</td>
                <td class="num">{{ $qty($line->quantity) }}</td>
                <td class="num">{{ $money((int) round((float) $line->unit_price * 100)) }}</td>
                <td class="num">{{ $money($service->linePence($line->quantity, $line->unit_price)) }}</td>
            </tr>
        @endforeach
    </table>

    <table class="totals">
        @if ($totals['received'] !== null)
            <tr><td class="k">Total</td><td class="num">{{ $money($totals['total']) }}</td></tr>
            <tr><td class="k">Received</td><td class="num">{{ $money($totals['received']) }}</td></tr>
            <tr class="grand">
                <td>{{ $service->balanceLabel($totals['balance']) }}</td>
                <td class="num">{{ $totals['balance'] === 0 ? '' : $money($totals['balance']) }}</td>
            </tr>
        @else
            <tr class="grand"><td>Total {{ $isReceipt ? '' : 'paid' }}</td><td class="num">{{ $money($totals['total']) }}</td></tr>
        @endif
    </table>

    @if ($document->notes)
        <div class="notes">{!! nl2br(e($document->notes)) !!}</div>
    @endif

    <div class="sign">
        Many thanks,<br>
        <strong>{{ $rep['name'] }}</strong><br>
        on behalf of {{ $rep['on_behalf_of'] }}
    </div>
</div>
</body>
</html>
