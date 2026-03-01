<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class Table extends Model
{
    protected $fillable = [
        'table_number',
        'seating_type',
        'qr_code',
        'is_active',
    ];

    protected static function booted()
    {
        static::creating(function ($table) {
            // Generate nomor otomatis
            if ($table->seating_type) {
                $prefix = $table->seating_type === 'lesehan' ? 'L-' : 'K-';
                $lastRecord = self::where('seating_type', $table->seating_type)
                    ->get()
                    ->sortByDesc(function ($item) {
                        return intval(substr($item->table_number, 2));
                    })
                    ->first();

                $next = 1;
                if ($lastRecord) {
                    $last = intval(substr($lastRecord->table_number, 2));
                    $next = $last + 1;
                }
                $table->table_number = $prefix . $next;
            }

            // Generate dan simpan QR code langsung dalam format SVG
            $url = 'http://localhost:3000/scan/' . $table->table_number;
            $svg = QrCode::format('svg')->size(300)->generate($url);
            $filename = 'table-qr/' . $table->table_number . '.svg';
            Storage::disk('public')->put($filename, $svg);
            $table->qr_code = $filename;
        });

        static::deleting(function ($table) {
            // Hapus file
            if ($table->qr_code && Storage::disk('public')->exists($table->qr_code)) {
                Storage::disk('public')->delete($table->qr_code);
            }
        });
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'table_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'table_id');
    }
}
