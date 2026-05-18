<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <link rel="stylesheet" href="{{ asset('admin-ui.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-wrap login-clean-wrap login-simple-wrap">
        <div class="login-hero login-clean-hero login-title-only">
            <div class="login-logo">
                <div style="font-size:60px; margin-bottom:10px;"><i class="fa-regular fa-futbol"></i></div>
                SPORTS FIELD<br>RENTAL
            </div>
        </div>

        <div class="login-card login-clean-card">
            <div class="login-form-title">SPORTS FIELD RENTAL</div>

            @if(session('error'))
                <div style="margin-bottom:12px; font-size:12px; color:#c61f1f;">{{ session('error') }}</div>
            @endif

            <form action="{{ route('login.submit') }}" method="POST">
                @csrf
                <div class="form-group" style="text-align:left;">
                    <label>Email Admin</label>
                    <input class="input-ui" type="email" name="email" value="{{ old('email', 'admin@gmail.com') }}">
                </div>
                <div class="form-group" style="text-align:left;">
                    <label>Password</label>
                    <input class="input-ui" type="password" name="password" value="admin123">
                </div>
                <button class="btn-ui btn-green login-btn" type="submit">Masuk ke Dashboard</button>
            </form>
        </div>
    </div>
</body>
</html>
