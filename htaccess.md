# Hosting di Hostinger
Taruh file .htaccess di dalam root folder dengan isi filenya sebagai berikut:

```
<IfModule mod_rewrite.c>
 
RewriteEngine On
 
RewriteRule ^(.*)$ public/$1 [L]

</IfModule>

<FilesMatch "\.(php4|php5|php3|php2|php|phtml)$">
SetHandler application/x-lsphp83
</FilesMatch>
```

Penjelasan:
- Bagian `<IfModule mod_rewrite.c>` memastikan bahwa aturan hanya diterapkan jika modul mod_rewrite diaktifkan di server.
- `RewriteEngine On` mengaktifkan mesin penulisan ulang URL.
- `RewriteRule ^(.*)$ public/$1 [L]` mengarahkan semua permintaan ke folder `public`.
- Bagian `<FilesMatch "\.(php4|php5|php3|php2|php|phtml)$">` menetapkan handler khusus untuk file PHP agar menggunakan versi PHP 8.3 di Hostinger. 

# Hosting di Domainesia
Taruh file .htaccess di dalam folder **public** dengan isi filenya sebagai berikut:

```
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Base path untuk subdomain
    RewriteBase /

    # Hilangkan akses ke index.php langsung
    RewriteRule ^index\.php$ - [L]

    # Arahkan semua request ke index.php Laravel
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Pastikan akses CORS (opsional)
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
</IfModule>

# Nonaktifkan directory listing
Options -Indexes
```

Penjelasan:
- Bagian `<IfModule mod_rewrite.c>` memastikan bahwa aturan hanya diterapkan jika modul mod_rewrite diaktifkan di server.
- `RewriteEngine On` mengaktifkan mesin penulisan ulang URL.
- `RewriteBase /` menetapkan basis penulisan ulang ke root.
- `RewriteRule ^index\.php$ - [L]` mencegah akses langsung ke `index.php`.
- `RewriteCond %{REQUEST_FILENAME} !-d` dan `RewriteCond %{REQUEST_FILENAME} !-f` memeriksa apakah permintaan bukan untuk direktori atau file yang ada.
- `RewriteRule ^ index.php [L]` mengarahkan semua permintaan yang tidak sesuai ke `index.php`.
- Aturan penulisan ulang memastikan bahwa semua permintaan diarahkan ke `index.php` Laravel, kecuali jika file atau direktori yang diminta ada.
- Bagian `<IfModule mod_headers.c>` menambahkan header CORS untuk mengizinkan akses dari semua origin (opsional).
- `Headers set Access-Control-Allow-Origin "*"` mengizinkan akses lintas sumber (CORS) dari semua domain.
- `Options -Indexes` menonaktifkan listing direktori untuk keamanan tambahan.  
