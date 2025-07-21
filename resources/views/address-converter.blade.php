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

        /* Thêm vào phần style */
        .btn-convert-container {
            display: flex;
            justify-content: center;
            margin: 20px 0;
            position: relative;
        }

        .btn-convert {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white !important;
            background-color: var(--primary-color);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.35);
            min-width: 220px;
            position: relative;
        }

        .btn-convert i {
            color: white !important;
            margin-right: 8px;
        }

        .btn-convert:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(78, 115, 223, 0.4);
            background-color: #3a5bd9;
            color: white !important;
        }

        .btn-convert:active {
            transform: translateY(1px);
        }

        /* Overlay loading */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .loading-overlay.active {
            opacity: 1;
            pointer-events: all;
            display: flex;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
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

        /* Thêm vào phần style */
        .form-file-text {
            font-size: 0.875rem;
            color: #6c757d;
        }

        #sqlFile {
            border: 2px dashed #dee2e6;
            padding: 20px;
            transition: all 0.3s;
        }

        #sqlFile:hover {
            border-color: var(--primary-color);
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
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

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
                        <div class="alert alert-primary animate__animated animate__fadeIn" role="alert">
                            <h5 class="fw-bold mb-3"><i class="fas fa-info-circle me-2"></i>Hướng dẫn sử dụng</h5>
                            <ul class="mb-0">
                                <li><strong>1. Tải mã nguồn:</strong>
                                    <div class="d-flex align-items-center">
                                        <code class="flex-grow-1">git clone https://github.com/Manh-IT-K2/Address-Convert-DB.git</code>
                                        <button class="btn btn-sm btn-outline-secondary ms-2 copy-btn" data-clipboard-text="git clone https://github.com/Manh-IT-K2/Address-Convert-DB.git">
                                            <i class="far fa-copy"></i>
                                        </button>
                                    </div>
                                </li>
                                <li><strong>2. Cài đặt & cấu hình:</strong>
                                    <ul>
                                        <li>
                                            <div class="d-flex align-items-center">
                                                <code class="flex-grow-1">composer install</code>
                                                <button class="btn btn-sm btn-outline-secondary ms-2 copy-btn" data-clipboard-text="composer install">
                                                    <i class="far fa-copy"></i>
                                                </button>
                                            </div>
                                        </li>
                                        <li>Sửa file <code>.env</code> để khai báo DB</li>
                                        <li>
                                            <div class="d-flex align-items-center">
                                                <code class="flex-grow-1">php artisan key:generate</code>
                                                <button class="btn btn-sm btn-outline-secondary ms-2 copy-btn" data-clipboard-text="php artisan key:generate">
                                                    <i class="far fa-copy"></i>
                                                </button>
                                            </div>
                                        </li>
                                    </ul>
                                </li>
                                <li><strong>3. Chuẩn bị bảng dữ liệu:</strong> <code>vtiger_diachicf</code> gồm:
                                    <ul>
                                        <li><code>diachiid</code> - khóa chính (auto increment)</li>
                                        <li><code>cf_860</code> - Tỉnh/Thành phố</li>
                                        <li><code>cf_862</code> - Quận/Huyện</li>
                                        <li><code>cf_864</code> - Phường/Xã</li>
                                    </ul>
                                </li>
                                <li><strong>4. Chạy ứng dụng:</strong>
                                    <div class="d-flex align-items-center">
                                        <code class="flex-grow-1">php artisan serve</code>
                                        <button class="btn btn-sm btn-outline-secondary ms-2 copy-btn" data-clipboard-text="php artisan serve">
                                            <i class="far fa-copy"></i>
                                        </button>
                                    </div>
                                    → truy cập <code>localhost:8000</code>
                                </li>
                                <li><strong>5. Nhấn nút "Bắt đầu chuyển đổi"</strong> để cập nhật địa chỉ mới</li>
                                <li><strong>6. Lưu ý:</strong>
                                    <ul>
                                        <li>Dữ liệu địa chỉ cũ phải chính xác, đúng chính tả, không viết tắt</li>
                                        <li>Backup dữ liệu trước khi sử dụng để đảm bảo an toàn</li>
                                    </ul>
                                </li>
                            </ul>
                        </div>

                        <form method="POST" action="{{ route('address.convert') }}" id="converterForm">
                            @csrf
                            <div class="btn-convert-container animate__animated animate__fadeIn">
                                <button type="submit" class="btn btn-convert" id="convertBtn">
                                    <span class="btn-text">
                                        <i class="fas fa-sync-alt me-2"></i> Bắt đầu chuyển đổi
                                    </span>
                                </button>
                            </div>
                        </form>
                        <!-- Thêm vào card-body, sau phần form hiện tại -->
                        <!-- <div class="mt-5 animate__animated animate__fadeIn">
                            <h5 class="mb-3">Hoặc chuyển đổi từ file SQL</h5>
                            <form method="POST" action="{{ route('address.convert.file') }}" enctype="multipart/form-data" id="fileConverterForm">
                                @csrf
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="sqlFile" class="form-label">Chọn file SQL cần chuyển đổi</label>
                                            <input class="form-control" type="file" id="sqlFile" name="sql_file" accept=".sql,.txt" required>
                                            <div class="form-text">File SQL chứa dữ liệu địa chỉ cần chuyển đổi (tối đa 10MB)</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary" id="convertFileBtn">
                                            <i class="fas fa-file-import me-2"></i> Chuyển đổi từ file
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div> -->
                        @if(session('conversion_results'))
                        <div class="alert alert-info mt-4">
                            <h5>Kết quả chuyển đổi từ file:</h5>
                            <ul>
                                <li>Chuyển đổi thành công: {{ session('conversion_results.converted') }} bản ghi</li>
                                <li>Cập nhật tỉnh/thành phố: {{ session('conversion_results.province_updated') }} bản ghi</li>
                                <li>Thất bại: {{ session('conversion_results.failed') }} bản ghi</li>
                            </ul>
                        </div>
                        @endif

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
            <span>Bản quyền &copy; {{ date('Y') }} thuộc về <strong>MTAC</strong> phát triển bởi <strong>Manh-IT-K2</strong>.</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.animate__animated');
            elements.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = 1;
                }, index * 100);
            });

            const form = document.getElementById('converterForm');
            const convertBtn = document.getElementById('convertBtn');
            const loadingOverlay = document.getElementById('loadingOverlay');

            form.addEventListener('submit', function(e) {
                // Ngăn submit mặc định
                e.preventDefault();

                // Hiển thị loading ngay lập tức
                loadingOverlay.classList.add('active');
                loadingOverlay.style.display = 'flex';
                loadingOverlay.style.opacity = '1';

                // Vô hiệu hóa nút
                convertBtn.disabled = true;

                // Thêm độ trễ nhỏ để đảm bảo UI cập nhật
                setTimeout(() => {
                    form.submit();
                }, 100);
            });
        });
        // Thêm vào phần script
        const fileConverterForm = document.getElementById('fileConverterForm');
        const convertFileBtn = document.getElementById('convertFileBtn');

        fileConverterForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Hiển thị loading
            loadingOverlay.style.display = 'flex';
            convertFileBtn.disabled = true;

            // Submit form
            setTimeout(() => {
                fileConverterForm.submit();
            }, 100);
        });
    </script>
    <!-- Thêm thư viện clipboard.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
    <script>
        // Khởi tạo clipboard.js
        new ClipboardJS('.copy-btn');

        // Hiển thị tooltip khi copy
        document.querySelectorAll('.copy-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const originalTitle = this.getAttribute('data-original-title') || '';
                this.setAttribute('data-original-title', 'Đã copy!');
                $(this).tooltip('show');

                setTimeout(() => {
                    this.setAttribute('data-original-title', originalTitle);
                    $(this).tooltip('hide');
                }, 1000);
            });

            // Khởi tạo tooltip (nếu dùng Bootstrap)
            $(btn).tooltip({
                trigger: 'manual',
                placement: 'top'
            });
        });
    </script>
</body>

</html>