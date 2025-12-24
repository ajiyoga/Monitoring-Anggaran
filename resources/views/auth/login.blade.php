<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Monitoring Sistem Anggaran Pelindo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            height: 100vh;
            display: flex;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #fff;
            background: url('/foto.WEBP') no-repeat center center fixed;
            background-size: cover;
            overflow: hidden;
            position: relative;
        }

        /* Overlay lembut di seluruh layar */
        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.35);
            z-index: 0;
        }

        /* 🔹 Bagian kiri: form login */
        .left-section {
            position: relative;
            width: 650px;
            /* 🔹 Diperbesar lagi dari 550px ke 650px */
            height: 100vh;
            background: rgba(30, 30, 30, 0.45);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            padding: 70px 50px;
            z-index: 1;
            box-shadow: 6px 0 25px rgba(0, 0, 0, 0.45);
        }

        .login-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 50px;
            text-align: center;
        }

        .login-header img {
            height: 65px;
        }

        .login-header h4 {
            font-weight: bold;
            margin: 0;
            line-height: 1.3;
            color: #fff;
            letter-spacing: 0.5px;
            font-size: 20px;
        }

        label {
            color: #fff;
            font-weight: 500;
            margin-bottom: 6px;
        }

        .form-control {
            border-radius: 10px;
            border: none;
            background-color: rgba(255, 255, 255, 0.9);
            color: #000;
            padding: 12px 15px;
            font-size: 15px;
        }

        .form-control::placeholder {
            color: #555;
        }

        .form-control:focus {
            background-color: #fff;
            box-shadow: 0 0 8px rgba(255, 255, 255, 0.6);
        }

        .btn-primary {
            width: 100%;
            border-radius: 10px;
            background-color: #007bff;
            border: none;
            font-weight: 600;
            padding: 12px;
            font-size: 16px;
            transition: background-color 0.3s, transform 0.2s;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }

        .text-center a {
            color: #fff;
            text-decoration: underline;
            transition: color 0.3s;
        }

        .text-center a:hover {
            color: #dcdcdc;
        }

        /* Responsif untuk layar kecil */
        @media (max-width: 768px) {
            .left-section {
                width: 100%;
                height: auto;
                padding: 40px 25px;
            }

            .login-header img {
                height: 50px;
            }

            .login-header h4 {
                font-size: 18px;
            }
        }
    </style>
</head>

<body>
    <div class="left-section">
        <div class="login-header">
            <img src="pelindo.PNG" alt="Logo Pelindo">
            <h4>MONITORING SISTEM<br>ANGGARAN PELINDO</h4>
        </div>

        <form action="{{ route('login.post') }}" method="POST" style="width:100%; max-width:420px;">
            @csrf
            <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
            </div>

            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" placeholder="Masukkan email" required>
            </div>

            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
            </div>

            <button type="submit" class="btn btn-primary">Masuk</button>
        </form>

        <p class="text-center mt-3">
            Belum punya akun? <a href="{{ route('register') }}">Daftar</a>
        </p>
    </div>
</body>

</html>
