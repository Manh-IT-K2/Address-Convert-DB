<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Address Converter Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
        }

        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', sans-serif;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            overflow: hidden;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 1.25rem 1.5rem;
            border-bottom: none;
        }

        .btn-convert {
            background-color: var(--primary-color);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.35);
        }

        .btn-convert:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(78, 115, 223, 0.4);
            background-color: #3a5bd9;
        }

        .btn-convert:active {
            transform: translateY(1px);
        }

        .progress-container {
            margin: 30px 0;
        }

        .progress-title {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-weight: 600;
            color: #5a5c69;
        }

        .progress {
            height: 20px;
            border-radius: 10px;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            background-color: var(--success-color);
            transition: width 1s ease-in-out;
        }

        .failed-records {
            max-height: 500px;
            overflow-y: auto;
            border-radius: 10px;
            border: 1px solid #e3e6f0;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: #f8f9fc;
            color: #4e73df;
            font-weight: 700;
            border-bottom: 2px solid #e3e6f0;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }

        .reason-col {
            max-width: 200px;
            word-wrap: break-word;
        }

        .footer {
            margin-top: 3rem;
            padding: 1.5rem 0;
            text-align: center;
            color: #858796;
            font-size: 0.9rem;
        }

        .success-count {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--success-color);
        }

        .failed-count {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--danger-color);
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card animate__animated animate__fadeIn">
                    <div class="card-header animate__animated animate__fadeInDown">
                        <i class="fas fa-map-marked-alt me-2"></i> Công cụ chuyển đổi địa chỉ
                    </div>

                    <div class="card-body">
                        @if(session('success'))
                        <div class="alert alert-success animate__animated animate__fadeIn">
                            {{ session('success') }}
                        </div>
                        @endif

                        <div class="mb-4 animate__animated animate__fadeIn">
                            <p>Công cụ này sẽ chuyển đổi địa chỉ từ định dạng cũ (Tỉnh/TP, Quận/Huyện, Phường/Xã) sang định dạng mới (Thành phố, Phường/Xã).</p>
                        </div>

                        <form method="POST" action="{{ route('address.convert') }}">
                            @csrf
                            <div class="d-grid gap-2 d-md-flex justify-content-md-start animate__animated animate__fadeIn">
                                <button type="submit" class="btn btn-convert" onclick="return confirm('Bạn có chắc chắn muốn chuyển đổi địa chỉ?')">
                                    <i class="fas fa-sync-alt me-2"></i> Bắt đầu chuyển đổi
                                </button>
                            </div>
                        </form>

                        @if(session('total_records') && session('converted') !== null)
                        <div class="progress-container animate__animated animate__fadeIn">
                            <div class="progress-title">
                                <span>Tiến trình chuyển đổi</span>
                                <span>
                                    <span class="success-count">{{ session('success_rate') }}%</span>
                                    ({{ session('converted') }}/{{ session('total_records') }})
                                </span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar"
                                    role="progressbar"
                                    style="width: {{ session('success_rate') }}%"
                                    aria-valuenow="{{ session('converted') }}"
                                    aria-valuemin="0"
                                    aria-valuemax="{{ session('total_records') }}">
                                </div>
                            </div>
                        </div>
                        @endif

                        @if(session('total_failed') > 0)
                        <div class="mt-5 animate__animated animate__fadeIn">
                            <h5 class="mb-3">
                                Danh sách chuyển đổi thất bại
                                <span class="badge bg-danger">{{ session('total_failed') }}</span>
                            </h5>
                            <div class="failed-records">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Phường/Xã cũ</th>
                                            <th>Quận/Huyện</th>
                                            <th>Tỉnh/TP cũ</th>
                                            <th class="reason-col">Lý do</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(session('failed_records') as $record)
                                        <tr class="animate__animated animate__fadeIn">
                                            <td>{{ $record['id'] }}</td>
                                            <td>{{ $record['ward'] }}</td>
                                            <td>{{ $record['district'] }}</td>
                                            <td>{{ $record['province'] }}</td>
                                            <td class="reason-col">{{ $record['reason'] }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer animate__animated animate__fadeIn">
        <div class="container">
            <span>Bản quyền &copy; {{ date('Y') }} thuộc về <strong>Manh-IT-K2</strong>. Mọi quyền được bảo lưu.</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Thêm hiệu ứng khi load trang
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.animate__animated');
            elements.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = 1;
                }, index * 100);
            });
        });
    </script>
</body>

</html>