# ERD Sistem Penjemputan Sampah Palembang

Relasi utama:

`users (1) -> (N) pickup_requests`

`users role OFFICER (1) -> (N) officers`

`officers (1) -> (N) pickup_requests`

`pickup_requests (1) -> (N) pickup_transactions`

`users (1) -> (N) user_notifications`

`pickup_requests (1) -> (N) user_notifications`

`users (1) -> (N) activity_logs`

`pickup_requests (1) -> (N) activity_logs`

Status penjemputan:

`Menunggu -> Diproses -> Terangkut -> Selesai`

Catatan implementasi:

- Tabel petugas pada kode bernama `officers`, dengan `officer_id` sebagai foreign key ke `users.user_id`.
- Tabel notifikasi aplikasi bernama `user_notifications` agar tidak bentrok dengan tabel `notifications` lama yang dipakai untuk kanal EMAIL/SMS.
