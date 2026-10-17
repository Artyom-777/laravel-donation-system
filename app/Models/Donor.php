<?php

namespace App\Models;

use Database\Factories\DonorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'email', 'phone', 'country', 'city', 'address', 'notes'])]
class Donor extends Model
{
    /** @use HasFactory<DonorFactory> */
    use HasFactory;

    /**
     * Get the donations made by the donor.
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * Get the donor's most recent donation.
     */
    public function latestDonation(): HasOne
    {
        return $this->hasOne(Donation::class)->latestOfMany('donated_at');
    }
}
