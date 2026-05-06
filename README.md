# Esoftdream Helper Module

Helper module yang menyediakan berbagai fungsi utility untuk query database, pagination, sanitasi response, dan generasi kode.

## Versi

Project ini memiliki dua versi:

### 1. Versi CodeIgniter 4 (`query_helper.php`)
- Membutuhkan dependensi `codeigniter4/framework`
- Menggunakan `CodeIgniter\Database\BaseConnection` untuk koneksi database
- Sesuai untuk project yang sudah menggunakan CodeIgniter 4

### 2. Versi Standalone (`query_helper_standalone.php`) **(Recommended)**
- **Tidak membutuhkan framework apa pun**
- Menggunakan **PDO (PHP Data Objects)** sebagai standar koneksi database
- Kompatibel dengan semua database yang didukung PDO (MySQL, PostgreSQL, SQLite, dll)
- Lebih ringan dan fleksibel

## Instalasi

### Menggunakan Composer

```bash
composer require esoftdream/helper
```

### Manual Installation

Copy folder `src/Helpers/` ke project Anda dan include file yang diperlukan.

## Penggunaan

### Versi Standalone (PDO)

Fungsi-fungsi akan **auto load** melalui Composer autoloader. Cukup require `vendor/autoload.php` sekali di entry point, dan semua fungsi helper langsung bisa digunakan.

```php
<?php

require_once 'vendor/autoload.php';

// Koneksi database menggunakan PDO
$pdo = new PDO('mysql:host=localhost;dbname=my_database', 'username', 'password');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fungsi langsung bisa dipakai (global namespace)
$pagination = page_generate($totalData, $currentPage, $limit);
$result = validate_date('2024-01-15', 'Y-m-d');

// Generate data query dengan pagination
$params = [
    'page' => 1,
    'limit' => 10,
    'search' => 'keyword',
    'sort' => '-created_at', // DESC
];

$query = [
    'table_and_join' => 'FROM users',
    'field_show' => ['id', 'name', 'email'],
    'order' => 'created_at DESC',
    'where_detail' => [],
];

$result = generate_data_query($params, $query, $pdo);

// Generate kode unik
$code = generate_code($pdo, 'products', 'product_code', [], 'PRD-', 5);
```

### Versi CodeIgniter 4

```php
<?php

// Menggunakan di dalam CodeIgniter 4
$db = \Config\Database::connect();

$result = generate_data_query($params, $query, $db);
```

## Fungsi yang Tersedia

### Utility Functions
- `validate_date($date, $format)` - Validasi format tanggal
- `page_generate($total, $pagenum, $limit)` - Generate informasi pagination
- `sanitization_response($data, $exclude_columns)` - Sanitasi response dari database
- `normalize_array($arr)` - Normalisasi array

### Query Helpers
- `generate_field_query($arr)` - Generate field untuk SQL query
- `filter_params($params, $fieldAllowed, $queryReturn)` - Filter parameter
- `filter_params_array($whereFilter, $fieldAllowed, $queryReturn)` - Filter array parameter
- `search_query($search, $field, $queryReturn)` - Generate search query
- `where_detail($arrWhere, $queryReturn)` - Generate WHERE clause
- `where_raw($rawWhere, $queryReturn, $bindings)` - Raw WHERE clause

### Database Functions (PDO/CI4)
- `generate_data_query($params, $query, $connection, $isArray, $returnQuery)` - Generate data dengan pagination
- `generate_detail_query($query, $connection, $isArray, $returnQuery)` - Generate single detail query
- `generate_code($connection, $table, $field, $where, $prefix, $digit)` - Generate sequential code
- `generate_random_code($connection, $table, $field, $length)` - Generate random unique code
- `generate_client_code($connection)` - Generate client code
- `select_max($connection, $table, $field, $where)` - Get maximum value
- `select_min($connection, $table, $field, $where)` - Get minimum value
- `insert_media($connection, $caption, $url, $type, $mime, $full_path, $masterId, $table)` - Insert media
- `delete_media($connection, $masterId, $typeMaster)` - Delete media dan file terkait

## Contoh Query Configuration

```php
$query = [
    'table_and_join' => 'FROM users LEFT JOIN profiles ON users.id = profiles.user_id',
    'field_show' => [
        'users.id',
        'users.name',
        'profiles.phone',
        'created_at datetime',
    ],
    'where_detail' => [
        'users.status' => 1,
    ],
    'order' => 'users.created_at DESC',
    'group_by' => '',
    'having' => [],
    'limit' => 10,
    'pagination' => true,
    'exclude_numeric_conversion' => ['phone'], // kolom yang tidak dikonversi ke numeric
];
```

## Requirement

- PHP 8.0 atau lebih baru
- PDO extension (untuk versi standalone)
- CodeIgniter 4 (hanya untuk versi CI4)

## License

Proprietary

## Author

Esoftdream
