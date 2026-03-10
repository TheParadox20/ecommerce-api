<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\LocationType;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        // Create location types (matching Google Places API types)
        $types = [];
        foreach (['country', 'political', 'administrative_area_level_1', 'locality', 'sublocality'] as $name) {
            $types[$name] = LocationType::firstOrCreate(['name' => $name]);
        }

        // Kenya
        $kenya = Location::create([
            'name' => 'Kenya',
            'short_name' => 'KE',
            'latitude' => -1.2920659,
            'longitude' => 36.8219462,
            'google_place_id' => 'ChIJp0lN2HIRLxgRTJKXslQCz_c',
        ]);
        $kenya->types()->attach([$types['country']->id, $types['political']->id]);

        // Counties with their major localities
        $counties = [
            [
                'name' => 'Nairobi County', 'short_name' => 'Nairobi County',
                'lat' => -1.2920659, 'lng' => 36.8219462,
                'localities' => [
                    ['name' => 'Nairobi', 'lat' => -1.2920659, 'lng' => 36.8219462],
                    ['name' => 'Westlands', 'lat' => -1.2635, 'lng' => 36.8042, 'delivery_fee' => 350],
                    ['name' => 'Karen', 'lat' => -1.3197, 'lng' => 36.7111, 'delivery_fee' => 400],
                    ['name' => 'Langata', 'lat' => -1.3558, 'lng' => 36.7498, 'delivery_fee' => 300],
                    ['name' => 'Embakasi', 'lat' => -1.3226, 'lng' => 36.9023, 'delivery_fee' => 400],
                    ['name' => 'Kasarani', 'lat' => -1.2227, 'lng' => 36.8985, 'delivery_fee' => 500],
                    ['name' => 'Kibra', 'lat' => -1.3133, 'lng' => 36.7847],
                    ['name' => 'Dagoretti', 'lat' => -1.2983, 'lng' => 36.7264],
                    ['name' => 'Ruaraka', 'lat' => -1.2385, 'lng' => 36.8758],
                    ['name' => 'Kilimani', 'lat' => -1.2892, 'lng' => 36.7850, 'delivery_fee' => 350],
                    ['name' => 'Lavington', 'lat' => -1.2789, 'lng' => 36.7728, 'delivery_fee' => 350],
                    ['name' => 'South B', 'lat' => -1.3106, 'lng' => 36.8378, 'delivery_fee' => 300],
                    ['name' => 'South C', 'lat' => -1.3175, 'lng' => 36.8253, 'delivery_fee' => 300],
                    ['name' => 'Eastleigh', 'lat' => -1.2742, 'lng' => 36.8539],
                    ['name' => 'Pangani', 'lat' => -1.2667, 'lng' => 36.8389, 'delivery_fee' => 400],
                    // New sublocalities from delivery zones
                    ['name' => 'Nairobi Central', 'lat' => -1.2836977, 'lng' => 36.825346, 'delivery_fee' => 300],
                    ['name' => 'Nairobi South', 'lat' => -1.3054735, 'lng' => 36.8380674, 'delivery_fee' => 300],
                    ['name' => 'Nairobi West', 'lat' => -1.3222736, 'lng' => 36.8288933, 'delivery_fee' => 300],
                    ['name' => 'Spring Valley', 'lat' => -1.2593949, 'lng' => 36.8007134, 'delivery_fee' => 300],
                    ['name' => 'Industrial Area', 'lat' => -1.3091877, 'lng' => 36.8696627, 'delivery_fee' => 300],
                    ['name' => 'Kileleshwa', 'lat' => -1.2741853, 'lng' => 36.7888466, 'delivery_fee' => 350],
                    ['name' => 'Highridge', 'lat' => -1.2675001, 'lng' => 36.812022, 'delivery_fee' => 350],
                    ['name' => 'Ngara', 'lat' => -1.2781236, 'lng' => 36.8304167, 'delivery_fee' => 350],
                    ['name' => 'Parklands', 'lat' => -1.2598822, 'lng' => 36.8178918, 'delivery_fee' => 400],
                    ['name' => 'Donholm', 'lat' => -1.2939161, 'lng' => 36.8971181, 'delivery_fee' => 400],
                    ['name' => 'BuruBuru', 'lat' => -1.2922481, 'lng' => 36.8815479, 'delivery_fee' => 400],
                    ['name' => 'Kariobangi North', 'lat' => -1.258532, 'lng' => 36.8793389, 'delivery_fee' => 450],
                    ['name' => 'Komarock', 'lat' => -1.2689365, 'lng' => 36.907205, 'delivery_fee' => 450],
                    ['name' => 'Muthaiga', 'lat' => -1.2500849, 'lng' => 36.8213209, 'delivery_fee' => 450],
                    ['name' => 'Waithaka', 'lat' => -1.281995, 'lng' => 36.7139515, 'delivery_fee' => 500],
                    ['name' => 'Kangemi', 'lat' => -1.2675543, 'lng' => 36.7307641, 'delivery_fee' => 500],
                    ['name' => 'Roysambu', 'lat' => -1.2160186, 'lng' => 36.8879618, 'delivery_fee' => 500],
                    ['name' => 'Kahawa South', 'lat' => -1.1857341, 'lng' => 36.9028575, 'delivery_fee' => 500],
                    ['name' => 'Dandora', 'lat' => -1.2482996, 'lng' => 36.9025814, 'delivery_fee' => 500],
                    ['name' => 'Umoja', 'lat' => -1.2773942, 'lng' => 36.9157976, 'delivery_fee' => 500],
                    ['name' => 'Kayole', 'lat' => -1.2773942, 'lng' => 36.9157976, 'delivery_fee' => 500],
                    ['name' => 'Ruai', 'lat' => -1.2536408, 'lng' => 36.9863415, 'delivery_fee' => 600],
                ],
            ],
            [
                'name' => 'Kiambu County', 'short_name' => 'Kiambu County',
                'lat' => -1.0314, 'lng' => 36.8681,
                'localities' => [
                    ['name' => 'Thika', 'lat' => -1.0396, 'lng' => 37.0900],
                    ['name' => 'Juja', 'lat' => -1.1033, 'lng' => 37.0232, 'delivery_fee' => 700],
                    ['name' => 'Ruiru', 'lat' => -1.1489, 'lng' => 36.9603, 'delivery_fee' => 600],
                    ['name' => 'Kiambu', 'lat' => -1.1714, 'lng' => 36.8356, 'delivery_fee' => 600],
                    ['name' => 'Limuru', 'lat' => -1.1133, 'lng' => 36.6461],
                    ['name' => 'Kikuyu', 'lat' => -1.2465, 'lng' => 36.6658, 'delivery_fee' => 600],
                    ['name' => 'Gatundu', 'lat' => -1.0017, 'lng' => 36.9058],
                    ['name' => 'Githunguri', 'lat' => -1.0567, 'lng' => 36.7742],
                    // New from delivery zones
                    ['name' => 'Ruaka', 'lat' => -1.2058128, 'lng' => 36.7795319, 'delivery_fee' => 500],
                    ['name' => 'Thindigua', 'lat' => -1.2043694, 'lng' => 36.8404525, 'delivery_fee' => 500],
                    ['name' => 'Kahawa Wendani', 'lat' => -1.2017712, 'lng' => 36.9252784, 'delivery_fee' => 500],
                    ['name' => 'Kahawa Sukari', 'lat' => -1.1946057, 'lng' => 36.9472742, 'delivery_fee' => 500],
                ],
            ],
            [
                'name' => 'Mombasa County', 'short_name' => 'Mombasa County',
                'lat' => -4.0435, 'lng' => 39.6682,
                'localities' => [
                    ['name' => 'Mombasa', 'lat' => -4.0435, 'lng' => 39.6682],
                    ['name' => 'Nyali', 'lat' => -4.0197, 'lng' => 39.7092],
                    ['name' => 'Bamburi', 'lat' => -3.9939, 'lng' => 39.7228],
                    ['name' => 'Likoni', 'lat' => -4.0730, 'lng' => 39.6558],
                    ['name' => 'Changamwe', 'lat' => -4.0275, 'lng' => 39.6300],
                    ['name' => 'Kisauni', 'lat' => -3.9850, 'lng' => 39.7050],
                ],
            ],
            [
                'name' => 'Kisumu County', 'short_name' => 'Kisumu County',
                'lat' => -0.1022, 'lng' => 34.7617,
                'localities' => [
                    ['name' => 'Kisumu', 'lat' => -0.1022, 'lng' => 34.7617],
                    ['name' => 'Ahero', 'lat' => -0.1786, 'lng' => 34.9197],
                    ['name' => 'Maseno', 'lat' => 0.0022, 'lng' => 34.5953],
                ],
            ],
            [
                'name' => 'Nakuru County', 'short_name' => 'Nakuru County',
                'lat' => -0.3031, 'lng' => 36.0800,
                'localities' => [
                    ['name' => 'Nakuru', 'lat' => -0.3031, 'lng' => 36.0800],
                    ['name' => 'Naivasha', 'lat' => -0.7172, 'lng' => 36.4310],
                    ['name' => 'Gilgil', 'lat' => -0.4933, 'lng' => 36.3222],
                    ['name' => 'Njoro', 'lat' => -0.3316, 'lng' => 35.9452],
                ],
            ],
            [
                'name' => 'Uasin Gishu County', 'short_name' => 'Uasin Gishu County',
                'lat' => 0.5143, 'lng' => 35.2698,
                'localities' => [
                    ['name' => 'Eldoret', 'lat' => 0.5143, 'lng' => 35.2698],
                ],
            ],
            [
                'name' => 'Machakos County', 'short_name' => 'Machakos County',
                'lat' => -1.5177, 'lng' => 37.2634,
                'localities' => [
                    ['name' => 'Machakos', 'lat' => -1.5177, 'lng' => 37.2634],
                    ['name' => 'Athi River', 'lat' => -1.4575, 'lng' => 36.9822, 'delivery_fee' => 600],
                    ['name' => 'Syokimau', 'lat' => -1.3667, 'lng' => 36.9333, 'delivery_fee' => 400],
                    ['name' => 'Mlolongo', 'lat' => -1.3903, 'lng' => 36.9397, 'delivery_fee' => 500],
                    ['name' => 'Kangundo', 'lat' => -1.3667, 'lng' => 37.3000],
                ],
            ],
            [
                'name' => 'Kajiado County', 'short_name' => 'Kajiado County',
                'lat' => -2.0981, 'lng' => 36.7820,
                'localities' => [
                    ['name' => 'Kajiado', 'lat' => -2.0981, 'lng' => 36.7820],
                    ['name' => 'Kitengela', 'lat' => -1.4697, 'lng' => 36.9611, 'delivery_fee' => 600],
                    ['name' => 'Ongata Rongai', 'lat' => -1.3962, 'lng' => 36.7589, 'delivery_fee' => 500],
                    ['name' => 'Ngong', 'lat' => -1.3614, 'lng' => 36.6575, 'delivery_fee' => 600],
                    // New from delivery zones
                    ['name' => 'Kiserian', 'lat' => -1.4251869, 'lng' => 36.6936512, 'delivery_fee' => 600],
                ],
            ],
            [
                'name' => 'Meru County', 'short_name' => 'Meru County',
                'lat' => 0.0480, 'lng' => 37.6559,
                'localities' => [
                    ['name' => 'Meru', 'lat' => 0.0480, 'lng' => 37.6559],
                    ['name' => 'Nkubu', 'lat' => -0.0439, 'lng' => 37.6608],
                ],
            ],
            [
                'name' => 'Nyeri County', 'short_name' => 'Nyeri County',
                'lat' => -0.4197, 'lng' => 36.9511,
                'localities' => [
                    ['name' => 'Nyeri', 'lat' => -0.4197, 'lng' => 36.9511],
                    ['name' => 'Karatina', 'lat' => -0.4944, 'lng' => 37.1292],
                    ['name' => 'Othaya', 'lat' => -0.5383, 'lng' => 36.9572],
                ],
            ],
            [
                'name' => 'Kilifi County', 'short_name' => 'Kilifi County',
                'lat' => -3.5108, 'lng' => 39.9093,
                'localities' => [
                    ['name' => 'Kilifi', 'lat' => -3.6306, 'lng' => 39.8499],
                    ['name' => 'Malindi', 'lat' => -3.2139, 'lng' => 40.1169],
                    ['name' => 'Watamu', 'lat' => -3.3540, 'lng' => 40.0240],
                ],
            ],
            [
                'name' => 'Nyandarua County', 'short_name' => 'Nyandarua County',
                'lat' => -0.1803, 'lng' => 36.5228,
                'localities' => [
                    ['name' => 'Ol Kalou', 'lat' => -0.2731, 'lng' => 36.3778],
                    ['name' => 'Engineer', 'lat' => -0.1500, 'lng' => 36.4333],
                ],
            ],
            [
                'name' => 'Murang\'a County', 'short_name' => 'Murang\'a County',
                'lat' => -0.7839, 'lng' => 37.0403,
                'localities' => [
                    ['name' => 'Murang\'a', 'lat' => -0.7839, 'lng' => 37.0403],
                    ['name' => 'Kenol', 'lat' => -0.8800, 'lng' => 37.0400],
                ],
            ],
            [
                'name' => 'Embu County', 'short_name' => 'Embu County',
                'lat' => -0.5389, 'lng' => 37.4596,
                'localities' => [
                    ['name' => 'Embu', 'lat' => -0.5389, 'lng' => 37.4596],
                ],
            ],
            [
                'name' => 'Kirinyaga County', 'short_name' => 'Kirinyaga County',
                'lat' => -0.4989, 'lng' => 37.2803,
                'localities' => [
                    ['name' => 'Kerugoya', 'lat' => -0.4989, 'lng' => 37.2803],
                    ['name' => 'Kutus', 'lat' => -0.5642, 'lng' => 37.2917],
                ],
            ],
            [
                'name' => 'Laikipia County', 'short_name' => 'Laikipia County',
                'lat' => 0.0600, 'lng' => 36.7800,
                'localities' => [
                    ['name' => 'Nanyuki', 'lat' => 0.0067, 'lng' => 37.0722],
                    ['name' => 'Rumuruti', 'lat' => 0.2728, 'lng' => 36.5381],
                ],
            ],
            [
                'name' => 'Trans-Nzoia County', 'short_name' => 'Trans-Nzoia County',
                'lat' => 1.0567, 'lng' => 34.9507,
                'localities' => [
                    ['name' => 'Kitale', 'lat' => 1.0187, 'lng' => 35.0020],
                ],
            ],
            [
                'name' => 'Nandi County', 'short_name' => 'Nandi County',
                'lat' => 0.1833, 'lng' => 35.1500,
                'localities' => [
                    ['name' => 'Kapsabet', 'lat' => 0.2000, 'lng' => 35.1000],
                ],
            ],
            [
                'name' => 'Kericho County', 'short_name' => 'Kericho County',
                'lat' => -0.3692, 'lng' => 35.2863,
                'localities' => [
                    ['name' => 'Kericho', 'lat' => -0.3692, 'lng' => 35.2863],
                    ['name' => 'Litein', 'lat' => -0.5225, 'lng' => 35.3806],
                ],
            ],
            [
                'name' => 'Bomet County', 'short_name' => 'Bomet County',
                'lat' => -0.7817, 'lng' => 35.3428,
                'localities' => [
                    ['name' => 'Bomet', 'lat' => -0.7817, 'lng' => 35.3428],
                ],
            ],
            [
                'name' => 'Kakamega County', 'short_name' => 'Kakamega County',
                'lat' => 0.2828, 'lng' => 34.7519,
                'localities' => [
                    ['name' => 'Kakamega', 'lat' => 0.2828, 'lng' => 34.7519],
                ],
            ],
            [
                'name' => 'Bungoma County', 'short_name' => 'Bungoma County',
                'lat' => 0.5636, 'lng' => 34.5606,
                'localities' => [
                    ['name' => 'Bungoma', 'lat' => 0.5636, 'lng' => 34.5606],
                ],
            ],
            [
                'name' => 'Busia County', 'short_name' => 'Busia County',
                'lat' => 0.4608, 'lng' => 34.1108,
                'localities' => [
                    ['name' => 'Busia', 'lat' => 0.4608, 'lng' => 34.1108],
                ],
            ],
            [
                'name' => 'Siaya County', 'short_name' => 'Siaya County',
                'lat' => 0.0607, 'lng' => 34.2881,
                'localities' => [
                    ['name' => 'Siaya', 'lat' => 0.0607, 'lng' => 34.2881],
                ],
            ],
            [
                'name' => 'Homa Bay County', 'short_name' => 'Homa Bay County',
                'lat' => -0.5273, 'lng' => 34.4571,
                'localities' => [
                    ['name' => 'Homa Bay', 'lat' => -0.5273, 'lng' => 34.4571],
                ],
            ],
            [
                'name' => 'Migori County', 'short_name' => 'Migori County',
                'lat' => -1.0634, 'lng' => 34.4731,
                'localities' => [
                    ['name' => 'Migori', 'lat' => -1.0634, 'lng' => 34.4731],
                ],
            ],
            [
                'name' => 'Kisii County', 'short_name' => 'Kisii County',
                'lat' => -0.6817, 'lng' => 34.7668,
                'localities' => [
                    ['name' => 'Kisii', 'lat' => -0.6817, 'lng' => 34.7668],
                ],
            ],
            [
                'name' => 'Nyamira County', 'short_name' => 'Nyamira County',
                'lat' => -0.5633, 'lng' => 34.9358,
                'localities' => [
                    ['name' => 'Nyamira', 'lat' => -0.5633, 'lng' => 34.9358],
                ],
            ],
            [
                'name' => 'Narok County', 'short_name' => 'Narok County',
                'lat' => -1.0872, 'lng' => 35.8714,
                'localities' => [
                    ['name' => 'Narok', 'lat' => -1.0872, 'lng' => 35.8714],
                ],
            ],
            [
                'name' => 'Samburu County', 'short_name' => 'Samburu County',
                'lat' => 1.2128, 'lng' => 36.9542,
                'localities' => [
                    ['name' => 'Maralal', 'lat' => 1.1000, 'lng' => 36.7000],
                ],
            ],
            [
                'name' => 'Baringo County', 'short_name' => 'Baringo County',
                'lat' => 0.4664, 'lng' => 35.7480,
                'localities' => [
                    ['name' => 'Kabarnet', 'lat' => 0.4917, 'lng' => 35.7500],
                ],
            ],
            [
                'name' => 'Elgeyo-Marakwet County', 'short_name' => 'Elgeyo-Marakwet County',
                'lat' => 0.6792, 'lng' => 35.5083,
                'localities' => [
                    ['name' => 'Iten', 'lat' => 0.6722, 'lng' => 35.5081],
                ],
            ],
            [
                'name' => 'West Pokot County', 'short_name' => 'West Pokot County',
                'lat' => 1.6200, 'lng' => 35.2200,
                'localities' => [
                    ['name' => 'Kapenguria', 'lat' => 1.2389, 'lng' => 35.1119],
                ],
            ],
            [
                'name' => 'Turkana County', 'short_name' => 'Turkana County',
                'lat' => 3.3122, 'lng' => 35.5658,
                'localities' => [
                    ['name' => 'Lodwar', 'lat' => 3.1191, 'lng' => 35.5967],
                ],
            ],
            [
                'name' => 'Marsabit County', 'short_name' => 'Marsabit County',
                'lat' => 2.3284, 'lng' => 37.9899,
                'localities' => [
                    ['name' => 'Marsabit', 'lat' => 2.3284, 'lng' => 37.9899],
                ],
            ],
            [
                'name' => 'Isiolo County', 'short_name' => 'Isiolo County',
                'lat' => 0.3546, 'lng' => 37.5822,
                'localities' => [
                    ['name' => 'Isiolo', 'lat' => 0.3546, 'lng' => 37.5822],
                ],
            ],
            [
                'name' => 'Tharaka-Nithi County', 'short_name' => 'Tharaka-Nithi County',
                'lat' => -0.2975, 'lng' => 37.7236,
                'localities' => [
                    ['name' => 'Chuka', 'lat' => -0.3292, 'lng' => 37.6461],
                ],
            ],
            [
                'name' => 'Kitui County', 'short_name' => 'Kitui County',
                'lat' => -1.3681, 'lng' => 38.0106,
                'localities' => [
                    ['name' => 'Kitui', 'lat' => -1.3681, 'lng' => 38.0106],
                ],
            ],
            [
                'name' => 'Makueni County', 'short_name' => 'Makueni County',
                'lat' => -1.8039, 'lng' => 37.6208,
                'localities' => [
                    ['name' => 'Wote', 'lat' => -1.7806, 'lng' => 37.6283],
                ],
            ],
            [
                'name' => 'Taita-Taveta County', 'short_name' => 'Taita-Taveta County',
                'lat' => -3.3169, 'lng' => 38.4850,
                'localities' => [
                    ['name' => 'Voi', 'lat' => -3.3931, 'lng' => 38.5619],
                ],
            ],
            [
                'name' => 'Kwale County', 'short_name' => 'Kwale County',
                'lat' => -4.1736, 'lng' => 39.4525,
                'localities' => [
                    ['name' => 'Kwale', 'lat' => -4.1736, 'lng' => 39.4525],
                    ['name' => 'Diani', 'lat' => -4.3167, 'lng' => 39.5833],
                    ['name' => 'Ukunda', 'lat' => -4.2833, 'lng' => 39.5667],
                ],
            ],
            [
                'name' => 'Tana River County', 'short_name' => 'Tana River County',
                'lat' => -1.8000, 'lng' => 39.6500,
                'localities' => [
                    ['name' => 'Hola', 'lat' => -1.5000, 'lng' => 40.0300],
                ],
            ],
            [
                'name' => 'Lamu County', 'short_name' => 'Lamu County',
                'lat' => -2.2717, 'lng' => 40.9022,
                'localities' => [
                    ['name' => 'Lamu', 'lat' => -2.2717, 'lng' => 40.9022],
                ],
            ],
            [
                'name' => 'Garissa County', 'short_name' => 'Garissa County',
                'lat' => -0.4532, 'lng' => 39.6461,
                'localities' => [
                    ['name' => 'Garissa', 'lat' => -0.4532, 'lng' => 39.6461],
                ],
            ],
            [
                'name' => 'Wajir County', 'short_name' => 'Wajir County',
                'lat' => 1.7471, 'lng' => 40.0573,
                'localities' => [
                    ['name' => 'Wajir', 'lat' => 1.7471, 'lng' => 40.0573],
                ],
            ],
            [
                'name' => 'Mandera County', 'short_name' => 'Mandera County',
                'lat' => 3.9373, 'lng' => 41.8569,
                'localities' => [
                    ['name' => 'Mandera', 'lat' => 3.9373, 'lng' => 41.8569],
                ],
            ],
            [
                'name' => 'Vihiga County', 'short_name' => 'Vihiga County',
                'lat' => 0.0833, 'lng' => 34.7333,
                'localities' => [
                    ['name' => 'Mbale', 'lat' => 0.0833, 'lng' => 34.7333],
                ],
            ],
        ];

        $countyTypeIds = [$types['administrative_area_level_1']->id, $types['political']->id];
        $localityTypeIds = [$types['locality']->id, $types['political']->id];

        foreach ($counties as $countyData) {
            $county = Location::create([
                'name' => $countyData['name'],
                'short_name' => $countyData['short_name'],
                'parent_id' => $kenya->id,
                'latitude' => $countyData['lat'],
                'longitude' => $countyData['lng'],
                'google_place_id' => $countyData['place_id'] ?? null,
            ]);
            $county->types()->attach($countyTypeIds);

            foreach ($countyData['localities'] as $localityData) {
                $locality = Location::create([
                    'name' => $localityData['name'],
                    'short_name' => $localityData['name'],
                    'parent_id' => $county->id,
                    'latitude' => $localityData['lat'],
                    'longitude' => $localityData['lng'],
                    'google_place_id' => $localityData['place_id'] ?? null,
                    'delivery_fee' => $localityData['delivery_fee'] ?? null,
                ]);
                $locality->types()->attach($localityTypeIds);
            }
        }
    }
}
