<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Anggaran</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f6fa;
            font-family: 'Inter', sans-serif;
        }
        .content {
            padding: 30px 60px;
        }
        .content h3 {
            font-weight: 600;
        }
        .card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .card h6 {
            font-weight: 600;
            margin-bottom: 15px;
        }
        .table th, .table td {
            vertical-align: middle;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="content">
        @yield('content')
    </div>
</body>
</html>
