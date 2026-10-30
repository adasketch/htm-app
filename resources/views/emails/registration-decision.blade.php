<!doctype html>
<html lang="id">
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Halo, {{ $user->name }}</h2>
    @if ($approved)
        <p>Pendaftaran akun HotelTask Anda telah disetujui.</p>
        <p>Silakan masuk menggunakan email dan password yang Anda daftarkan.</p>
    @else
        <p>Mohon maaf, pendaftaran akun HotelTask Anda belum disetujui.</p>
        <p>Silakan hubungi administrator workspace jika Anda memerlukan informasi lebih lanjut.</p>
    @endif
</body>
</html>
