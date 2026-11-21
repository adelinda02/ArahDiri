<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Kode reset kata sandi</title></head>
<body style="margin:0;padding:0;background:#F4F8FC;font-family:'Plus Jakarta Sans',Arial,Helvetica,sans-serif;color:#0B3C5D;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F4F8FC;padding:32px 12px;">
        <tr><td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background:#FFFFFF;border:1px solid #CFE2F2;border-radius:16px;">
                <tr><td style="padding:28px 28px 8px;">
                    <p style="margin:0 0 4px;font-size:12px;font-weight:700;letter-spacing:.12em;color:#1D70B8;">ARAHDIRI</p>
                    <h1 style="margin:0 0 12px;font-size:20px;line-height:1.3;color:#0B3C5D;">Kode reset kata sandi</h1>
                    <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#2C5470;">Halo {{ $name }}, gunakan kode berikut untuk membuat kata sandi baru di ArahDiri.</p>
                </td></tr>
                <tr><td align="center" style="padding:0 28px;">
                    <div style="display:inline-block;padding:14px 24px;border-radius:12px;background:#E3F0FB;font-size:32px;font-weight:800;letter-spacing:10px;color:#0B3C5D;font-family:'Courier New',monospace;">{{ $code }}</div>
                </td></tr>
                <tr><td style="padding:18px 28px 28px;">
                    <p style="margin:0 0 10px;font-size:14px;line-height:1.6;color:#2C5470;">Kode ini berlaku <strong>{{ $minutes }} menit</strong> dan hanya bisa dipakai sekali.</p>
                    <p style="margin:0;font-size:13px;line-height:1.6;color:#4A657A;">Jangan berikan kode ini kepada siapa pun, termasuk yang mengaku admin. Kalau kamu tidak meminta reset kata sandi, abaikan email ini; kata sandimu tidak berubah.</p>
                </td></tr>
            </table>
            <p style="margin:16px 0 0;font-size:12px;color:#4A657A;">ArahDiri 2026</p>
        </td></tr>
    </table>
</body>
</html>
