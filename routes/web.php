<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\AddressConverterController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/address-converter', [AddressConverterController::class, 'showConverter'])->name('address.converter');
Route::post('/address-convert', [AddressConverterController::class, 'convertAddresses'])->name('address.convert');

Route::get('/test-convert', function () {
    return view('test-convert');
});

Route::post('/test-convert', [AddressConverterController::class, 'handleTestConvert'])->name('test.convert.address');

Route::post('/address/convert/file', [AddressConverterController::class, 'processFile'])
    ->name('address.convert.file');


Route::get('/update-address-data', function () {
    // Đường dẫn đến file JSON
    $jsonFilePath = storage_path('app/address_data.json');
    
    try {
        // Đọc nội dung file JSON
        $jsonContent = file_get_contents($jsonFilePath);
        $data = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Lỗi đọc file JSON: " . json_last_error_msg());
        }
        
        // Kiểm tra cấu trúc dữ liệu
        if (!isset($data['data']) || !is_array($data['data'])) {
            throw new Exception("Cấu trúc dữ liệu không hợp lệ: thiếu mảng 'data'");
        }
        
        // Xử lý thêm trường district và chuẩn hóa tên phường
        foreach ($data['data'] as &$province) {
            if (!isset($province['wards']) || !is_array($province['wards'])) {
                continue;
            }
            
            foreach ($province['wards'] as &$ward) {
                $districts = [];
                
                if (!isset($ward['mergedFrom']) || !is_array($ward['mergedFrom'])) {
                    $ward['district'] = ['Không xác định'];
                    continue;
                }
                
                // Chuẩn hóa tên phường trong mergedFrom
                foreach ($ward['mergedFrom'] as &$item) {
                    // Trường hợp có quận huyện trong ngoặc đơn (vd: "7 (quận Tân Bình)")
                    if (preg_match('/^([^(]+)\s*\((quận|huyện)\s[^)]+\)$/i', $item, $matches)) {
                        $phuongPart = trim($matches[1]);
                        $huyenPart = preg_replace('/^([^(]+)\s*/', '', $item); // Giữ lại phần trong ngoặc
                        
                        // Xử lý phần phường
                        $phuongPart = normalizePhuongName($phuongPart);
                        
                        $item = $phuongPart . ' ' . $huyenPart;
                    } 
                    // Các trường hợp khác
                    else {
                        $item = normalizePhuongName($item);
                    }
                }
                unset($item);
                
                // Xử lý trường district
                foreach ($ward['mergedFrom'] as $item) {
                    if (preg_match_all('/\((quận|huyện)\s([^)]+)\)/i', $item, $matches, PREG_SET_ORDER)) {
                        foreach ($matches as $match) {
                            $districtType = ucfirst($match[1]);
                            $districtName = ucwords(strtolower($match[2]));
                            $districtFull = $districtType . ' ' . $districtName;
                            
                            if (!in_array($districtFull, $districts)) {
                                $districts[] = $districtFull;
                            }
                        }
                    }
                }
                
                $ward['district'] = !empty($districts) ? $districts : ['Không xác định'];
            }
        }
        
        // Lưu lại file JSON
        $updatedJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($jsonFilePath, $updatedJson);
        
        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công!',
            'file_path' => $jsonFilePath,
            'updated_data_sample' => $data['data'][0]['wards'][0] ?? null
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage(),
            'trace' => env('APP_DEBUG') ? $e->getTrace() : null
        ], 500);
    }
});

// Hàm helper để chuẩn hóa tên phường
function normalizePhuongName($name) {
    // Nếu là số đơn thuần (vd: "7")
    if (preg_match('/^(\d+)$/', $name, $matches)) {
        $number = intval($matches[1]);
        return 'Phường ' . str_pad($number, 2, '0', STR_PAD_LEFT);
    }
    // Nếu có dạng "Phường X" hoặc "Phường XX" nhưng số < 10 (vd: "Phường 6")
    elseif (preg_match('/^Phường\s+(\d+)$/i', $name, $matches)) {
        $number = intval($matches[1]);
        if ($number < 10) {
            return 'Phường ' . str_pad($number, 2, '0', STR_PAD_LEFT);
        }
        return $name; // Giữ nguyên nếu số >= 10
    }
    // Nếu có dạng "3" hoặc "Phường 3" kèm theo phần khác (đã xử lý ở trên)
    return $name;
}