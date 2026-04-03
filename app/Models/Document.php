<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use Auditable, HasFactory;

    protected $table = 'documents';

    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'uploaded_by',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'uploaded_by');
    }
}
