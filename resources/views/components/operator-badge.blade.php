@props(['operator' => null])

@php
    $op = strtolower($operator ?? '');
@endphp

@if($op === 'airtel')
    <span style="display:inline-flex;align-items:center;padding:3px 8px;border-radius:12px;background:#fee2e2;color:#b91c1c;font-size:12px;font-weight:700;">
        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#e60000;margin-right:6px;"></span>
        Airtel Money
    </span>
@elseif($op === 'vodacom')
    <span style="display:inline-flex;align-items:center;padding:3px 8px;border-radius:12px;background:#fee2e2;color:#b91c1c;font-size:12px;font-weight:700;">
        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#e60000;margin-right:6px;"></span>
        M-Pesa (Vodacom)
    </span>
@elseif($op === 'orange')
    <span style="display:inline-flex;align-items:center;padding:3px 8px;border-radius:12px;background:#ffedd5;color:#c2410c;font-size:12px;font-weight:700;">
        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#ff7900;margin-right:6px;"></span>
        Orange Money
    </span>
@elseif($op === 'africell')
    <span style="display:inline-flex;align-items:center;padding:3px 8px;border-radius:12px;background:#f3e8ff;color:#6b21a8;font-size:12px;font-weight:700;">
        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#682c91;margin-right:6px;"></span>
        AfriMoney
    </span>
@else
    <span style="display:inline-flex;align-items:center;padding:3px 8px;border-radius:12px;background:#f1f5f9;color:#475569;font-size:12px;font-weight:600;">
        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#94a3b8;margin-right:6px;"></span>
        Mobile Money RDC
    </span>
@endif
