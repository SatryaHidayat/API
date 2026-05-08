<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOrderQueue implements ShouldQueue
{
use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

// Variabel untuk menampung data yang dikirim dari antrean
protected $orderPayload;

/**
* Create a new job instance.
*/
public function __construct($orderPayload)
{
// Menyimpan data ke dalam variabel class saat Job dipanggil (dispatched)
$this->orderPayload = $orderPayload;
}

/**
* Execute the job.
*/
public function handle(): void
{
// Di sinilah kamu menulis logika berat atau proses antreannya
Log::info('Memulai pemrosesan antrean order...');

try {
// Contoh eksekusi:
// 1. Simpan data order ke database MySQL
// 2. Tembak API ke service lain (misalnya InventoryService)
// 3. Kirim notifikasi

Log::info('Data Order Berhasil Diproses: ', $this->orderPayload);

} catch (\Exception $e) {
// Tangkap error jika ada kegagalan proses
Log::error('Gagal memproses order: ' . $e->getMessage());

// Jika ingin mengulang proses (retry), kamu bisa melempar error-nya kembali
// throw $e;
}
}
}