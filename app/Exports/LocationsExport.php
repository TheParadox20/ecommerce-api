<?php

namespace App\Exports;

use App\Models\Location;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LocationsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        // Get all locations that are not top-level (Kenya) and not direct children of Kenya (Counties)
        // Actually, we want the urban zones, which are children of counties.
        
        $kenyaId = Location::where('name', 'Kenya')->value('id');
        $countyIds = Location::where('parent_id', $kenyaId)->pluck('id');

        return Location::whereIn('parent_id', $countyIds)
            ->with('parent')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'County',
            'Town/Urban Zone',
            'SACCO/Rider',
            'Delivery Fee (KES)',
        ];
    }

    public function map($location): array
    {
        return [
            $location->parent ? $location->parent->name : '',
            $location->name,
            $location->sacco_rider,
            $location->delivery_fee,
        ];
    }
}
