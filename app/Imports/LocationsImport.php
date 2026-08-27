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
        $riderFee = $row['rider_fee_kes'] ?? null;
        $riderName = isset($row['rider_namecontact']) ? trim($row['rider_namecontact']) : null;
        $saccoFee = $row['sacco_fee_kes'] ?? null;
        $saccoName = isset($row['sacco_namestage']) ? trim($row['sacco_namestage']) : null;

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
            
            // Update SACCO/Rider if provided
            if ($saccoRider !== null && $saccoRider !== '' && $location->sacco_rider !== $saccoRider) {
                $updates['sacco_rider'] = $saccoRider;
            }
            if ($riderName !== null && $riderName !== '' && $location->rider_name !== $riderName) {
                $updates['rider_name'] = $riderName;
            }
            if ($saccoName !== null && $saccoName !== '' && $location->sacco_name !== $saccoName) {
                $updates['sacco_name'] = $saccoName;
            }
            
            // Update fees if numeric
            if (is_numeric($deliveryFee) && $location->delivery_fee != $deliveryFee) {
                $updates['delivery_fee'] = $deliveryFee;
            }
            if (is_numeric($riderFee) && $location->rider_fee != $riderFee) {
                $updates['rider_fee'] = $riderFee;
            }
            if (is_numeric($saccoFee) && $location->sacco_fee != $saccoFee) {
                $updates['sacco_fee'] = $saccoFee;
            }

            if (!empty($updates)) {
                $location->update($updates);
            }
            
            return null;
        }

        // Create new location if it doesn't exist
        return new Location([
            'name' => $townName,
            'parent_id' => $countyId,
            'short_name' => $townName,
            'sacco_rider' => $saccoRider,
            'rider_name' => $riderName,
            'sacco_name' => $saccoName,
            'delivery_fee' => is_numeric($deliveryFee) ? $deliveryFee : null,
            'rider_fee' => is_numeric($riderFee) ? $riderFee : null,
            'sacco_fee' => is_numeric($saccoFee) ? $saccoFee : null,
        ]);
    }
}
