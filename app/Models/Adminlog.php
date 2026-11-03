<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A short record of something an administrator did (shown on the admin Overview). */
class AdminLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['admin_id', 'action', 'subject_type', 'subject_id', 'summary', 'note'];

    protected $casts = ['created_at' => 'datetime'];

    public static function record(?User $admin, string $action, string $summary, ?string $subjectType = null, ?string $subjectId = null, ?string $note = null): self
    {
        return static::create([
            'admin_id' => $admin?->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'summary' => mb_substr($summary, 0, 255),
            'note' => $note,
        ]);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}