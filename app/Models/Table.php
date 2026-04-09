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

    protected $appends = [
        'id_table',
    ];

    protected static function booted()
    {
        static::creating(function ($table) {
            if ($table->seating_type === 'kursi') {
                $table->seating_type = 'chair';
            }

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
            $url = route('menu.scan', ['scanHash' => self::encodeScanToken($table->table_number)], true);
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

    public function getIdTableAttribute(): int
    {
        return (int) $this->attributes['id'];
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'table_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'id_table');
    }

    public static function encodeScanToken(string $tableNumber): string
    {
        return rtrim(strtr(base64_encode($tableNumber), '+/', '-_'), '=');
    }

    public static function decodeScanToken(string $token): ?string
    {
        $decoded = base64_decode(strtr($token, '-_', '+/') . str_repeat('=', (4 - strlen($token) % 4) % 4), true);

        if ($decoded === false) {
            return null;
        }

        if (! preg_match('/^(K|L)-\d+$/', $decoded)) {
            return null;
        }

        return $decoded;
    }
}
