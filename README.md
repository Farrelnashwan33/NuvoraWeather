# ⚡ Nuvora Weather — Modern Weather Intelligence Platform

> **"Know Your Weather. Plan Your Day."**

Nuvora Weather adalah aplikasi pemantau cuaca modern generasi berikutnya dengan tampilan *dark futuristic dashboard*, nuansa *deep midnight blue*, *glassmorphism*, aksen *cyan glow*, animasi cuaca dinamis, serta navigasi responsif (*desktop, tablet & mobile-first*).

---

## 🌟 Fitur Utama

- **No Sign-Up Required for Users**: Pengguna publik dapat langsung melihat cuaca tanpa login atau registrasi.
- **Geolocation Auto-Detection**: Deteksi otomatis koordinat GPS pengguna melalui Geolocation API dengan fallback mulus ke kota tersimpan di `localStorage` (default: Bandung, Indonesia).
- **Search City & Remote Indonesian Regions**: Pencarian wilayah super lengkap mencakup seluruh penjuru Indonesia — dari kota metropolitan hingga **wilayah 3T dan pulau-pulau terluar/terpencil** (misal: *Pulau Miangas, Pulau Rote, Sabang, Merauke, Natuna, Krayan Nunukan, Banda Neira, Raja Ampat, Asmat, Wamena, Simeulue, Mentawai, Alor, Sumba, Enggano, Bawean, Karimunjawa, Wakatobi, Morotai, Boven Digoel, Mahakam Ulu, Fakfak, dll.*) berbasis hybrid OpenStreetMap Nominatim, database 3T lokal, dan Open-Meteo.
- **Hero Weather Card**: Salam dinamis (*Good Morning/Afternoon/Evening*), suhu utama besar, kondisi cuaca, feels like, rentang suhu harian (High/Low).
- **6-Metric Highlights Grid**: Kelembapan (*Humidity*), Kecepatan & Arah Angin (*Wind speed & compass needle*), Tekanan Udara (*Pressure*), Jarak Pandang (*Visibility*), Indeks UV (*UV Index* dengan bar status), Titik Embun (*Dew Point*).
- **24-Hour Hourly Forecast**: Slider horizontal responsif untuk prakiraan 24 jam dengan icon animasi, probabilitas hujan (%), dan kecepatan angin.
- **7-Day Extended Forecast**: Prakiraan mingguan dengan rentang visual suhu proporsional dan kondisi cuaca.
- **Sun & Daylight Trajectory**: Visualisasi busur posisi matahari, waktu matahari terbit (*Sunrise*), terbenam (*Sunset*), dan durasi siang hari.
- **Interactive Weather Radar & Google Maps**: Peta cuaca interaktif dengan integrasi Google Maps (pilihan layer: *Google Satellite/Hybrid*, *Google Street/Roadmap*, *Google Terrain*, *Dark Canvas*, dan *Google Maps Live Embed*), tombol navigasi langsung ke Google Maps, serta mode layar penuh (*Fullscreen*).
- **Dynamic Weather Atmosphere Background**: Latar belakang ambient yang berganti secara dinamis berdasarkan kondisi cuaca (*Sunny, Rainy, Thunderstorm, Clear Night, Snowy, Cloudy*).
- **Unit & Theme Toggle**: Pengalihan satuan suhu (°C / °F) dan tema (*Dark / Light Mode*) secara instan.
- **Mobile-First Bottom Navigation**: Navigasi bawah khusus mobile (*Home, Forecast, Radar, Cities, Quick Search*).
- **Admin Control Room & Security**: Portal admin terproteksi token Sanctum (`/admin/login` & `/admin/dashboard`) untuk memantau metrik API, mengelola kota unggulan (*Featured Cities*), melihat log request, dan mengubah setelan aplikasi (*App Settings*).

---

## 🛠️ Tech Stack & Arsitektur

- **Frontend**: Svelte 5 / SvelteKit, Tailwind CSS, Lucide Icons, Leaflet.js
- **Backend**: Laravel 11/12 (REST API, Service Architecture `WeatherService`, Sanctum Token Authentication, Rate Limiting & Caching)
- **Weather Provider**: Open-Meteo API & Open-Meteo Geocoding API (Real-time, tanpa hardcode)
- **Database**: MySQL (MAMP Compatible) / SQLite

---

## 🚀 Panduan Instalasi & Menjalankan Aplikasi

### 1. Cara Menjalankan MAMP
1. Buka aplikasi **MAMP** di komputer Anda.
2. Klik tombol **"Start Servers"** untuk mengaktifkan Apache/Nginx dan MySQL Server.
3. Pastikan port MySQL MAMP aktif (default: `8889` atau `3306`).

### 2. Membuat Database MySQL
1. Buka **phpMyAdmin** dari MAMP (biasanya di `http://localhost:8888/phpMyAdmin/` atau `http://localhost/phpMyAdmin/`).
2. Buat database baru bernama:
   ```sql
   CREATE DATABASE nuvora_weather CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

### 3. Konfigurasi `.env` Backend Laravel
Masuk ke direktori `backend/`:
```bash
cd backend
cp .env.example .env
```
Sesuaikan konfigurasi database MAMP pada file `backend/.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8889
DB_DATABASE=nuvora_weather
DB_USERNAME=root
DB_PASSWORD=root

WEATHER_API_URL=https://api.open-meteo.com/v1/forecast
GEOCODING_API_URL=https://geocoding-api.open-meteo.com/v1/search
WEATHER_CACHE_TTL_MINUTES=15
```
*(Catatan: Jika port MAMP Anda adalah `3306`, ubah `DB_PORT=3306`).*

### 4. Menjalankan Migration
Jalankan migrasi tabel database di direktori `backend/`:
```bash
php artisan migrate
```

### 5. Menjalankan Seeder
Isi data awal untuk Admin, Kota Unggulan (*Featured Cities*), dan Pengaturan:
```bash
php artisan db:seed
```
*Atau jalankan migrasi fresh sekaligus seeder:*
```bash
php artisan migrate:fresh --seed
```

### 6. Menjalankan Server Backend Laravel
Jalankan perintah berikut di direktori `backend/`:
```bash
php artisan serve --port=8000
```
API akan aktif dan siap menerima request di: `http://127.0.0.1:8000/api`

### 7. Menjalankan Frontend SvelteKit
Buka terminal baru, masuk ke direktori `frontend/`:
```bash
cd frontend
npm install
npm run dev
```
Aplikasi web akan dapat diakses di: `http://localhost:5173`

---

## 🔐 Kredensial & Akses Admin

- **URL Login Admin**: `http://localhost:5173/admin/login`
- **Email**: `admin@nuvora.com`
- **Password**: `password123`

### Fitur Admin Dashboard (`/admin/dashboard`):
1. **Overview & Metrics**: Total request, volume 24 jam terakhir, persentase keberhasilan API, error log count, provider status, dan kota yang paling sering dicari (*Top Cities*).
2. **Featured Cities**: Tambah kota baru dengan pencarian autofill otomatis, hapus kota, atau aktifkan/nonaktifkan kota yang muncul di bar kota global publik.
3. **Weather Request Logs**: Riwayat lengkap pencarian cuaca beserta koordinat, status respon HTTP, dan waktu permintaan.
4. **App Settings**: Pengaturan durasi cache TTL (menit), batas rate limiting, pesan notifikasi sistem/peringatan cuaca.

---

## 🌐 Endpoint REST API

| Method | Endpoint | Deskripsi | Auth |
|---|---|---|---|
| `GET` | `/api/weather/current?lat={lat}&lon={lon}&city={city}` | Data lengkap cuaca, highlights, hourly & daily | Publik |
| `GET` | `/api/weather/hourly?lat={lat}&lon={lon}` | Prakiraan per jam (24 jam) | Publik |
| `GET` | `/api/weather/daily?lat={lat}&lon={lon}` | Prakiraan 7 hari ke depan | Publik |
| `GET` | `/api/weather/search?q={query}` | Autocomplete pencarian kota & koordinat | Publik |
| `GET` | `/api/weather/location?latitude={lat}&longitude={lon}` | Cuaca & nama lokasi berdasarkan GPS | Publik |
| `GET` | `/api/cities/featured` | Daftar kota unggulan aktif dengan preview cuaca | Publik |
| `POST` | `/api/admin/login` | Login admin & generate token Sanctum | Publik |
| `POST` | `/api/admin/logout` | Logout admin & revoke token | Admin |
| `GET` | `/api/admin/stats` | Statistik performa & telemetri request | Admin |
| `GET` | `/api/admin/logs` | Log request cuaca | Admin |
| `GET` | `/api/admin/featured-cities` | Daftar semua kota unggulan | Admin |
| `POST` | `/api/admin/featured-cities` | Menambahkan kota unggulan baru | Admin |
| `DELETE` | `/api/admin/featured-cities/{id}` | Menghapus kota unggulan | Admin |
| `GET` | `/api/admin/settings` | Mengambil konfigurasi aplikasi | Admin |
| `POST` | `/api/admin/settings` | Menyimpan perubahan konfigurasi aplikasi | Admin |

---

## 🏗️ Cara Production Deployment

### 1. Build Frontend
```bash
cd frontend
npm run build
```

### 2. Konfigurasi Backend untuk Production
```bash
cd backend
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Setup Web Server (Nginx / Apache)
- Arahkan domain utama ke build SvelteKit atau reverse proxy Node.
- Arahkan sub-path `/api` atau subdomain `api.nuvora.com` ke direktori `backend/public/` Laravel dengan PHP-FPM.

---

*Dikembangkan dengan ❤️ untuk pengalaman pemantauan cuaca terbaik.*
