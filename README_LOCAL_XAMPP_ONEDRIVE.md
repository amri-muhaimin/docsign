# DocSign Mini (Local XAMPP + OneDrive workflow)

Tujuan: mahasiswa upload PDF ke OneDrive → file sinkron ke laptop → dosen tanda tangan via localhost → hasil kembali ke OneDrive.

## 1) Jalankan di XAMPP
1. Copy folder `docsign` ke:
   `C:\xampp\htdocs\docsign\`
2. Jalankan Apache di XAMPP
3. Buka:
   `http://localhost/docsign/`

## 2) Arahkan storage ke OneDrive (opsi terbaik)
Agar file asli/hasil otomatis tersinkron, pindahkan folder `storage` ke OneDrive lalu buat junction.

### Contoh struktur OneDrive
- `OneDrive\DocSign\incoming\`  (mahasiswa upload)
- `OneDrive\DocSign\signed\`    (hasil final untuk dibagikan)
- `OneDrive\DocSign\storage\`   (dipakai aplikasi)

### Langkah junction
1. Stop Apache (sementara)
2. Pindahkan:
   - dari `C:\xampp\htdocs\docsign\storage\`
   - ke   `C:\Users\<NAMA_WINDOWS>\OneDrive\DocSign\storage\`
3. Buka Command Prompt as Administrator:
   ```
   mklink /J "C:\xampp\htdocs\docsign\storage" "C:\Users\<NAMA_WINDOWS>\OneDrive\DocSign\storage"
   ```
4. Start Apache lagi

## 3) Ganti password admin
- Generate hash:
  ```
  php tools/password_hash.php "PasswordBaruYangKuat"
  ```
- Tempel hasilnya ke `config.php` pada `ADMIN_PASS_HASH`.

## 4) Catatan keamanan
Folder `tools/` sebaiknya **tidak** diupload ke hosting publik. Untuk penggunaan lokal, aman.
