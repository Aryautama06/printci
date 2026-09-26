@php
    $template = $transaction->receiptTemplate;
    $paperWidth = $template?->paper_size === '80mm' ? '80mm' : '58mm';
    $statusLabel = ['paid' => 'LUNAS', 'draft' => 'DRAF', 'void' => 'DIBATALKAN'][$transaction->status] ?? strtoupper($transaction->status);
    $totalAmount = $transaction->total_amount ?: ((float) $transaction->amount + (float) ($transaction->admin_fee ?: 2500));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $transaction->invoice_number }} | Printci</title>
    <style>
        :root{--paper-width:{{ $paperWidth }};--ink:#172033;--muted:#667085;--line:#d9dee8;--accent:#675ee8;--success:#087f5b}
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;background:#eef1f7;color:var(--ink);font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.4}
        .preview-bar{width:min(620px,calc(100% - 32px));margin:24px auto 14px;display:flex;justify-content:space-between;align-items:center;color:#667085;font-size:12px}
        .preview-bar strong{color:#172033;font-size:13px}.preview-bar button{border:0;border-radius:7px;background:#675ee8;color:white;padding:9px 14px;cursor:pointer;font-weight:700}
        .receipt{width:var(--paper-width);max-width:calc(100vw - 32px);margin:0 auto 40px;background:white;box-shadow:0 18px 50px #1720331f;padding:22px 16px;color:var(--ink)}
        .receipt-header{text-align:center;padding-bottom:16px;border-bottom:1px dashed #9da6b5}.brand{display:flex;justify-content:center;align-items:center;gap:8px;font-size:19px;font-weight:800;letter-spacing:-.7px}.brand-mark{display:grid;place-items:center;width:27px;height:27px;border-radius:8px;background:linear-gradient(135deg,#837bff,#574fd0);color:white;font-size:15px;box-shadow:0 4px 12px #675ee833}.brand-dot{color:var(--accent)}.store-header{white-space:pre-line;margin:9px 0 0;font-weight:700;font-size:12px}.invoice{margin-top:10px;color:var(--muted);font-size:10px;letter-spacing:.5px}.status{display:inline-block;margin-top:10px;padding:4px 9px;border-radius:20px;background:#e5f7f0;color:var(--success);font-size:9px;font-weight:800;letter-spacing:.7px}.status.draft{background:#fff4d8;color:#9a6700}.status.void{background:#ffe5e9;color:#bd243c}.section{padding:14px 0;border-bottom:1px solid var(--line)}.section-title{margin:0 0 10px;color:var(--muted);font-size:9px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase}.detail{display:flex;justify-content:space-between;gap:14px;margin:7px 0}.detail span:first-child{color:var(--muted)}.detail span:last-child{max-width:65%;text-align:right;overflow-wrap:anywhere}.amounts{padding:14px 0;border-bottom:1px dashed #9da6b5}.amount-row{display:flex;justify-content:space-between;margin:7px 0;color:var(--muted)}.amount-row strong{color:var(--ink)}.grand-total{display:flex;justify-content:space-between;align-items:end;margin-top:13px;padding:12px 0 0;border-top:2px solid var(--ink);font-size:14px;font-weight:800}.grand-total strong{color:var(--accent);font-size:18px}.receipt-footer{text-align:center;padding-top:18px;color:var(--muted);white-space:pre-line;font-size:10px}.thanks{color:var(--ink);font-weight:700;margin-bottom:5px}.print-meta{margin-top:15px;text-align:center;color:#98a1b1;font-size:8px}
        @media print{body{background:white;font-size:11px}.preview-bar{display:none}.receipt{width:var(--paper-width);max-width:none;margin:0;padding:4mm 3mm;box-shadow:none}.receipt-header{padding-bottom:3mm}.section{padding:3mm 0}.amounts{padding:3mm 0}.receipt-footer{padding-top:4mm}.print-meta{display:none}@page{size:auto;margin:0}}
    </style>
</head>
<body>
    <div class="preview-bar"><strong>Pratinjau struk pembayaran</strong><button type="button" onclick="window.print()">Cetak struk</button></div>
    <main class="receipt">
        <header class="receipt-header">
            <div class="brand"><span class="brand-mark">P</span><span>printci<span class="brand-dot">.</span></span></div>
            <div class="store-header">{{ $template?->header ?: 'Bukti pembayaran resmi' }}</div>
            <div class="invoice">{{ $transaction->invoice_number }}</div>
            <span class="status {{ $transaction->status === 'draft' ? 'draft' : ($transaction->status === 'void' ? 'void' : '') }}">{{ $statusLabel }}</span>
        </header>
        <section class="section">
            <h2 class="section-title">Informasi pembayaran</h2>
            <div class="detail"><span>Tanggal</span><span>{{ $transaction->paid_at?->format('d/m/Y H:i') ?: '-' }}</span></div>
            <div class="detail"><span>Nama pengirim</span><span>{{ $transaction->sender_name ?? $transaction->customer?->name ?? 'Pelanggan umum' }}</span></div>
            <div class="detail"><span>Bank tujuan</span><span>{{ $transaction->destination_bank ?: '-' }}</span></div>
            <div class="detail"><span>ID transaksi</span><span>{{ $transaction->transaction_id ?: '-' }}</span></div>
            @if($transaction->paymentCategory)<div class="detail"><span>Metode</span><span>{{ $transaction->paymentCategory->name }}</span></div>@endif
        </section>
        <section class="amounts">
            <h2 class="section-title">Rincian pembayaran</h2>
            <div class="amount-row"><span>Nominal pembayaran</span><strong>Rp {{ number_format($transaction->amount, 0, ',', '.') }}</strong></div>
            <div class="amount-row"><span>Biaya admin</span><strong>Rp {{ number_format($transaction->admin_fee ?: 2500, 0, ',', '.') }}</strong></div>
            <div class="grand-total"><span>Total dibayar</span><strong>Rp {{ number_format($totalAmount, 0, ',', '.') }}</strong></div>
        </section>
        <footer class="receipt-footer"><div class="thanks">Terima kasih atas pembayaran Anda.</div>{{ $template?->footer ?: 'Simpan struk ini sebagai bukti pembayaran yang sah.' }}<div class="print-meta">Dicetak oleh Printci · <span id="print-clock">{{ now()->format('d/m/Y H:i:s') }}</span></div></footer>
    </main>
    <script>function updatePrintClock(){const now=new Date();document.getElementById('print-clock').textContent=new Intl.DateTimeFormat('id-ID',{day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit',second:'2-digit'}).format(now)}updatePrintClock();setInterval(updatePrintClock,1000);window.addEventListener('load',()=>setTimeout(()=>window.print(),350));</script>
</body>
</html>
