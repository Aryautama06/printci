<?php

namespace App\Http\Controllers;

use App\Models\Printer;
use App\Models\PrintLog;
use App\Models\Transaction;
use App\Services\VisionProofExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(private readonly VisionProofExtractor $visionProofExtractor) {}

    public function analyzeProof(Request $request): JsonResponse
    {
        $validated = $request->validate(['proof' => ['required', 'image', 'max:5120']]);

        return response()->json($this->visionProofExtractor->extract($validated['proof']));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'payment_category_id' => ['nullable', 'exists:payment_categories,id'],
            'receipt_template_id' => ['nullable', 'exists:receipt_templates,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'paid_at' => ['nullable', 'date'],
            'sender_name' => ['nullable', 'string', 'max:255'],
            'destination_bank' => ['nullable', 'string', 'max:255'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'proof' => ['nullable', 'image', 'max:5120'],
        ]);

        $proof = $request->file('proof');
        if ($proof) {
            $data['proof_path'] = $proof->store('payment-proofs');
        }

        $data['uuid'] = (string) Str::uuid();
        $data['invoice_number'] = 'INV-'.now()->format('ymd-His').'-'.Str::upper(Str::random(4));
        if (empty($data['transaction_id']) || Transaction::where('transaction_id', $data['transaction_id'])->exists()) {
            $data['transaction_id'] = $this->generateTransactionId();
        }
        $data['status'] = 'paid';
        $data['admin_fee'] = 2500;
        $data['total_amount'] = (float) $data['amount'] + $data['admin_fee'];
        $data['paid_at'] ??= now();
        $data['ocr_data'] = $proof ? $this->visionProofExtractor->extract($proof) : null;

        Transaction::create($data);

        return back()->with('success', 'Transaksi berhasil dibuat dan siap dicetak.');
    }

    private function generateTransactionId(): string
    {
        do {
            $transactionId = 'TRX-'.now()->format('ymdHis').'-'.Str::upper(Str::random(6));
        } while (Transaction::where('transaction_id', $transactionId)->exists());

        return $transactionId;
    }

    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'payment_category_id' => ['nullable', 'exists:payment_categories,id'],
            'receipt_template_id' => ['nullable', 'exists:receipt_templates,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'paid_at' => ['nullable', 'date'],
            'sender_name' => ['nullable', 'string', 'max:255'],
            'destination_bank' => ['nullable', 'string', 'max:255'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['admin_fee'] = 2500;
        $data['total_amount'] = (float) $data['amount'] + $data['admin_fee'];

        $transaction->update($data);

        return back()->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        $transaction->delete();

        return back()->with('success', 'Transaksi berhasil dihapus.');
    }

    public function void(Transaction $transaction): RedirectResponse
    {
        $transaction->update(['status' => 'void']);

        return back()->with('success', 'Transaksi ditandai sebagai void.');
    }

    public function reprint(Transaction $transaction, Request $request): RedirectResponse
    {
        $printer = $request->validate(['printer_id' => ['nullable', 'exists:printers,id']]);
        $transaction->increment('print_count');
        $transaction->update(['printed_at' => now()]);
        PrintLog::create([
            'transaction_id' => $transaction->id,
            'printer_id' => $printer['printer_id'] ?? null,
            'status' => 'queued',
            'printed_at' => now(),
            'payload' => ['source' => 'web-bluetooth', 'paper_size' => $transaction->receiptTemplate?->paper_size ?? '58mm'],
        ]);

        return back()->with('success', 'Struk masuk antrean cetak Bluetooth.');
    }

    public function receipt(Transaction $transaction): View
    {
        return view('receipt', ['transaction' => $transaction->load(['customer', 'paymentCategory', 'receiptTemplate'])]);
    }

    public function export(): Response
    {
        $rows = Transaction::with(['customer', 'paymentCategory'])->latest()->get();
        $output = fopen('php://temp', 'w+');
        fputcsv($output, ['Invoice', 'Tanggal', 'Pelanggan', 'Kategori', 'Nominal', 'Status', 'ID Transaksi']);
        foreach ($rows as $transaction) {
            fputcsv($output, [$transaction->invoice_number, $transaction->created_at->format('Y-m-d H:i'), $transaction->customer?->name, $transaction->paymentCategory?->name, $transaction->amount, $transaction->status, $transaction->transaction_id]);
        }
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return response($content)->header('Content-Type', 'text/csv')->header('Content-Disposition', 'attachment; filename="printci-transactions.csv"');
    }

    public function printerStatus(Request $request, Printer $printer): Response
    {
        $printer->update(['status' => $request->string('status', 'online')->toString(), 'last_seen_at' => now(), 'battery_level' => $request->integer('battery_level') ?: $printer->battery_level]);

        return response()->json(['printer' => $printer->fresh()]);
    }
}
