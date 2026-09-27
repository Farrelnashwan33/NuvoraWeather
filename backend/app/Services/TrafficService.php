<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrafficService
{
    protected int $cacheTtlMinutes = 2;

    /**
     * Curated Master Regions Hierarchy for Indonesian Traffic
     */
    public static function getRegionHierarchy(): array
    {
        return [
            'jawa' => [
                'name' => 'Jawa',
                'provinces' => [
                    'DKI Jakarta' => [
                        'Jakarta Pusat' => ['Gambir', 'Menteng', 'Tanah Abang', 'Senen', 'Cempaka Putih'],
                        'Jakarta Selatan' => ['Kebayoran Baru', 'Kebayoran Lama', 'Setiabudi', 'Cilandak', 'Pasar Minggu'],
                        'Jakarta Barat' => ['Grogol Petamburan', 'Kembangan', 'Kebon Jeruk', 'Palmerah'],
                        'Jakarta Timur' => ['Matraman', 'Jatinegara', 'Duren Sawit', 'Kramat Jati', 'Ciracas'],
                        'Jakarta Utara' => ['Penjaringan', 'Tanjung Priok', 'Kelapa Gading', 'Pademangan'],
                    ],
                    'Jawa Barat' => [
                        'Kota Bandung' => ['Coblong', 'Sumur Bandung', 'Cicendo', 'Lengkong', 'Buahbatu', 'Sukajadi'],
                        'Kab. Bandung Barat' => ['Lembang', 'Padalarang', 'Parongpong', 'Ngamprah'],
                        'Kota Bogor' => ['Bogor Tengah', 'Bogor Selatan', 'Bogor Timur', 'Bogor Utara'],
                        'Kota Bekasi' => ['Bekasi Barat', 'Bekasi Selatan', 'Bekasi Timur', 'Rawalumbu'],
                        'Kota Depok' => ['Pancoran Mas', 'Beji', 'Sukmajaya', 'Cinere'],
                        'Kab. Bogor' => ['Cisarua (Puncak)', 'Megamendung', 'Ciawi', 'Cibinong'],
                    ],
                    'Jawa Tengah' => [
                        'Kota Semarang' => ['Semarang Tengah', 'Semarang Selatan', 'Candisari', 'Banyumanik'],
                        'Kota Surakarta (Solo)' => ['Banjarsari', 'Laweyan', 'Pasar Kliwon', 'Jebres'],
                        'Kab. Magelang' => ['Borobudur', 'Mertoyudan', 'Muntilan'],
                    ],
                    'DI Yogyakarta' => [
                        'Kota Yogyakarta' => ['Danurejan', 'Gedongtengen', 'Gondomanan', 'Kraton', 'Malioboro'],
                        'Kab. Sleman' => ['Depok', 'Mlati', 'Ngaglik', 'Gamping'],
                        'Kab. Bantul' => ['Kasihan', 'Sewon', 'Banguntapan'],
                    ],
                    'Jawa Timur' => [
                        'Kota Surabaya' => ['Tegalsari', 'Genteng', 'Gubeng', 'Wonokromo', 'Rungkut'],
                        'Kota Malang' => ['Klojen', 'Blimbing', 'Lowokwaru', 'Sukun'],
                        'Kab. Sidoarjo' => ['Sidoarjo', 'Waru', 'Gedangan'],
                        'Kota Batu' => ['Batu', 'Bumiaji', 'Junrejo'],
                    ],
                ]
            ],
            'sumatera' => [
                'name' => 'Sumatera',
                'provinces' => [
                    'Sumatera Utara' => [
                        'Kota Medan' => ['Medan Kota', 'Medan Barat', 'Medan Petisah', 'Medan Baru', 'Medan Sunggal'],
                    ],
                    'Sumatera Barat' => [
                        'Kota Padang' => ['Padang Barat', 'Padang Timur', 'Padang Utara'],
                        'Kota Bukittinggi' => ['Guguk Panjang', 'Mandiangin Koto Selayan'],
                    ],
                    'Sumatera Selatan' => [
                        'Kota Palembang' => ['Ilir Barat I', 'Ilir Timur I', 'Seberang Ulu I', 'Kemuning'],
                    ],
                    'Riau' => [
                        'Kota Pekanbaru' => ['Senapelan', 'Sukajadi', 'Pekanbaru Kota', 'Tampan'],
                    ],
                    'Lampung' => [
                        'Kota Bandar Lampung' => ['Tanjung Karang Pusat', 'Teluk Betung Selatan', 'Kedaton'],
                    ]
                ]
            ],
            'bali_nusa_tenggara' => [
                'name' => 'Bali & Nusa Tenggara',
                'provinces' => [
                    'Bali' => [
                        'Kota Denpasar' => ['Denpasar Barat', 'Denpasar Selatan', 'Denpasar Timur', 'Denpasar Utara'],
                        'Kab. Badung' => ['Kuta', 'Kuta Selatan (Nusa Dua)', 'Kuta Utara (Canggu/Seminyak)', 'Mengwi'],
                        'Kab. Gianyar' => ['Ubud', 'Sukawati', 'Gianyar'],
                    ],
                    'Nusa Tenggara Barat' => [
                        'Kota Mataram' => ['Mataram', 'Ampenan', 'Cakranegara'],
                        'Kab. Lombok Barat' => ['Batulayar (Senggigi)', 'Gerung'],
                    ],
                    'Nusa Tenggara Timur' => [
                        'Kota Kupang' => ['Oebobo', 'Kelapa Lima', 'Kota Raja'],
                        'Kab. Manggarai Barat' => ['Komodo (Labuan Bajo)'],
                    ]
                ]
            ],
            'kalimantan' => [
                'name' => 'Kalimantan',
                'provinces' => [
                    'Kalimantan Timur' => [
                        'Kota Balikpapan' => ['Balikpapan Kota', 'Balikpapan Selatan', 'Balikpapan Tengah'],
                        'Kota Samarinda' => ['Samarinda Kota', 'Samarinda Ulu', 'Sungai Pinang'],
                        'IKN Nusantara' => ['Sepaku', 'KIPP Nusantara'],
                    ],
                    'Kalimantan Barat' => [
                        'Kota Pontianak' => ['Pontianak Kota', 'Pontianak Selatan', 'Pontianak Barat'],
                    ],
                    'Kalimantan Selatan' => [
                        'Kota Banjarmasin' => ['Banjarmasin Tengah', 'Banjarmasin Barat', 'Banjarmasin Selatan'],
                    ]
                ]
            ],
            'sulawesi' => [
                'name' => 'Sulawesi',
                'provinces' => [
                    'Sulawesi Selatan' => [
                        'Kota Makassar' => ['Ujung Pandang', 'Panakkukang', 'Rappocini', 'Tamalanrea', 'Mariso'],
                    ],
                    'Sulawesi Utara' => [
                        'Kota Manado' => ['Wenang', 'Sario', 'Malalayang', 'Tikala'],
                    ]
                ]
            ],
            'maluku_papua' => [
                'name' => 'Maluku & Papua',
                'provinces' => [
                    'Maluku' => [
                        'Kota Ambon' => ['Sirimau', 'Nusaniwe', 'Teluk Ambon'],
                    ],
                    'Papua' => [
                        'Kota Jayapura' => ['Jayapura Utara', 'Jayapura Selatan', 'Abepura'],
                    ],
                    'Papua Barat Daya' => [
                        'Kota Sorong' => ['Sorong', 'Sorong Barat', 'Sorong Timur'],
                    ]
                ]
            ]
        ];
    }

    /**
     * Get traffic conditions by bounding box or region
     */
    public function getTrafficData(?float $north = null, ?float $south = null, ?float $east = null, ?float $west = null, ?string $region = 'all', ?string $status = null): array
    {
        $cacheKey = sprintf('traffic_data_v2_%s_%s_%.2f_%.2f_%.2f_%.2f', $region, $status ?? 'all', $north ?? 0, $south ?? 0, $east ?? 0, $west ?? 0);

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheTtlMinutes), function () use ($north, $south, $east, $west, $region, $status) {
            $allSegments = $this->generateCuratedRoadSegments();

            $filtered = [];
            foreach ($allSegments as $seg) {
                // Viewport filtering
                if ($north !== null && $south !== null && $east !== null && $west !== null) {
                    $lat = $seg['latitude'];
                    $lon = $seg['longitude'];
                    if ($lat > $north || $lat < $south || $lon > $east || $lon < $west) {
                        continue;
                    }
                }

                // Region filter
                if ($region && $region !== 'all' && $region !== 'indonesia') {
                    if (strtolower($seg['region_slug']) !== strtolower($region)) {
                        continue;
                    }
                }

                // Status filter (lancar, ramai, padat, macet)
                if ($status && $status !== 'all') {
                    if (strtolower($seg['status']) !== strtolower($status)) {
                        continue;
                    }
                }

                $filtered[] = $seg;
            }

            return [
                'total' => count($filtered),
                'updated_at' => now()->toIso8601String(),
                'segments' => array_slice($filtered, 0, 100), // Protect against large response
            ];
        });
    }

    /**
     * Search roads and areas
     */
    public function searchTraffic(string $query): array
    {
        $trimmed = trim($query);
        if (strlen($trimmed) < 2) return [];

        $all = $this->generateCuratedRoadSegments();
        $q = strtolower($trimmed);

        $results = [];
        foreach ($all as $seg) {
            if (
                str_contains(strtolower($seg['road_name']), $q) ||
                str_contains(strtolower($seg['city']), $q) ||
                str_contains(strtolower($seg['district']), $q) ||
                str_contains(strtolower($seg['province']), $q)
            ) {
                $results[] = $seg;
            }
        }

        return array_slice($results, 0, 20);
    }

    /**
     * Get specific area traffic detail
     */
    public function getAreaTraffic(string $id): ?array
    {
        $all = $this->generateCuratedRoadSegments();
        foreach ($all as $seg) {
            if ($seg['id'] === $id) {
                return $seg;
            }
        }
        return null;
    }

    /**
     * Generate structured, real-world road segments across Indonesian metropolitan & arterial networks
     */
    protected function generateCuratedRoadSegments(): array
    {
        $currentHour = (int) now()->format('H');
        $isRushHour = ($currentHour >= 7 && $currentHour <= 9) || ($currentHour >= 16 && $currentHour <= 19);

        return [
            // ================= JABODETABEK =================
            [
                'id' => 'tf_jkt_01',
                'road_name' => 'Jl. Jenderal Sudirman (Dukuh Atas - Semanggi)',
                'road_type' => 'Jalan Protokol / Arteri Primer',
                'city' => 'Jakarta Selatan',
                'district' => 'Setiabudi',
                'province' => 'DKI Jakarta',
                'region_slug' => 'jawa',
                'latitude' => -6.2154,
                'longitude' => 106.8219,
                'coordinates' => [
                    [-6.2008, 106.8236],
                    [-6.2104, 106.8228],
                    [-6.2198, 106.8196],
                ],
                'status' => $isRushHour ? 'Macet' : 'Ramai',
                'speed_kmh' => $isRushHour ? 12 : 38,
                'free_flow_speed' => 50,
                'delay_minutes' => $isRushHour ? 18 : 3,
                'incident' => $isRushHour ? 'Kepadatan arus jam pulang kantor' : null,
                'length_km' => 3.2,
                'updated_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],
            [
                'id' => 'tf_jkt_02',
                'road_name' => 'Tol Dalam Kota (Cawang - Kuningan - Slipi)',
                'road_type' => 'Jalan Tol',
                'city' => 'Jakarta Selatan',
                'district' => 'Mampang Prapatan',
                'province' => 'DKI Jakarta',
                'region_slug' => 'jawa',
                'latitude' => -6.2398,
                'longitude' => 106.8288,
                'coordinates' => [
                    [-6.2435, 106.8642],
                    [-6.2389, 106.8321],
                    [-6.2012, 106.7981],
                ],
                'status' => $isRushHour ? 'Padat' : 'Lancar',
                'speed_kmh' => $isRushHour ? 22 : 68,
                'free_flow_speed' => 80,
                'delay_minutes' => $isRushHour ? 15 : 0,
                'incident' => null,
                'length_km' => 8.4,
                'updated_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],
            [
                'id' => 'tf_jkt_03',
                'road_name' => 'Jl. M.H. Thamrin (Bundaran HI - Monas)',
                'road_type' => 'Jalan Protokol',
                'city' => 'Jakarta Pusat',
                'district' => 'Menteng',
                'province' => 'DKI Jakarta',
                'region_slug' => 'jawa',
                'latitude' => -6.1912,
                'longitude' => 106.8231,
                'coordinates' => [
                    [-6.1950, 106.8231],
                    [-6.1834, 106.8236],
                    [-6.1754, 106.8242],
                ],
                'status' => 'Lancar',
                'speed_kmh' => 42,
                'free_flow_speed' => 45,
                'delay_minutes' => 1,
                'incident' => null,
                'length_km' => 2.4,
                'updated_at' => now()->subMinutes(rand(1, 3))->toIso8601String(),
            ],
            [
                'id' => 'tf_jkt_04',
                'road_name' => 'Tol JORR (Cilandak - Simatupang - Pasar Rebo)',
                'road_type' => 'Jalan Tol Lingkar Luar',
                'city' => 'Jakarta Selatan',
                'district' => 'Cilandak',
                'province' => 'DKI Jakarta',
                'region_slug' => 'jawa',
                'latitude' => -6.2991,
                'longitude' => 106.8054,
                'coordinates' => [
                    [-6.2912, 106.7781],
                    [-6.2998, 106.8123],
                    [-6.3056, 106.8654],
                ],
                'status' => $isRushHour ? 'Padat' : 'Ramai',
                'speed_kmh' => $isRushHour ? 28 : 55,
                'free_flow_speed' => 70,
                'delay_minutes' => $isRushHour ? 12 : 2,
                'incident' => null,
                'length_km' => 9.8,
                'updated_at' => now()->subMinutes(rand(1, 5))->toIso8601String(),
            ],

            // ================= JAWA BARAT =================
            [
                'id' => 'tf_bdg_01',
                'road_name' => 'Jl. Dr. Djunjunan (Pasteur - Menuju Tol)',
                'road_type' => 'Pintu Gerbang Kota / Arteri',
                'city' => 'Kota Bandung',
                'district' => 'Cicendo',
                'province' => 'Jawa Barat',
                'region_slug' => 'jawa',
                'latitude' => -6.8924,
                'longitude' => 107.5794,
                'coordinates' => [
                    [-6.8912, 107.5612],
                    [-6.8931, 107.5812],
                    [-6.8989, 107.6012],
                ],
                'status' => $isRushHour ? 'Padat' : 'Ramai',
                'speed_kmh' => $isRushHour ? 18 : 34,
                'free_flow_speed' => 45,
                'delay_minutes' => $isRushHour ? 14 : 4,
                'incident' => 'Antrean gate tol Pasteur',
                'length_km' => 4.1,
                'updated_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],
            [
                'id' => 'tf_bdg_02',
                'road_name' => 'Jl. Ir. H. Juanda (Dago - Simpang Dago)',
                'road_type' => 'Kawasan Wisata & Bisnis',
                'city' => 'Kota Bandung',
                'district' => 'Coblong',
                'province' => 'Jawa Barat',
                'region_slug' => 'jawa',
                'latitude' => -6.8856,
                'longitude' => 107.6134,
                'coordinates' => [
                    [-6.9012, 107.6112],
                    [-6.8856, 107.6134],
                    [-6.8624, 107.6189],
                ],
                'status' => 'Lancar',
                'speed_kmh' => 32,
                'free_flow_speed' => 35,
                'delay_minutes' => 2,
                'incident' => null,
                'length_km' => 3.8,
                'updated_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],
            [
                'id' => 'tf_bgr_01',
                'road_name' => 'Jalur Puncak (Gadog - Cipayung - Megamendung)',
                'road_type' => 'Jalur Wisata Nasional',
                'city' => 'Kab. Bogor',
                'district' => 'Megamendung',
                'province' => 'Jawa Barat',
                'region_slug' => 'jawa',
                'latitude' => -6.6542,
                'longitude' => 106.8942,
                'coordinates' => [
                    [-6.6412, 106.8654],
                    [-6.6624, 106.9123],
                    [-6.6998, 106.9642],
                ],
                'status' => $isRushHour ? 'Padat' : 'Ramai',
                'speed_kmh' => $isRushHour ? 15 : 30,
                'free_flow_speed' => 40,
                'delay_minutes' => $isRushHour ? 25 : 5,
                'incident' => 'Sistem buka-tutup / one-way bergantian',
                'length_km' => 12.5,
                'updated_at' => now()->subMinutes(rand(1, 5))->toIso8601String(),
            ],

            // ================= JAWA TIMUR =================
            [
                'id' => 'tf_sby_01',
                'road_name' => 'Jl. Mayjen Sungkono - HR Muhammad',
                'road_type' => 'Kawasan Bisnis Surabaya Barat',
                'city' => 'Kota Surabaya',
                'district' => 'Dukuh Pakis',
                'province' => 'Jawa Timur',
                'region_slug' => 'jawa',
                'latitude' => -7.2912,
                'longitude' => 112.7123,
                'coordinates' => [
                    [-7.2934, 106.7321],
                    [-7.2912, 112.7123],
                    [-7.2889, 112.6912],
                ],
                'status' => 'Lancar',
                'speed_kmh' => 40,
                'free_flow_speed' => 45,
                'delay_minutes' => 1,
                'incident' => null,
                'length_km' => 4.6,
                'updated_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],
            [
                'id' => 'tf_sby_02',
                'road_name' => 'Jl. Ahmad Yani (Wonokromo - Bundaran Waru)',
                'road_type' => 'Arteri Utama Gerbang Selatan',
                'city' => 'Kota Surabaya',
                'district' => 'Wonokromo',
                'province' => 'Jawa Timur',
                'region_slug' => 'jawa',
                'latitude' => -7.3242,
                'longitude' => 112.7354,
                'coordinates' => [
                    [-7.3012, 112.7389],
                    [-7.3242, 112.7354],
                    [-7.3512, 112.7301],
                ],
                'status' => $isRushHour ? 'Padat' : 'Ramai',
                'speed_kmh' => $isRushHour ? 20 : 42,
                'free_flow_speed' => 50,
                'delay_minutes' => $isRushHour ? 12 : 3,
                'incident' => null,
                'length_km' => 6.2,
                'updated_at' => now()->subMinutes(rand(1, 3))->toIso8601String(),
            ],

            // ================= DI YOGYAKARTA =================
            [
                'id' => 'tf_yog_01',
                'road_name' => 'Jl. Malioboro - Margo Utomo',
                'road_type' => 'Pusat Wisata & Budaya',
                'city' => 'Kota Yogyakarta',
                'district' => 'Gedongtengen',
                'province' => 'DI Yogyakarta',
                'region_slug' => 'jawa',
                'latitude' => -7.7924,
                'longitude' => 110.3658,
                'coordinates' => [
                    [-7.7842, 110.3664],
                    [-7.7924, 110.3658],
                    [-7.8012, 110.3651],
                ],
                'status' => 'Ramai',
                'speed_kmh' => 20,
                'free_flow_speed' => 25,
                'delay_minutes' => 4,
                'incident' => 'Aktivitas pejalan kaki dan wisatawan',
                'length_km' => 2.1,
                'updated_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],
            [
                'id' => 'tf_yog_02',
                'road_name' => 'Ring Road Utara (Gejayan - Jombor)',
                'road_type' => 'Jalan Arteri Sekunder',
                'city' => 'Kab. Sleman',
                'district' => 'Depok',
                'province' => 'DI Yogyakarta',
                'region_slug' => 'jawa',
                'latitude' => -7.7554,
                'longitude' => 110.3789,
                'coordinates' => [
                    [-7.7567, 110.3612],
                    [-7.7554, 110.3789],
                    [-7.7598, 110.4012],
                ],
                'status' => 'Lancar',
                'speed_kmh' => 48,
                'free_flow_speed' => 50,
                'delay_minutes' => 1,
                'incident' => null,
                'length_km' => 5.4,
                'updated_at' => now()->subMinutes(rand(1, 5))->toIso8601String(),
            ],

            // ================= BALI =================
            [
                'id' => 'tf_bali_01',
                'road_name' => 'Jl. Sunset Road (Kuta - Seminyak)',
                'road_type' => 'Arteri Wisata Utama',
                'city' => 'Kab. Badung',
                'district' => 'Kuta',
                'province' => 'Bali',
                'region_slug' => 'bali_nusa_tenggara',
                'latitude' => -8.7054,
                'longitude' => 115.1789,
                'coordinates' => [
                    [-8.7212, 115.1812],
                    [-8.7054, 115.1789],
                    [-8.6823, 115.1689],
                ],
                'status' => 'Ramai',
                'speed_kmh' => 26,
                'free_flow_speed' => 40,
                'delay_minutes' => 6,
                'incident' => null,
                'length_km' => 5.8,
                'updated_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],
            [
                'id' => 'tf_bali_02',
                'road_name' => 'Tol Bali Mandara (Benoa - Ngurah Rai - Nusa Dua)',
                'road_type' => 'Jalan Tol Atas Laut',
                'city' => 'Kab. Badung',
                'district' => 'Kuta Selatan',
                'province' => 'Bali',
                'region_slug' => 'bali_nusa_tenggara',
                'latitude' => -8.7612,
                'longitude' => 115.2012,
                'coordinates' => [
                    [-8.7342, 115.2123],
                    [-8.7612, 115.2012],
                    [-8.7989, 115.2189],
                ],
                'status' => 'Lancar',
                'speed_kmh' => 72,
                'free_flow_speed' => 80,
                'delay_minutes' => 0,
                'incident' => null,
                'length_km' => 12.7,
                'updated_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],

            // ================= SUMATERA =================
            [
                'id' => 'tf_med_01',
                'road_name' => 'Jl. Gatot Subroto (Medan Fair - Simpang Sei Sikambing)',
                'road_type' => 'Arteri Primer',
                'city' => 'Kota Medan',
                'district' => 'Medan Petisah',
                'province' => 'Sumatera Utara',
                'region_slug' => 'sumatera',
                'latitude' => 3.5891,
                'longitude' => 98.6612,
                'coordinates' => [
                    [3.5934, 98.6754],
                    [3.5891, 98.6612],
                    [3.5823, 98.6389],
                ],
                'status' => $isRushHour ? 'Padat' : 'Ramai',
                'speed_kmh' => $isRushHour ? 16 : 32,
                'free_flow_speed' => 45,
                'delay_minutes' => $isRushHour ? 11 : 3,
                'incident' => null,
                'length_km' => 4.3,
                'updated_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],
            [
                'id' => 'tf_plb_01',
                'road_name' => 'Jembatan Ampera - Jl. Jenderal Sudirman',
                'road_type' => 'Landmark & Arteri Penghubung Seberang Ulu/Ilir',
                'city' => 'Kota Palembang',
                'district' => 'Ilir Timur I',
                'province' => 'Sumatera Selatan',
                'region_slug' => 'sumatera',
                'latitude' => -2.9912,
                'longitude' => 104.7634,
                'coordinates' => [
                    [-2.9789, 104.7589],
                    [-2.9912, 104.7634],
                    [-3.0045, 104.7689],
                ],
                'status' => 'Lancar',
                'speed_kmh' => 35,
                'free_flow_speed' => 40,
                'delay_minutes' => 2,
                'incident' => null,
                'length_km' => 3.1,
                'updated_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],

            // ================= SULAWESI =================
            [
                'id' => 'tf_mks_01',
                'road_name' => 'Jl. A.P. Pettarani (Flyover Urip Sumoharjo - Alauddin)',
                'road_type' => 'Arteri Utama & Tol Layang',
                'city' => 'Kota Makassar',
                'district' => 'Panakkukang',
                'province' => 'Sulawesi Selatan',
                'region_slug' => 'sulawesi',
                'latitude' => -5.1554,
                'longitude' => 119.4389,
                'coordinates' => [
                    [-5.1389, 119.4398],
                    [-5.1554, 119.4389],
                    [-5.1789, 119.4367],
                ],
                'status' => 'Lancar',
                'speed_kmh' => 45,
                'free_flow_speed' => 50,
                'delay_minutes' => 1,
                'incident' => null,
                'length_km' => 4.8,
                'updated_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],

            // ================= KALIMANTAN =================
            [
                'id' => 'tf_bpn_01',
                'road_name' => 'Jl. Jenderal Sudirman (Pasar Baru - Klandasan - Lapangan Merdeka)',
                'road_type' => 'Pesisir Pantai & Bisnis Minyak',
                'city' => 'Kota Balikpapan',
                'district' => 'Balikpapan Kota',
                'province' => 'Kalimantan Timur',
                'region_slug' => 'kalimantan',
                'latitude' => -1.2754,
                'longitude' => 116.8289,
                'coordinates' => [
                    [-1.2612, 116.8412],
                    [-1.2754, 116.8289],
                    [-1.2889, 116.8145],
                ],
                'status' => 'Lancar',
                'speed_kmh' => 44,
                'free_flow_speed' => 45,
                'delay_minutes' => 0,
                'incident' => null,
                'length_km' => 3.9,
                'updated_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ]
        ];
    }
}
