<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class VisionProofExtractor
{
    /**
     * @return array<string, mixed>
     */
    public function extract(UploadedFile $proof): array
    {
        $endpoint = config('services.vision.url');
        $key = config('services.vision.key');

        if (! $endpoint || ! $key || $key === 'your-api-key') {
            return $this->manualReview($proof, 'Masukkan VISION_API_KEY OCR.space di file .env untuk mengisi data otomatis.');
        }

        try {
            $response = Http::asMultipart()
                ->timeout(45)
                ->attach('file', $proof->getContent(), $proof->getClientOriginalName())
                ->post($endpoint, [
                    'apikey' => $key,
                    'language' => 'eng',
                    'isOverlayRequired' => 'false',
                    'OCREngine' => '2',
                    'scale' => 'true',
                ]);
        } catch (\Throwable) {
            return $this->manualReview($proof, 'OCR.space tidak dapat dihubungi. Periksa koneksi internet dan API key.');
        }

        if ($response->failed()) {
            return $this->manualReview($proof, 'OCR.space menolak permintaan. Periksa API key dan batas penggunaan akun.');
        }

        $payload = $response->json();
        if (($payload['IsErroredOnProcessing'] ?? false) === true) {
            return $this->manualReview($proof, $payload['ErrorMessage'][0] ?? 'Gambar tidak dapat dibaca oleh OCR.space.');
        }

        $text = collect($payload['ParsedResults'] ?? [])
            ->pluck('ParsedText')
            ->filter()
            ->implode("\n");

        if ($text === '') {
            return $this->manualReview($proof, 'OCR.space tidak menemukan teks pada gambar. Gunakan foto yang lebih jelas.');
        }

        return array_merge([
            'provider' => 'ocr-space',
            'confidence' => 75,
            'source' => $proof->getClientOriginalName(),
            'raw_text' => $text,
        ], $this->parsePaymentText($text));
    }

    /**
     * @return array<string, mixed>
     */
    private function parsePaymentText(string $text): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: [])));
        $amount = $this->findAmount($lines);
        $date = $this->findDate($text);
        $transactionId = $this->findLineValue($lines, ['id transaksi', 'transaction id', 'reference', 'ref no', 'nomor referensi']);
        $sender = $this->findLineValue($lines, ['nama pengirim', 'nama', 'pengirim', 'sender', 'from', 'beneficiary', 'recipient', 'penerima']);
        $bank = $this->findLineValue($lines, ['bank tujuan', 'nama bank', 'bank', 'merchant']);

        return [
            'amount' => $amount,
            'paid_at' => $date,
            'sender_name' => $sender,
            'destination_bank' => $bank,
            'transaction_id' => $transactionId,
            'message' => 'Data berhasil dibaca otomatis oleh OCR.space. Silakan periksa sebelum menyimpan.',
        ];
    }

    private function findAmount(array $lines): ?float
    {
        $candidates = [];
        foreach ($lines as $line) {
            if (! preg_match_all('/(?:rp\.?|idr)\s*([\d.,\s]+)/i', $line, $matches)) {
                continue;
            }
            foreach ($matches[1] as $value) {
                $digits = preg_replace('/[^0-9]/', '', $value);
                if ($digits !== '' && (int) $digits > 0) {
                    $candidates[] = (int) $digits;
                }
            }
        }

        return $candidates ? (float) max($candidates) : null;
    }

    private function findDate(string $text): ?string
    {
        if (! preg_match('/\b(\d{1,2})[\/-](\d{1,2})[\/-](\d{2,4})\b/', $text, $match)) {
            return null;
        }

        $year = strlen($match[3]) === 2 ? '20'.$match[3] : $match[3];

        return sprintf('%04d-%02d-%02dT12:00', $year, $match[2], $match[1]);
    }

    private function findLineValue(array $lines, array $labels): ?string
    {
        foreach ($lines as $index => $line) {
            foreach ($labels as $label) {
                if (stripos($line, $label) === false) {
                    continue;
                }
                $value = trim((string) preg_replace('/^.*?'.preg_quote($label, '/').'\s*[:\-]?\s*/i', '', $line));
                if ($value !== '' && strcasecmp($value, $line) !== 0) {
                    return $value;
                }
                if (isset($lines[$index + 1]) && $lines[$index + 1] !== '') {
                    return $lines[$index + 1];
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function manualReview(UploadedFile $proof, string $message): array
    {
        return [
            'provider' => 'manual-review',
            'confidence' => 0,
            'source' => $proof->getClientOriginalName(),
            'message' => $message,
            'amount' => null,
            'paid_at' => null,
            'sender_name' => null,
            'destination_bank' => null,
            'transaction_id' => null,
        ];
    }
}
