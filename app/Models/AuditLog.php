<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['actor_id', 'action', 'subject_type', 'subject_id', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public static function record(string $action, ?int $actorId = null, ?string $subjectType = null, ?string $subjectId = null, array $meta = []): void
    {
        static::create(['actor_id' => $actorId, 'action' => $action, 'subject_type' => $subjectType, 'subject_id' => $subjectId, 'meta' => $meta ?: null]);
    }
}
