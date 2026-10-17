<?php

namespace App\Models;

use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'created_by',
    'title',
    'short_description',
    'description',
    'goal_amount',
    'start_date',
    'end_date',
    'category',
    'status',
    'image_path',
])]
class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    /**
     * Get the user who created the campaign.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the donations made to the campaign.
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function completeIfExpired(): bool
    {
        return static::query()
            ->whereKey($this->getKey())
            ->whereIn('status', ['active', 'upcoming'])
            ->whereDate('end_date', '<', today())
            ->update(['status' => 'completed']) === 1;
    }

    /**
     * Get the campaign date and amount casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'goal_amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}
