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
                '300',
                'Rider Joe',
                '300',
                'Rider Joe (0712345678)',
                '200',
                'Super Metro / Stage',
            ],
            [
                'Kiambu County',
                'Thika Town',
                '450',
                '2NK Sacco',
                '',
                '',
                '',
                '',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'County',
            'Town/Urban Zone',
            'Delivery Fee (KES)',
            'SACCO/Rider',
            'Rider Fee (KES)',
            'Rider Name/Contact',
            'SACCO Fee (KES)',
            'SACCO Name/Stage',
        ];
    }
}
