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


// Route::get('/update-address-data', function () {
//     // Đường dẫn đến file JSON
//     $jsonFilePath = storage_path('app/address_data.json');

//     try {
//         // Đọc nội dung file JSON
//         $jsonContent = file_get_contents($jsonFilePath);
//         $data = json_decode($jsonContent, true);

//         if (json_last_error() !== JSON_ERROR_NONE) {
//             throw new Exception("Lỗi đọc file JSON: " . json_last_error_msg());
//         }

//         // Kiểm tra cấu trúc dữ liệu
//         if (!isset($data['data']) || !is_array($data['data'])) {
//             throw new Exception("Cấu trúc dữ liệu không hợp lệ: thiếu mảng 'data'");
//         }

//         // Xử lý thêm trường district và chuẩn hóa tên phường
//         foreach ($data['data'] as &$province) {
//             if (!isset($province['wards']) || !is_array($province['wards'])) {
//                 continue;
//             }

//             foreach ($province['wards'] as &$ward) {
//                 $districts = [];

//                 if (!isset($ward['mergedFrom']) || !is_array($ward['mergedFrom'])) {
//                     $ward['district'] = ['Không xác định'];
//                     continue;
//                 }

//                 // Xử lý tách các phần tử có chứa " và " trong mergedFrom
//                 $newMergedFrom = [];
//                 foreach ($ward['mergedFrom'] as $item) {
//                     // Tách các phần nếu có chứa " và " với điều kiện phía sau có "phần" hoặc "một phần" hoặc "còn lại"
//                     if (preg_match('/^(.*?)\s+và\s+(một phần|còn lại|phần)(?:\s+.*)?$/iu', $item, $matches)) {
//                         $partBeforeAnd = trim($matches[1]);
//                         $keyword = $matches[2]; // Giữ lại từ khóa "một phần", "phần", "còn lại"
//                         $rest = trim(str_replace($matches[1] . ' và ' . $matches[2], '', $item));
                        
//                         // Thêm phần trước "và" vào mảng mới
//                         $newMergedFrom[] = $partBeforeAnd;
//                         // Thêm phần sau "và" vào mảng mới (bao gồm cả keyword)
//                         $newMergedFrom[] = $keyword . ' ' . $rest;
//                     } else {
//                         $newMergedFrom[] = $item;
//                     }
//                 }
//                 $ward['mergedFrom'] = $newMergedFrom;

//                 // Chuẩn hóa tên phường trong mergedFrom
//                 foreach ($ward['mergedFrom'] as &$item) {
//                     // Trường hợp có quận/huyện/thành phố trong ngoặc đơn
//                     if (preg_match('/^([^(]+)\s*\((quận|huyện|tp|thành phố)\s[^)]+\)$/i', $item, $matches)) {
//                         $phuongPart = trim($matches[1]);
//                         $huyenPart = preg_replace('/^([^(]+)\s*/', '', $item); // Giữ lại phần trong ngoặc

//                         // Xử lý phần phường
//                         $phuongPart = normalizePhuongName($phuongPart);

//                         $item = $phuongPart . ' ' . $huyenPart;
//                     }
//                     // Các trường hợp khác
//                     else {
//                         $item = normalizePhuongName($item);
//                     }
//                 }
//                 unset($item);

//                 // Xử lý trường district
//                 foreach ($ward['mergedFrom'] as $item) {
//                     // Tìm quận/huyện/thành phố trong ngoặc
//                     if (preg_match_all('/\((quận|huyện|tp|thành phố)\s([^)]+)\)/i', $item, $matches, PREG_SET_ORDER)) {
//                         foreach ($matches as $match) {
//                             $districtType = ucfirst($match[1]);
//                             // Xử lý viết tắt "tp" thành "Thành phố"
//                             if (strtolower($districtType) === 'tp') {
//                                 $districtType = 'Thành phố';
//                             }
//                             $districtName = ucwords(strtolower(trim($match[2])));
//                             $districtFull = $districtType . ' ' . $districtName;

//                             if (!in_array($districtFull, $districts)) {
//                                 $districts[] = $districtFull;
//                             }
//                         }
//                     }

//                     // Thêm xử lý cho trường hợp "TP Thủ Đức" (không có ngoặc)
//                     if (preg_match('/\b(TP|Thành phố)\s([^,\s)]+)(?:\s([^,\s)]+))?/i', $item, $tpMatches)) {
//                         $tpName = 'Thành phố ' . ucwords(strtolower(trim($tpMatches[2])));
//                         // Nếu có phần thứ 3 (như "Thủ Đức") thì nối vào
//                         if (isset($tpMatches[3])) {
//                             $tpName .= ' ' . ucwords(strtolower(trim($tpMatches[3])));
//                         }

//                         if (!in_array($tpName, $districts)) {
//                             $districts[] = $tpName;
//                         }
//                     }
//                 }

//                 $ward['district'] = !empty($districts) ? $districts : ['Không xác định'];
//             }
//         }

//         // Lưu lại file JSON
//         $updatedJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
//         file_put_contents($jsonFilePath, $updatedJson);

//         return response()->json([
//             'success' => true,
//             'message' => 'Cập nhật thành công!',
//             'file_path' => $jsonFilePath,
//             'updated_data_sample' => $data['data'][0]['wards'][0] ?? null
//         ]);
//     } catch (Exception $e) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Lỗi: ' . $e->getMessage(),
//             'trace' => env('APP_DEBUG') ? $e->getTrace() : null
//         ], 500);
//     }
// });

// // Hàm helper để chuẩn hóa tên phường (giữ nguyên như cũ)
// function normalizePhuongName($name)
// {
//     // Nếu là số đơn thuần (vd: "7")
//     if (preg_match('/^(\d+)$/', $name, $matches)) {
//         $number = intval($matches[1]);
//         return 'Phường ' . str_pad($number, 2, '0', STR_PAD_LEFT);
//     }
//     // Nếu có dạng "Phường X" hoặc "Phường XX" nhưng số < 10 (vd: "Phường 6")
//     elseif (preg_match('/^Phường\s+(\d+)$/i', $name, $matches)) {
//         $number = intval($matches[1]);
//         if ($number < 10) {
//             return 'Phường ' . str_pad($number, 2, '0', STR_PAD_LEFT);
//         }
//         return $name; // Giữ nguyên nếu số >= 10
//     }
//     // Nếu có dạng "3" hoặc "Phường 3" kèm theo phần khác (đã xử lý ở trên)
//     return $name;
// }