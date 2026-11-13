<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Laporan siswa bahwa hasil rekomendasinya terasa tidak sesuai. */
class ResultReport extends Model
{
    public const CATEGORIES = [
        'tidak_sesuai_minat' => 'Tidak sesuai dengan minatku',
        'tidak_nyambung' => 'Sekolah, kuliah, dan karier tidak nyambung',
        'kampus' => 'Kampus atau akreditasi kurang tepat',
        'data_salah' => 'Ada data yang salah (nama, jenjang, dll.)',
        'lainnya' => 'Lainnya',
    ];

    protected $fillable = ['test_session_id', 'user_id', 'category', 'message', 'status', 'admin_note', 'resolved_by', 'resolved_at'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TestSession::class, 'test_session_id');
    }
}
