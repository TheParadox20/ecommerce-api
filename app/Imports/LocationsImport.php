<?php

namespace App\Imports;

use App\Models\Location;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LocationsImport implements ToModel, WithHeadingRow
{
    protected $kenyaId;
    protected $counties = [];

    public function __construct()
    {
        $this->kenyaId = Location::where('name', 'Kenya')->value('id');
        if (!$this->kenyaId) {
            $kenya = Location::create(['name' => 'Kenya', 'short_name' => 'Kenya']);
            $this->kenyaId = $kenya->id;
        }
    }

    public function model(array $row)
    {
        $countyName = trim($row['county'] ?? '');
        $townName = trim($row['townurban_zone'] ?? '');
        $saccoRider = isset($row['saccorider']) ? trim($row['saccorider']) : null;
        $deliveryFee = $row['delivery_fee_kes'] ?? null;

        if (empty($countyName) || empty($townName)) {
            return null;
        }

        // Find or create county
        if (!isset($this->counties[$countyName])) {
            $county = Location::firstOrCreate(
                ['name' => $countyName, 'parent_id' => $this->kenyaId],
                ['short_name' => $countyName]
            );
            $this->counties[$countyName] = $county->id;
        }

        $countyId = $this->counties[$countyName];

        // Find existing location
        $location = Location::where('name', $townName)
            ->where('parent_id', $countyId)
            ->first();

        if ($location) {
            $updates = [];
            
            // Only update SACCO/Rider if it's provided and different
            if ($saccoRider !== null && $saccoRider !== '' && $location->sacco_rider !== $saccoRider) {
                $updates['sacco_rider'] = $saccoRider;
            }
            
            // Only update delivery fee if it's a valid number and different
            if (is_numeric($deliveryFee) && $location->delivery_fee != $deliveryFee) {
                $updates['delivery_fee'] = $deliveryFee;
            }

            if (!empty($updates)) {
                $location->update($updates);
            }
            
            return null; // Don't return the model to avoid double-inserting if it's a collection-based import
        }

        // Create new location if it doesn't exist
        return new Location([
            'name' => $townName,
            'parent_id' => $countyId,
            'short_name' => $townName,
            'sacco_rider' => $saccoRider,
            'delivery_fee' => is_numeric($deliveryFee) ? $deliveryFee : null,
        ]);
    }
}
