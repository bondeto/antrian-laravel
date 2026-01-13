# Sistem Antrian Pelayanan Kantor (3 Lantai)

Sistem antrian realtime menggunakan Laravel Latest, Inertia Vue, dan Laravel Reverb (WebSocket). Database menggunakan PostgreSQL.

## Fitur
- **Kiosk**: Ambil nomor antrian berdasarkan layanan per lantai.
- **Monitor**: Layar monitor per lantai dengan Voice (TTS) otomatis saat pemanggilan.
- **Operator**: Panel operator untuk memanggil, memanggil ulang, dan memproses antrian.
- **Realtime**: Tidak ada polling database, semua update menggunakan WebSocket.

## Persyaratan
- Docker Desktop
- PHP 8.2+ & Composer (untuk setup awal)
- Node.js & NPM

## Cara Instalasi

1. **Clone Repository**
   ```bash
   git clone https://github.com/bondeto/antrian-laravel.git
   cd antrian-laravel
   ```

2. **Setup Environment**
   ```bash
   cp .env.example .env
   # Sesuaikan DB_PASSWORD di .env jika diperlukan
   ```

3. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

4. **Jalankan Docker Containers**
   ```bash
   # Ini akan menjalankan PostgreSQL, Redis, dan Laravel Sail (App Container)
   docker-compose up -d
   ```

5. **Generate Key & Migrasi Database**
   ```bash
   # Gunakan Sail jika PHP host tidak memiliki driver pdo_mysql
   ./vendor/bin/sail artisan key:generate
   ./vendor/bin/sail artisan migrate --seed
   ```

## Cara Menjalankan

### Cara Cepat (Windows)
Cukup klik dua kali file `run-app.bat` di folder root. File ini akan otomatis menjalankan:
1. Docker Containers
2. Laravel Serve
3. Vite Dev Session
4. Reverb WebSocket Server
5. Queue Worker

### Cara Manual (3-4 Terminal)
Jika ingin menjalankan secara manual, gunakan perintah berikut di terminal terpisah:

**Terminal 1: Vite (Frontend)**
```bash
npm run dev
```

**Terminal 2: Reverb (WebSocket)**
```bash
./vendor/bin/sail artisan reverb:start
```

**Terminal 3: Worker (Opsional, jika menggunakan queue)**
```bash
./vendor/bin/sail artisan queue:work
```

**Terminal 4: Laravel Serve (Jika tidak menggunakan Docker Sail untuk serve)**
```bash
php artisan serve
```

## Akun Login Operator
- **Email**: `op1@example.com`
- **Password**: `password`

## URL Akses
- **Kiosk**: `http://localhost:8000/`
- **Monitor Lantai 1**: `http://localhost:8000/monitor/1`
- **Monitor Lantai 2**: `http://localhost:8000/monitor/2`
- **Operator**: `http://localhost:8000/operator`
