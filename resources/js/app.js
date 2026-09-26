import { createWorker } from 'tesseract.js';

const escPos = { init: new Uint8Array([0x1b, 0x40]), alignCenter: new Uint8Array([0x1b, 0x61, 0x01]), alignLeft: new Uint8Array([0x1b, 0x61, 0x00]), boldOn: new Uint8Array([0x1b, 0x45, 0x01]), boldOff: new Uint8Array([0x1b, 0x45, 0x00]), cut: new Uint8Array([0x1d, 0x56, 0x00]) };
const encoder = new TextEncoder();
let bluetoothDevice;
function openModal(id) { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }
document.querySelectorAll('[data-open]').forEach((button) => button.addEventListener('click', () => openModal(button.dataset.open)));
document.querySelectorAll('[data-close]').forEach((button) => button.addEventListener('click', () => closeModal(button.dataset.close)));
document.querySelectorAll('.modal-backdrop').forEach((modal) => modal.addEventListener('click', (event) => { if (event.target === modal) modal.classList.remove('open'); }));
document.querySelector('[data-table-search]')?.addEventListener('input', (event) => { const query = event.target.value.toLowerCase(); document.querySelectorAll('[data-table-row]').forEach((row) => { row.hidden = !row.textContent.toLowerCase().includes(query); }); });
const transactionTable = document.querySelector('.transactions-panel table');
const statusFilter = document.querySelector('[data-status-filter]') || document.querySelector('.transactions-panel .select-control');
if (transactionTable) {
    const headerRow = transactionTable.querySelector('thead tr');
    const rows = [...transactionTable.querySelectorAll('tbody tr[data-table-row]')];
    const numberHeader = document.createElement('th');
    numberHeader.textContent = 'No.';
    headerRow?.prepend(numberHeader);
    const dateHeader = document.createElement('th');
    dateHeader.textContent = 'Tanggal & waktu';
    headerRow?.children[1]?.after(dateHeader);
    rows.forEach((row, index) => {
        const numberCell = document.createElement('td');
        numberCell.className = 'row-number';
        numberCell.textContent = String(index + 1).padStart(2, '0');
        row.prepend(numberCell);
        const transactionCell = row.cells[1];
        const dateText = transactionCell?.querySelector('small')?.textContent;
        if (dateText) {
            const dateCell = document.createElement('td');
            dateCell.className = 'transaction-date';
            dateCell.innerHTML = `<strong>${dateText.split(',')[0]}</strong><small>${dateText.split(',')[1]?.trim() || ''} WIB</small>`;
            transactionCell.querySelector('small').remove();
            transactionCell.after(dateCell);
        }
    });
}
statusFilter?.addEventListener('change', (event) => { const selected = { 'Semua status': 'all', Lunas: 'paid', Draf: 'draft', Dibatalkan: 'void' }[event.target.value] || event.target.value; document.querySelectorAll('[data-table-row]').forEach((row) => { row.hidden = selected !== 'all' && row.querySelector('.status')?.classList.contains(selected) !== true; }); });
document.querySelectorAll('[data-copy]').forEach((button) => button.addEventListener('click', async () => { await navigator.clipboard?.writeText(button.dataset.copy); button.textContent = '✓'; setTimeout(() => { button.textContent = '⋮'; }, 1200); }));
const realtimeClock = document.querySelector('#realtime-clock');
const realtimeGreeting = document.querySelector('#realtime-greeting');
const paidAtInput = document.querySelector('[name="paid_at"]');
function localDateTimeValue(date = new Date()) {
    const offset = date.getTimezoneOffset() * 60000;
    return new Date(date.getTime() - offset).toISOString().slice(0, 16);
}
function updateRealtimeClock() {
    const now = new Date();
    if (realtimeClock) realtimeClock.textContent = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' }).format(now);
    if (realtimeGreeting) {
        const hour = now.getHours();
        realtimeGreeting.textContent = hour >= 4 && hour < 11 ? 'Selamat pagi' : hour < 15 ? 'Selamat siang' : hour < 18 ? 'Selamat sore' : 'Selamat malam';
    }
}
updateRealtimeClock();
setInterval(updateRealtimeClock, 1000);
document.querySelectorAll('[data-open="scan-modal"]').forEach((button) => button.addEventListener('click', () => { if (paidAtInput) paidAtInput.value = localDateTimeValue(); }));
const proofInput = document.querySelector('#proof-input');
const proofAdded = document.querySelector('#proof-added');
const proofPreview = document.querySelector('#proof-preview');
const proofName = document.querySelector('#proof-name');
const proofStatus = document.querySelector('#proof-status');
const paymentAmount = document.querySelector('#payment-amount');
const summaryAmount = document.querySelector('#summary-amount');
const summaryTotal = document.querySelector('#summary-total');
const adminFee = 2500;
function formatRupiah(value) { return `Rp ${Number(value || 0).toLocaleString('id-ID')}`; }
function refreshPaymentSummary() {
    const amount = Number(paymentAmount?.value || 0);
    if (summaryAmount) summaryAmount.textContent = formatRupiah(amount);
    if (summaryTotal) summaryTotal.textContent = formatRupiah(amount + adminFee);
}
function setScanFields(data) {
    const fields = { amount: '#payment-amount', paid_at: '[name="paid_at"]', sender_name: '[name="sender_name"]', destination_bank: '[name="destination_bank"]', transaction_id: '[name="transaction_id"]' };
    Object.entries(fields).forEach(([key, selector]) => { if (data[key] && (key !== 'transaction_id' || !document.querySelector(selector).value)) document.querySelector(selector).value = data[key]; });
    refreshPaymentSummary();
}
function createTransactionId() { const suffix = crypto.randomUUID ? crypto.randomUUID().slice(0, 6) : Math.random().toString(36).slice(2, 8); return `TRX-${new Date().toISOString().replace(/\D/g, '').slice(2, 14)}-${suffix.toUpperCase()}`; }
function parseLocalOcr(text) {
    const lines = text.split(/\r?\n/).map((line) => line.trim()).filter(Boolean);
    const amounts = [...text.matchAll(/(?:rp|idr)\s*([\d.,]+)/gi)].map((match) => Number(match[1].replace(/[^\d]/g, ''))).filter(Boolean);
    const valueAfter = (labels) => { const lineIndex = lines.findIndex((line) => labels.some((label) => line.toLowerCase().includes(label))); if (lineIndex < 0) return ''; const sameLine = lines[lineIndex].split(/[:\-]/).slice(1).join(' ').trim(); return sameLine || lines[lineIndex + 1] || ''; };
    const dateMatch = text.match(/\b(\d{1,2})[\/-](\d{1,2})[\/-](\d{2,4})\b/);
    return { amount: amounts.sort((a, b) => b - a)[0] || '', paid_at: dateMatch ? `${dateMatch[3].length === 2 ? `20${dateMatch[3]}` : dateMatch[3]}-${dateMatch[2].padStart(2, '0')}-${dateMatch[1].padStart(2, '0')}T12:00` : '', sender_name: valueAfter(['nama pengirim', 'nama', 'pengirim', 'sender', 'from', 'beneficiary', 'recipient', 'penerima']), destination_bank: valueAfter(['bank tujuan', 'nama bank', 'bank', 'merchant']), transaction_id: valueAfter(['id transaksi', 'transaction id', 'reference', 'ref no']) };
}
async function readImageInBrowser(file) {
    const worker = await createWorker('eng');
    const result = await worker.recognize(file);
    await worker.terminate();
    return parseLocalOcr(result.data.text);
}
paymentAmount?.addEventListener('input', refreshPaymentSummary);
proofInput?.addEventListener('change', () => {
    const file = proofInput.files?.[0];
    if (!file) return;
    proofAdded.hidden = false;
    const transactionIdField = document.querySelector('[name="transaction_id"]');
    if (transactionIdField && !transactionIdField.value) transactionIdField.value = createTransactionId();
    proofName.textContent = file.name;
    proofStatus.textContent = 'Foto berhasil ditambahkan. Membaca data pembayaran...';
    const reader = new FileReader();
    reader.onload = (event) => { proofPreview.src = event.target.result; proofStatus.textContent = 'Foto siap dibaca. Nominal dan data dapat diperiksa sebelum disimpan.'; };
    reader.readAsDataURL(file);
    refreshPaymentSummary();
    const formData = new FormData();
    formData.append('proof', file);
    proofStatus.textContent = 'Foto berhasil ditambahkan. Sedang membaca data pembayaran...';
    fetch('/transactions/analyze-proof', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' }, body: formData })
        .then((response) => response.json())
        .then((data) => {
            if (data.confidence > 0) {
                setScanFields(data);
                proofStatus.textContent = 'Data berhasil diisi otomatis oleh OCR API. Silakan periksa sebelum menyimpan.';
                return;
            }
            proofStatus.textContent = 'API belum tersedia. Membaca gambar langsung di browser...';
            return readImageInBrowser(file).then((localData) => { setScanFields(localData); proofStatus.textContent = localData.amount || localData.sender_name ? 'Data berhasil dibaca dari gambar. Silakan periksa sebelum menyimpan.' : 'Teks pada gambar belum terbaca. Gunakan foto yang lebih jelas.'; });
        })
        .catch(() => readImageInBrowser(file).then((localData) => { setScanFields(localData); proofStatus.textContent = localData.amount || localData.sender_name ? 'Data berhasil dibaca dari gambar. Silakan periksa sebelum menyimpan.' : 'Teks pada gambar belum terbaca. Gunakan foto yang lebih jelas.'; }).catch(() => { proofStatus.textContent = 'Foto tersimpan. OCR gagal membaca gambar, silakan isi data manual.'; }));
});
document.querySelector('#proof-change')?.addEventListener('click', () => proofInput?.click());
async function connectThermalPrinter(button) {
    if (!window.isSecureContext && !['localhost', '127.0.0.1'].includes(window.location.hostname)) {
        const localUrl = new URL(window.location.href);
        localUrl.hostname = 'localhost';
        window.alert('Koneksi Bluetooth membutuhkan halaman aman. Halaman akan dialihkan ke localhost. Klik Hubungkan printer lagi setelah halaman terbuka.');
        window.location.href = localUrl.toString();
        return;
    }
    if (!navigator.bluetooth) { window.alert('Web Bluetooth tidak tersedia. Gunakan Google Chrome atau Microsoft Edge versi terbaru.'); return; }
    try {
        button.disabled = true;
        button.querySelector('span').textContent = 'Menunggu perangkat...';
        bluetoothDevice = await navigator.bluetooth.requestDevice({ acceptAllDevices: true, optionalServices: [0x18f0, '000018f0-0000-1000-8000-00805f9b34fb'] });
        const server = await bluetoothDevice.gatt.connect();
        const service = await server.getPrimaryService(0x18f0).catch(() => server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb'));
        const characteristics = await service.getCharacteristics();
        const writable = characteristics.find((item) => item.properties.write || item.properties.writeWithoutResponse);
        if (!writable) throw new Error('Karakteristik printer tidak dapat ditulis.');
        window.printciPrinter = { device: bluetoothDevice, characteristic: writable };
        button.querySelector('span').textContent = 'Printer terhubung';
        button.closest('.panel').querySelector('.device-status')?.classList.replace('offline', 'online');
        button.closest('.panel').querySelector('.device-info small').textContent = '58mm · Terhubung';
        await fetch(`/printers/${button.dataset.printerId}/status`, { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify({ status: 'online' }) });
    } catch (error) {
        button.disabled = false;
        button.querySelector('span').textContent = 'Hubungkan printer';
        window.alert(`Printer tidak terhubung: ${error.message}`);
    }
}
document.querySelectorAll('[data-connect-printer]').forEach((button) => button.addEventListener('click', () => connectThermalPrinter(button)));
window.printciEscPos = { async printReceipt(lines) { const characteristic = window.printciPrinter?.characteristic; if (!characteristic) throw new Error('Hubungkan printer Bluetooth terlebih dahulu.'); const chunks = [escPos.init, escPos.alignCenter, escPos.boldOn, encoder.encode(lines.header), escPos.boldOff, escPos.alignLeft, encoder.encode(`\n${lines.body}\n`), escPos.alignCenter, encoder.encode(`${lines.footer}\n\n`), escPos.cut]; for (const chunk of chunks) await characteristic.writeValue(chunk); } };
