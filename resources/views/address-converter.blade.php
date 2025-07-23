<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Address Converter Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #43aa8b;
            --light: #f8f9fa;
            --dark: #212529;
            --muted: #6c757d;
            --gradient: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }

        body {
            background-color: #f5f7ff;
            font-family: 'Nunito', sans-serif;
            color: var(--dark);
            line-height: 1.6;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(67, 97, 238, 0.15);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: var(--gradient);
            color: white;
            padding: 1.5rem;
            border-bottom: none;
        }

        .card-header h5 {
            font-weight: 700;
            margin-bottom: 0;
        }

        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }

        .form-control::placeholder {
            color: var(--muted);
            opacity: 0.6;
        }

        .btn-convert {
            background: var(--gradient);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
            transition: all 0.4s;
            position: relative;
            overflow: hidden;
        }

        .btn-convert:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(67, 97, 238, 0.4);
        }

        .btn-convert:active {
            transform: translateY(1px);
        }

        .btn-convert::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }

        .btn-convert:hover::before {
            left: 100%;
        }

        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .alert-primary {
            background-color: rgba(67, 97, 238, 0.1);
            border-left: 4px solid var(--primary);
        }

        .alert-success {
            background-color: var(--success);
            border-left: 4px solid var(--primary);
        }

        .alert-danger {
            background-color: rgba(247, 37, 133, 0.1);
            border-left: 4px solid var(--danger);
        }

        .progress {
            height: 12px;
            border-radius: 6px;
            background-color: #e9ecef;
        }

        .progress-bar {
            border-radius: 6px;
            background: var(--gradient);
            transition: width 1s ease;
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead th {
            background-color: var(--primary);
            color: white;
            border: none;
        }

        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .code-block {
            position: relative;
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary);
            background-color: #f5f7ff;
        }

        .copy-btn {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            opacity: 0.7;
            transition: all 0.2s;
            border-radius: 6px;
            padding: 3px 8px;
        }

        .copy-btn:hover {
            opacity: 1;
            background-color: rgba(67, 97, 238, 0.1);
        }

        .badge {
            padding: 5px 10px;
            border-radius: 50px;
            font-weight: 600;
        }

        footer {
            color: var(--muted);
            font-size: 0.9rem;
        }

        footer strong {
            color: var(--primary);
            font-weight: 700;
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade {
            animation: fadeIn 0.6s ease forwards;
        }

        /* Floating elements */
        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .card-header {
                padding: 1rem;
            }

            .btn-convert {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card animate-fade">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-map-marked-alt me-2"></i> Công cụ chuyển đổi địa chỉ</h5>
                    </div>

                    <div class="card-body">
                        @if(session('success'))
                        <div class="alert alert-success animate-fade">
                            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                        </div>
                        @endif

                        <p class="mb-4 text-muted">Công cụ này sẽ chuyển đổi địa chỉ từ định dạng cũ (Tỉnh/TP, Quận/Huyện, Phường/Xã) sang định dạng mới (Thành phố, Phường/Xã).</p>

                        <div class="alert alert-primary mb-4 animate-fade">
                            <h6 class="fw-bold mb-3"><i class="fas fa-info-circle me-2"></i> Hướng dẫn sử dụng</h6>
                            <ol class="mb-0 ps-3">
                                <li class="mb-3">
                                    <strong>Tải mã nguồn:</strong>
                                    <div class="code-block mt-2">
                                        <code>git clone https://github.com/Manh-IT-K2/Address-Convert-DB.git</code>
                                        <button class="copy-btn btn btn-sm btn-outline-primary" onclick="copyToClipboard(this)"><i class="far fa-copy"></i></button>
                                    </div>
                                </li>
                                <li class="mb-3">
                                    <strong>Cài đặt & cấu hình:</strong>
                                    <div class="code-block mt-2">
                                        <code>composer install<br>php artisan key:generate</code>
                                        <button class="copy-btn btn btn-sm btn-outline-primary" onclick="copyToClipboard(this)"><i class="far fa-copy"></i></button>
                                    </div>
                                </li>
                                <li class="mb-3">
                                    <strong>Chuẩn bị bảng dữ liệu gồm:</strong>
                                    <div class="mt-2 ps-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <span>Khóa chính (auto increment)</span>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <span>Tỉnh/Thành phố</span>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <span>Quận/Huyện</span>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <span>Phường/Xã</span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <span><code>convert_status</code> và <code>convert_error</code> - 2 trường được thêm tự động (nếu lỗi hãy tạo thủ công)</span>
                                        </div>
                                    </div>
                                </li>
                                <li class="mb-2">
                                    <strong>Chạy ứng dụng:</strong>
                                    <div class="code-block mt-2">
                                        <code>php artisan serve</code>
                                        <button class="copy-btn btn btn-sm btn-outline-primary" onclick="copyToClipboard(this)"><i class="far fa-copy"></i></button>
                                    </div>
                                </li>
                            </ol>
                        </div>

                        @if($errors->has('database_error'))
                        <div class="alert alert-danger animate-fade">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ $errors->first('database_error') }}
                        </div>
                        @endif

                        <form method="POST" action="{{ route('address.convert') }}" id="converterForm">
                            @csrf
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="table_name" class="form-label fw-bold">Tên bảng</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-table text-primary"></i></span>
                                        <input type="text" class="form-control" id="table_name" name="table_name"
                                            placeholder="vd: vtiger_diachicf" value="{{ old('table_name', session('table_config.table_name', '')) }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="id_field" class="form-label fw-bold">Trường ID</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-key text-primary"></i></span>
                                        <input type="text" class="form-control" id="id_field" name="id_field"
                                            placeholder="vd: diachiid" value="{{ old('id_field', session('table_config.id_field', '')) }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="province_field" class="form-label fw-bold">Trường Tỉnh/Thành phố</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-city text-primary"></i></span>
                                        <input type="text" class="form-control" id="province_field" name="province_field"
                                            placeholder="vd: cf_860" value="{{ old('province_field', session('table_config.province_field', '')) }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="district_field" class="form-label fw-bold">Trường Quận/Huyện</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-map-marked-alt text-primary"></i></span>
                                        <input type="text" class="form-control" id="district_field" name="district_field"
                                            placeholder="vd: cf_862" value="{{ old('district_field', session('table_config.district_field', '')) }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="ward_field" class="form-label fw-bold">Trường Phường/Xã</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-map-marker-alt text-primary"></i></span>
                                        <input type="text" class="form-control" id="ward_field" name="ward_field"
                                            placeholder="vd: cf_864" value="{{ old('ward_field', session('table_config.ward_field', '')) }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center my-4">
                                <button type="submit" class="btn btn-convert text-white px-4 py-3" id="convertBtn">
                                    <i class="fas fa-sync-alt me-2"></i> Bắt đầu chuyển đổi
                                </button>
                            </div>
                        </form>

                        @if(session('total_records') && session('converted') !== null)
                        <div class="mb-4 animate-fade">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fw-bold">Tiến trình chuyển đổi</span>
                                <span>
                                    <span class="text-primary fw-bold">{{ session('success_rate') }}%</span>
                                    ({{ session('converted') }}/{{ session('total_records') }})
                                </span>
                            </div>
                            <div class="progress" style="height: 12px;">
                                <div class="progress-bar" style="width: {{ session('success_rate') }}%"></div>
                            </div>
                        </div>
                        @endif

                        @if(session('total_failed') > 0)
                        <div class="mt-4 animate-fade">
                            <h6 class="mb-3 fw-bold">
                                <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                                Danh sách chuyển đổi thất bại
                                <span class="badge bg-danger">{{ session('total_failed') }}</span>
                            </h6>
                            <div class="table-responsive" style="max-height: 500px;">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 80px;">ID</th> <!-- Fixed width for ID column -->
                                            <th style="width: 25%; min-width: 150px;">Phường/Xã cũ</th>
                                            <th style="width: 25%; min-width: 150px;">Quận/Huyện</th>
                                            <th style="width: 25%; min-width: 150px;">Tỉnh/TP cũ</th>
                                            <th style="width: 25%; min-width: 200px;">Lý do</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(session('failed_records') as $record)
                                        <tr>
                                            <td><span class="badge bg-secondary">{{ $record['id'] }}</span></td>
                                            <td>{{ $record['ward'] }}</td>
                                            <td>{{ $record['district'] }}</td>
                                            <td>{{ $record['province'] }}</td>
                                            <td><span class="badge bg-danger">{{ $record['reason'] }}</span></td>
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

    <footer class="text-center py-4 mt-4">
        <small>Bản quyền &copy; {{ date('Y') }} thuộc về <strong>MTAC</strong> phát triển bởi <strong>Manh-IT-K2</strong></small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(button) {
            const codeBlock = button.parentElement;
            const code = codeBlock.querySelector('code').textContent;
            navigator.clipboard.writeText(code.trim());

            const originalIcon = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-success');

            setTimeout(() => {
                button.innerHTML = originalIcon;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-primary');
            }, 2000);
        }

        // Add animation to elements when they come into view
        document.addEventListener('DOMContentLoaded', function() {
            const animateElements = document.querySelectorAll('.animate-fade');

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = 1;
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, {
                threshold: 0.1
            });

            animateElements.forEach(el => {
                el.style.opacity = 0;
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(el);
            });
        });
    </script>
</body>

</html>