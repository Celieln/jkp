# Sistem JKP

<p align="center">
  Sistem pengelolaan data Jaminan Kehilangan Pekerjaan (JKP) dengan export Excel.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.x-%23777BB4?style=for-the-badge&logo=php&logoColor=white"/>
  <img src="https://img.shields.io/badge/Bootstrap-5-%237952B3?style=for-the-badge&logo=bootstrap&logoColor=white"/>
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge"/>
  <img src="https://img.shields.io/badge/PRs-Welcome-brightgreen?style=for-the-badge"/>
</p>



## Highlight

- **Front-end first** - repository berisi tampilan depan (public UI) yang siap jalan
- **Ringan & cepat** - tanpa framework berat, load cepat
- **Mudah di-deploy** - cukup PHP + database, tanpa setup rumit
- **Keamanan dasar terpasang** - prepared statements, sanitization, password hashing

## Fitur Utama

- CRUD data peserta
- Mapping kolom import
- Export Excel
- Rekap data otomatis

## Teknologi

<details>
<summary><b>Lihat detail teknologi</b></summary>

**Backend**
- PHP 8.x - server-side scripting dengan **PDO** + **prepared statements**
- CRUD + mapping data + export Excel (PHPSpreadsheet)
- Session-based authentication (bcrypt)

**Frontend**
- HTML5, CSS3, JavaScript (ES6+)
- Bootstrap 5 responsive dashboard

**Database**
- MySQL 8 / MariaDB - skema ternormalisasi

**Tooling & DevOps**
- Composer
- Git & GitHub
- Laragon/WAMP
</details>

## Struktur Proyek

```
jkp
  includes/    # Komponen yang di-include (header, footer, dll)
  assets/      # CSS, JS, gambar
  *.php        # Halaman tampilan depan
```

## Menjalankan

Prasyarat: [Laragon](https://laragon.org) / [XAMPP](https://www.apachefriends.org)

1. Clone repository:

   ```bash
   git clone https://github.com/Celieln/jkp.git
   ```

2. Letakkan folder di `laragon/www/` atau `htdocs/`.
3. Buka `http://localhost/jkp`.

## Kontribusi

Kontribusi sangat diterima! Baca [CONTRIBUTING](CONTRIBUTING.md) dahulu, lalu buat Pull Request atau buka [Issues](https://github.com/Celieln/jkp/issues) untuk melaporkan bug / request fitur.

## Lisensi

Distributed under the [MIT](LICENSE) License. (c) [Celieln](https://github.com/Celieln)
