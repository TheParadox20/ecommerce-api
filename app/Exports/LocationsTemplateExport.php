<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LocationsTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [
                'Nairobi County',
                'Westlands',
                'Rider Joe',
                '300',
            ],
            [
                'Kiambu County',
                'Thika Town',
                '2NK Sacco',
                '450',
            ],
        ];
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
}
