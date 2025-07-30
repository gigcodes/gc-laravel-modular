<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Database\Factories\PasskeyFactory;

/**
 * @property string              $id
 * @property string              $user_id
 * @property string              $name
 * @property string              $credential_id
 * @property string              $public_key
 * @property int                 $sign_count
 * @property string|null         $aaguid
 * @property array               $transports
 * @property string              $type
 * @property array|null          $attestation_data
 * @property \Carbon\Carbon|null $last_used_at
 * @property \Carbon\Carbon      $created_at
 * @property \Carbon\Carbon      $updated_at
 * @property-read User $user
 * @property-read string $display_name
 */
class Passkey extends Model
{
    use HasFactory;

    protected static function newFactory(): PasskeyFactory
    {
        return PasskeyFactory::new();
    }

    protected $fillable = [
        'user_id',
        'name',
        'credential_id',
        'public_key',
        'sign_count',
        'aaguid',
        'transports',
        'type',
        'attestation_data',
        'last_used_at',
    ];

    protected $casts = [
        'transports'       => 'array',
        'attestation_data' => 'array',
        'sign_count'       => 'integer',
        'last_used_at'     => 'datetime',
    ];

    /**
     * Get the user that owns the passkey.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Update the sign count and last used timestamp.
     */
    public function updateUsage(int $signCount): void
    {
        $this->update([
            'sign_count'   => $signCount,
            'last_used_at' => now(),
        ]);
    }

    /**
     * Get a user-friendly display name for the passkey.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->name) {
            return $this->name;
        }

        // Generate a default name based on creation date
        return 'Passkey created on '.$this->created_at->format('M j, Y');
    }

    /**
     * Scope to find passkey by credential ID.
     *
     * @param mixed $query
     */
    public function scopeByCredentialId($query, string $credentialId)
    {
        return $query->where('credential_id', $credentialId);
    }
}
