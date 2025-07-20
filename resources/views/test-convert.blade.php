<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Test Convert Address</title>
</head>
<body>
    <h1>Test Chuyển Đổi Địa Chỉ</h1>

    <form method="POST" action="{{ route('test.convert.address') }}">
        @csrf
        <label>Địa chỉ:</label>
        <input type="text" name="address" placeholder="Ví dụ: Phường Linh Trung, Quận Thủ Đức, TP.HCM" style="width: 400px">
        <button type="submit">Chuyển đổi</button>
    </form>

    @if(session('result'))
        <h3>Kết quả:</h3>
        <pre>{{ print_r(session('result'), true) }}</pre>
    @endif
</body>
</html>
