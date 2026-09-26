<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PaymentCategory;
use App\Models\Printer;
use App\Models\ReceiptTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ManagementController extends Controller
{
    public function customer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'segment' => ['required', 'string', 'max:30'],
        ]);
        Customer::updateOrCreate(['id' => $request->integer('id') ?: null], $data);

        return back()->with('success', 'Data pelanggan berhasil disimpan.');
    }

    public function category(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => ['required', 'string', 'max:40'],
        ]);
        PaymentCategory::updateOrCreate(['id' => $request->integer('id') ?: null], $data + ['is_active' => $request->boolean('is_active', true)]);

        return back()->with('success', 'Kategori pembayaran berhasil disimpan.');
    }

    public function template(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'paper_size' => ['required', 'in:58mm,80mm'],
            'font_size' => ['required', 'in:small,normal,large'],
            'header' => ['nullable', 'string', 'max:1000'],
            'footer' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['elements'] = [
            'customer' => $request->boolean('show_customer'),
            'bank' => $request->boolean('show_bank'),
            'transaction_id' => $request->boolean('show_transaction_id'),
        ];
        $data['is_default'] = $request->boolean('is_default');
        if ($data['is_default']) {
            ReceiptTemplate::where('is_default', true)->update(['is_default' => false]);
        }
        ReceiptTemplate::updateOrCreate(['id' => $request->integer('id') ?: null], $data);

        return back()->with('success', 'Template struk berhasil disimpan.');
    }

    public function printer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'bluetooth_id' => ['nullable', 'string', 'max:255'],
            'paper_size' => ['required', 'in:58mm,80mm'],
        ]);
        Printer::updateOrCreate(['id' => $request->integer('id') ?: null], $data + ['connection_type' => 'bluetooth', 'status' => 'offline']);

        return back()->with('success', 'Perangkat printer berhasil disimpan.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $models = ['customer' => Customer::class, 'category' => PaymentCategory::class, 'template' => ReceiptTemplate::class, 'printer' => Printer::class];
        abort_unless(isset($models[$type]), 404);
        $models[$type]::findOrFail($id)->delete();

        return back()->with('success', 'Data berhasil dihapus.');
    }
}
