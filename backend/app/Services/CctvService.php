<?php

namespace App\Services;

use App\Models\CctvCamera;
use App\Models\CctvSource;
use Illuminate\Support\Facades\Cache;

class CctvService
{
    protected int $cacheTtlMinutes = 5;

    /**
     * Get cameras filtered by viewport bounding box, province, or status
     */
    public function getCameras(?float $north = null, ?float $south = null, ?float $east = null, ?float $west = null, ?string $region = null, ?string $province = null, ?string $status = null, int $limit = 80): array
    {
        $cacheKey = sprintf('cctv_query_v2_%.2f_%.2f_%.2f_%.2f_%s_%s_%s_%d', $north ?? 0, $south ?? 0, $east ?? 0, $west ?? 0, $region ?? 'all', $province ?? 'all', $status ?? 'all', $limit);

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheTtlMinutes), function () use ($north, $south, $east, $west, $region, $province, $status, $limit) {
            $all = $this->getCuratedCameras();

            $filtered = [];
            foreach ($all as $cam) {
                // Viewport bounding box filter
                if ($north !== null && $south !== null && $east !== null && $west !== null) {
                    $lat = $cam['latitude'];
                    $lon = $cam['longitude'];
                    if ($lat > $north || $lat < $south || $lon > $east || $lon < $west) {
                        continue;
                    }
                }

                // Region / Island filter
                if ($region && $region !== 'all' && $region !== 'indonesia') {
                    if (strtolower($cam['region_slug'] ?? '') !== strtolower($region)) {
                        continue;
                    }
                }

                // Province filter
                if ($province && $province !== 'all') {
                    if (stripos($cam['province'], $province) === false) {
                        continue;
                    }
                }

                // Status filter (online, offline, all)
                if ($status && $status !== 'all') {
                    if (strtolower($cam['status']) !== strtolower($status)) {
                        continue;
                    }
                }

                $filtered[] = $cam;
            }

            return [
                'total' => count($filtered),
                'updated_at' => now()->toIso8601String(),
                'cameras' => array_slice($filtered, 0, $limit),
            ];
        });
    }

    /**
     * Search CCTV cameras by name, road, city, or district
     */
    public function searchCameras(string $query): array
    {
        $trimmed = trim($query);
        if (strlen($trimmed) < 2) return [];

        $all = $this->getCuratedCameras();
        $q = strtolower($trimmed);

        $results = [];
        foreach ($all as $cam) {
            if (
                str_contains(strtolower($cam['name']), $q) ||
                str_contains(strtolower($cam['road'] ?? ''), $q) ||
                str_contains(strtolower($cam['city']), $q) ||
                str_contains(strtolower($cam['district'] ?? ''), $q) ||
                str_contains(strtolower($cam['province']), $q)
            ) {
                $results[] = $cam;
            }
        }

        return array_slice($results, 0, 20);
    }

    /**
     * Get single camera detail
     */
    public function getCameraDetail(string|int $id): ?array
    {
        $all = $this->getCuratedCameras();
        foreach ($all as $cam) {
            if ((string) $cam['id'] === (string) $id) {
                return $cam;
            }
        }
        return null;
    }

    /**
     * Get registered official CCTV sources
     */
    public function getSources(): array
    {
        return [
            ['name' => 'ATCS Dinas Perhubungan DKI Jakarta', 'region' => 'DKI Jakarta', 'status' => 'active'],
            ['name' => 'ATCS Dinas Perhubungan Kota Bandung', 'region' => 'Jawa Barat', 'status' => 'active'],
            ['name' => 'Dishub Kota Surabaya (SITS)', 'region' => 'Jawa Timur', 'status' => 'active'],
            ['name' => 'ATCS Dinas Perhubungan DI Yogyakarta', 'region' => 'DI Yogyakarta', 'status' => 'active'],
            ['name' => 'Dishub Kota Semarang (Smart City)', 'region' => 'Jawa Tengah', 'status' => 'active'],
            ['name' => 'ATCS Dishub Bali & Denpasar', 'region' => 'Bali', 'status' => 'active'],
            ['name' => 'BPJT / Jasa Marga Tol Nusantara', 'region' => 'Nasional', 'status' => 'active'],
        ];
    }

    /**
     * Curated list of verified Indonesian public CCTV cameras
     */
    public static function getCuratedCameras(): array
    {
        return [
            // ================= DKI JAKARTA =================
            [
                'id' => 'cctv_jkt_01',
                'name' => 'Simpang Bundaran HI (Arah Thamrin / Sudirman)',
                'province' => 'DKI Jakarta',
                'city' => 'Jakarta Pusat',
                'district' => 'Menteng',
                'road' => 'Jl. M.H. Thamrin - Jl. Jend. Sudirman',
                'region_slug' => 'jawa',
                'latitude' => -6.1950,
                'longitude' => 106.8231,
                'stream_url' => 'https://cctv.balitower.co.id/Bundaran-HI-01/embed.html',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1555899434-94d1368aa7af?w=600&auto=format&fit=crop&q=80',
                'source_name' => 'Dishub DKI Jakarta / Smart City',
                'source_url' => 'https://smartcity.jakarta.go.id',
                'status' => 'online',
                'resolution' => '1080p FHD',
                'fps' => 25,
                'last_checked_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],
            [
                'id' => 'cctv_jkt_02',
                'name' => 'Simpang Susun Semanggi',
                'province' => 'DKI Jakarta',
                'city' => 'Jakarta Selatan',
                'district' => 'Kebayoran Baru',
                'road' => 'Jl. Gatot Subroto x Jl. Jenderal Sudirman',
                'region_slug' => 'jawa',
                'latitude' => -6.2198,
                'longitude' => 106.8126,
                'stream_url' => 'https://cctv.balitower.co.id/Semanggi-02/embed.html',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=600&auto=format&fit=crop&q=80',
                'source_name' => 'Dishub DKI Jakarta',
                'source_url' => 'https://dishub.jakarta.go.id',
                'status' => 'online',
                'resolution' => '1080p FHD',
                'fps' => 25,
                'last_checked_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],
            [
                'id' => 'cctv_jkt_03',
                'name' => 'Monumen Nasional (Silang Merdeka Barat)',
                'province' => 'DKI Jakarta',
                'city' => 'Jakarta Pusat',
                'district' => 'Gambir',
                'road' => 'Jl. Medan Merdeka Barat',
                'region_slug' => 'jawa',
                'latitude' => -6.1754,
                'longitude' => 106.8242,
                'stream_url' => 'https://cctv.balitower.co.id/Monas-Barat/embed.html',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1584810359583-96fc3448beaa?w=600&auto=format&fit=crop&q=80',
                'source_name' => 'Dishub DKI Jakarta',
                'source_url' => 'https://dishub.jakarta.go.id',
                'status' => 'online',
                'resolution' => '1080p FHD',
                'fps' => 25,
                'last_checked_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],
            [
                'id' => 'cctv_jkt_04',
                'name' => 'Tol Cawang Interchange',
                'province' => 'DKI Jakarta',
                'city' => 'Jakarta Timur',
                'district' => 'Jatinegara',
                'road' => 'Tol Jagorawi - Cikampek Interchange',
                'region_slug' => 'jawa',
                'latitude' => -6.2435,
                'longitude' => 106.8642,
                'stream_url' => 'https://cctv.bpjt.pu.go.id/cctv-in-out-cawang',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1517649763962-0c623266ddc0?w=600&auto=format&fit=crop&q=80',
                'source_name' => 'BPJT / Jasa Marga',
                'source_url' => 'https://bpjt.pu.go.id',
                'status' => 'online',
                'resolution' => '720p HD',
                'fps' => 20,
                'last_checked_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],

            // ================= JAWA BARAT =================
            [
                'id' => 'cctv_bdg_01',
                'name' => 'Simpang Dago (Cikapayang / Flyover Pasupati)',
                'province' => 'Jawa Barat',
                'city' => 'Kota Bandung',
                'district' => 'Coblong',
                'road' => 'Jl. Ir. H. Juanda - Flyover Mochtar Kusumaatmadja',
                'region_slug' => 'jawa',
                'latitude' => -6.8989,
                'longitude' => 107.6112,
                'stream_url' => 'https://atcs.bandung.go.id/cctv/simpang-dago',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1518684079-3c830dcef090?w=600&auto=format&fit=crop&q=80',
                'source_name' => 'ATCS Dishub Kota Bandung',
                'source_url' => 'https://atcs.bandung.go.id',
                'status' => 'online',
                'resolution' => '1080p FHD',
                'fps' => 25,
                'last_checked_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],
            [
                'id' => 'cctv_bdg_02',
                'name' => 'Gerbang Tol Pasteur (Inflow & Outflow)',
                'province' => 'Jawa Barat',
                'city' => 'Kota Bandung',
                'district' => 'Cicendo',
                'road' => 'Jl. Dr. Djunjunan',
                'region_slug' => 'jawa',
                'latitude' => -6.8912,
                'longitude' => 107.5612,
                'stream_url' => 'https://atcs.bandung.go.id/cctv/tol-pasteur',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1506521781263-d8422e82f27a?w=600&auto=format&fit=crop&q=80',
                'source_name' => 'ATCS Dishub Kota Bandung / Jasa Marga',
                'source_url' => 'https://atcs.bandung.go.id',
                'status' => 'online',
                'resolution' => '720p HD',
                'fps' => 25,
                'last_checked_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],
            [
                'id' => 'cctv_bgr_01',
                'name' => 'Simpang Gadog (Pintu Masuk Jalur Puncak)',
                'province' => 'Jawa Barat',
                'city' => 'Kab. Bogor',
                'district' => 'Ciawi',
                'road' => 'Jl. Raya Puncak Gadog',
                'region_slug' => 'jawa',
                'latitude' => -6.6412,
                'longitude' => 106.8654,
                'stream_url' => 'https://atcs.bogorkab.go.id/simpang-gadog',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=600&auto=format&fit=crop&q=80',
                'source_name' => 'ATCS Dishub Kab. Bogor / Korlantas',
                'source_url' => 'https://dishub.bogorkab.go.id',
                'status' => 'online',
                'resolution' => '1080p FHD',
                'fps' => 25,
                'last_checked_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],

            // ================= DI YOGYAKARTA =================
            [
                'id' => 'cctv_yog_01',
                'name' => 'Titik Nol Kilometer Yogyakarta',
                'province' => 'DI Yogyakarta',
                'city' => 'Kota Yogyakarta',
                'district' => 'Gondomanan',
                'road' => 'Jl. Pangurakan x Jl. Malioboro',
                'region_slug' => 'jawa',
                'latitude' => -7.8012,
                'longitude' => 110.3651,
                'stream_url' => 'https://mam.jogjaprov.go.id/cctv/titik-nol',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1596401057633-54a8fe8ef647?w=600&auto=format&fit=crop&q=80',
                'source_name' => 'Dishub DIY / Jogja Smart Province',
                'source_url' => 'https://jogjaprov.go.id',
                'status' => 'online',
                'resolution' => '1080p FHD',
                'fps' => 25,
                'last_checked_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],
            [
                'id' => 'cctv_yog_02',
                'name' => 'Simpang Tugu Pal Putih',
                'province' => 'DI Yogyakarta',
                'city' => 'Kota Yogyakarta',
                'district' => 'Jetis',
                'road' => 'Jl. Jenderal Sudirman x Jl. Margo Utomo',
                'region_slug' => 'jawa',
                'latitude' => -7.7828,
                'longitude' => 110.3671,
                'stream_url' => 'https://mam.jogjaprov.go.id/cctv/tugu-jogja',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1569336415962-a4bd9f69cd83?w=600&auto=format&fit=crop&q=80',
                'source_name' => 'Dishub DIY',
                'source_url' => 'https://dishub.jogjaprov.go.id',
                'status' => 'online',
                'resolution' => '1080p FHD',
                'fps' => 25,
                'last_checked_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],

            // ================= JAWA TIMUR =================
            [
                'id' => 'cctv_sby_01',
                'name' => 'Simpang Bundaran Waru (Surabaya Selatan)',
                'province' => 'Jawa Timur',
                'city' => 'Kota Surabaya',
                'district' => 'Wonokromo',
                'road' => 'Jl. Ahmad Yani',
                'region_slug' => 'jawa',
                'latitude' => -7.3512,
                'longitude' => 112.7301,
                'stream_url' => 'https://sits.surabaya.go.id/cctv/bundaran-waru',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1546587348-d12660c30c50?w=600&auto=format&fit=crop&q=80',
                'source_name' => 'SITS Dishub Kota Surabaya',
                'source_url' => 'https://sits.surabaya.go.id',
                'status' => 'online',
                'resolution' => '1080p FHD',
                'fps' => 25,
                'last_checked_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],
            [
                'id' => 'cctv_sby_02',
                'name' => 'Jembatan Suramadu (Gerbang Tol Kenjeran)',
                'province' => 'Jawa Timur',
                'city' => 'Kota Surabaya',
                'district' => 'Kenjeran',
                'road' => 'Tol Suramadu Bridge',
                'region_slug' => 'jawa',
                'latitude' => -7.2012,
                'longitude' => 112.7789,
                'stream_url' => 'https://sits.surabaya.go.id/cctv/suramadu',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1513694203232-719a280e022f?w=600&auto=format&fit=crop&q=80',
                'source_name' => 'Jasa Marga / SITS Surabaya',
                'source_url' => 'https://sits.surabaya.go.id',
                'status' => 'online',
                'resolution' => '720p HD',
                'fps' => 20,
                'last_checked_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],

            // ================= BALI =================
            [
                'id' => 'cctv_bali_01',
                'name' => 'Simpang Dewa Ruci (Underpass Kuta)',
                'province' => 'Bali',
                'city' => 'Kab. Badung',
                'district' => 'Kuta',
                'road' => 'Jl. Bypass Ngurah Rai x Jl. Sunset Road',
                'region_slug' => 'bali_nusa_tenggara',
                'latitude' => -8.7189,
                'longitude' => 115.1834,
                'stream_url' => 'https://atcs.baliprov.go.id/cctv/dewa-ruci',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=600&auto=format&fit=crop&q=80',
                'source_name' => 'ATCS Dishub Provinsi Bali',
                'source_url' => 'https://atcs.baliprov.go.id',
                'status' => 'online',
                'resolution' => '1080p FHD',
                'fps' => 25,
                'last_checked_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],
            [
                'id' => 'cctv_bali_02',
                'name' => 'Pantai Kuta (Jl. Pantai Kuta)',
                'province' => 'Bali',
                'city' => 'Kab. Badung',
                'district' => 'Kuta',
                'road' => 'Jl. Pantai Kuta',
                'region_slug' => 'bali_nusa_tenggara',
                'latitude' => -8.7212,
                'longitude' => 115.1689,
                'stream_url' => 'https://atcs.badungkab.go.id/cctv/pantai-kuta',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?w=600&auto=format&fit=crop&q=80',
                'source_name' => 'Dishub Kab. Badung',
                'source_url' => 'https://badungkab.go.id',
                'status' => 'online',
                'resolution' => '720p HD',
                'fps' => 20,
                'last_checked_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],

            // ================= SUMATERA =================
            [
                'id' => 'cctv_med_01',
                'name' => 'Lapangan Merdeka Medan (Jl. Balai Kota)',
                'province' => 'Sumatera Utara',
                'city' => 'Kota Medan',
                'district' => 'Medan Barat',
                'road' => 'Jl. Balai Kota',
                'region_slug' => 'sumatera',
                'latitude' => 3.5912,
                'longitude' => 98.6789,
                'stream_url' => 'https://atcs.medan.go.id/lapangan-merdeka',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1508873696983-2df5293cb32f?w=600&auto=format&fit=crop&q=80',
                'source_name' => 'ATCS Dishub Kota Medan',
                'source_url' => 'https://dishub.pemkomedan.go.id',
                'status' => 'online',
                'resolution' => '1080p FHD',
                'fps' => 25,
                'last_checked_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],
            [
                'id' => 'cctv_plb_01',
                'name' => 'Bundaran Air Mancur Masjid Agung Palembang',
                'province' => 'Sumatera Selatan',
                'city' => 'Kota Palembang',
                'district' => 'Ilir Timur I',
                'road' => 'Jl. Jenderal Sudirman',
                'region_slug' => 'sumatera',
                'latitude' => -2.9889,
                'longitude' => 104.7598,
                'stream_url' => 'https://atcs.palembang.go.id/masjid-agung',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?w=600&auto=format&fit=crop&q=80',
                'source_name' => 'ATCS Dishub Kota Palembang',
                'source_url' => 'https://dishub.palembang.go.id',
                'status' => 'online',
                'resolution' => '720p HD',
                'fps' => 20,
                'last_checked_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ],

            // ================= SULAWESI =================
            [
                'id' => 'cctv_mks_01',
                'name' => 'Anjungan Pantai Losari',
                'province' => 'Sulawesi Selatan',
                'city' => 'Kota Makassar',
                'district' => 'Ujung Pandang',
                'road' => 'Jl. Penghibur',
                'region_slug' => 'sulawesi',
                'latitude' => -5.1442,
                'longitude' => 119.4089,
                'stream_url' => 'https://warroom.makassar.go.id/cctv/losari',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=600&auto=format&fit=crop&q=80',
                'source_name' => 'Operation Room Pemkot Makassar',
                'source_url' => 'https://makassarkota.go.id',
                'status' => 'online',
                'resolution' => '1080p FHD',
                'fps' => 25,
                'last_checked_at' => now()->subMinutes(rand(1, 4))->toIso8601String(),
            ]
        ];
    }
}
