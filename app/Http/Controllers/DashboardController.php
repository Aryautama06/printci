<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PaymentCategory;
use App\Models\Printer;
use App\Models\ReceiptTemplate;
use App\Models\Transaction;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $transactions = Transaction::with(['customer', 'paymentCategory'])->latest()->paginate(8);

        return view('dashboard', [
            'transactions' => $transactions,
            'customers' => Customer::orderBy('name')->get(),
            'categories' => PaymentCategory::where('is_active', true)->orderBy('name')->get(),
            'templates' => ReceiptTemplate::orderByDesc('is_default')->orderBy('name')->get(),
            'printers' => Printer::latest('last_seen_at')->get(),
            'stats' => [
                'today' => Transaction::whereDate('created_at', today())->count(),
                'revenue' => Transaction::where('status', 'paid')->whereDate('created_at', today())->sum('amount'),
                'pending' => Transaction::where('status', 'draft')->count(),
                'printed' => Transaction::where('status', 'paid')->whereNotNull('printed_at')->count(),
            ],
        ]);
    }
}
