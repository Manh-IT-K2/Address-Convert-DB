<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AddressConverterController extends Controller
{

    protected $wardMappings = [];

    protected $provinceMergeMap = [
        'Tuyên Quang' => 'Tuyên Quang',
        'Hà Giang' => 'Tuyên Quang',
        'Lào Cai' => 'Lào Cai',
        'Yên Bái' => 'Lào Cai',
        'Thái Nguyên' => 'Thái Nguyên',
        'Bắc Kạn' => 'Thái Nguyên',
        'Phú Thọ' => 'Phú Thọ',
        'Vĩnh Phúc' => 'Phú Thọ',
        'Hoà Bình' => 'Phú Thọ',
        'Bắc Ninh' => 'Bắc Ninh',
        'Bắc Giang' => 'Bắc Ninh',
        'Hưng Yên' => 'Hưng Yên',
        'Thái Bình' => 'Hưng Yên',
        'Hải Dương' => 'Hải Phòng',
        'Hải Phòng' => 'Hải Phòng',
        'Hà Nam' => 'Ninh Bình',
        'Ninh Bình' => 'Ninh Bình',
        'Nam Định' => 'Ninh Bình',
        'Quảng Bình' => 'Quảng Trị',
        'Quảng Trị' => 'Quảng Trị',
        'Quảng Nam' => 'Đà Nẵng',
        'Đà Nẵng' => 'Đà Nẵng',
        'Kon Tum' => 'Quảng Ngãi',
        'Quảng Ngãi' => 'Quảng Ngãi',
        'Gia Lai' => 'Gia Lai',
        'Bình Định' => 'Gia Lai',
        'Khánh Hòa' => 'Khánh Hòa',
        'Ninh Thuận' => 'Khánh Hòa',
        'Lâm Đồng' => 'Lâm Đồng',
        'Đắk Nông' => 'Lâm Đồng',
        'Bình Thuận' => 'Lâm Đồng',
        'Đắk Lắk' => 'Đắk Lắk',
        'Phú Yên' => 'Đắk Lắk',
        'Hồ Chí Minh' => 'Hồ Chí Minh',
        'Bà Rịa - Vũng Tàu' => 'Hồ Chí Minh',
        'Bà Rịa Vũng Tàu' => 'Hồ Chí Minh',
        'Bình Dương' => 'Hồ Chí Minh',
        'Đồng Nai' => 'Đồng Nai',
        'Bình Phước' => 'Đồng Nai',
        'Tây Ninh' => 'Tây Ninh',
        'Long An' => 'Tây Ninh',
        'Cần Thơ' => 'Cần Thơ',
        'Sóc Trăng' => 'Cần Thơ',
        'Hậu Giang' => 'Cần Thơ',
        'Vĩnh Long' => 'Vĩnh Long',
        'Bến Tre' => 'Vĩnh Long',
        'Trà Vinh' => 'Vĩnh Long',
        'Tiền Giang' => 'Đồng Tháp',
        'Đồng Tháp' => 'Đồng Tháp',
        'Cà Mau' => 'Cà Mau',
        'Bạc Liêu' => 'Cà Mau',
        'An Giang' => 'An Giang',
        'Kiên Giang' => 'An Giang',
        'Thừa Thiên Huế' => 'Huế',
        'Nghệ An' => 'Nghệ An',
        'Thành phố Hà Nội' => "Hà Nội",
        "Lai Châu" => "Lai Châu",
        'Điện Biện' => 'Điện Biên',
        'Sơn La' => 'Sơn La',
        'Lạng Sơn' => 'Lạng Sơn',
        'Quảng Ninh' => 'Quảng Ninh',
        'Thanh Hoá' => 'Thanh Hoá',
        'Hà Tĩnh' => 'Hà Tĩnh',
        'Cao Bằng' => 'Cao Bằng'
    ];

    public function showConverter()
    {
        return view('address-converter');
    }
    // Thêm phương thức mới để xử lý file
    // protected function parseSqlFile($content)
    // {
    //     // Tìm tất cả các câu lệnh INSERT vào bảng vtiger_diachicf
    //     preg_match_all('/INSERT INTO `vtiger_diachicf`.*?\((.*?)\)\s*VALUES\s*\((.*?)\);/s', $content, $matches);

    //     $data = [];
    //     for ($i = 0; $i < count($matches[0]); $i++) {
    //         $columns = $this->parseSqlColumns($matches[1][$i]);
    //         $values = $this->parseSqlValues($matches[2][$i]);

    //         // Kết hợp columns và values thành associative array
    //         $rowData = [];
    //         foreach ($columns as $index => $column) {
    //             $rowData[$column] = $values[$index] ?? null;
    //         }

    //         $data[] = [
    //             'table' => 'vtiger_diachicf',
    //             'data' => $rowData
    //         ];
    //     }

    //     return $data;
    // }

    // protected function parseSqlColumns($columnsString)
    // {
    //     // Xử lý chuỗi columns trong SQL
    //     $columns = array_map(function ($col) {
    //         return trim($col, "` \t\n\r\0\x0B");
    //     }, explode(',', $columnsString));

    //     return $columns;
    // }

    // protected function parseSqlValues($valuesString)
    // {
    //     // Xử lý chuỗi values trong SQL phức tạp hơn
    //     $values = [];
    //     $current = '';
    //     $inQuotes = false;
    //     $escapeNext = false;

    //     for ($i = 0; $i < strlen($valuesString); $i++) {
    //         $char = $valuesString[$i];

    //         if ($escapeNext) {
    //             $current .= $char;
    //             $escapeNext = false;
    //             continue;
    //         }

    //         if ($char === '\\') {
    //             $escapeNext = true;
    //             continue;
    //         }

    //         if ($char === "'") {
    //             $inQuotes = !$inQuotes;
    //             continue;
    //         }

    //         if ($char === ',' && !$inQuotes) {
    //             $values[] = $current;
    //             $current = '';
    //             continue;
    //         }

    //         $current .= $char;
    //     }

    //     if ($current !== '') {
    //         $values[] = $current;
    //     }

    //     return $values;
    // }

    // protected function convertAddressData($data)
    // {
    //     $converted = 0;
    //     $failed = 0;
    //     $provinceUpdated = 0;
    //     $convertedData = [];
    //     $failedRecords = [];

    //     foreach ($data as $entry) {
    //         $rowData = $entry['data'];

    //         // Lấy thông tin địa chỉ từ các trường tương ứng
    //         $oldProvince = $rowData['cf_860'] ?? null;
    //         $oldWard = $rowData['cf_864'] ?? null;
    //         $district = $rowData['cf_862'] ?? null;
    //         $diachiid = $rowData['diachiid'] ?? null;

    //         if (!$oldProvince || !$oldWard) {
    //             $failed++;
    //             $failedRecords[] = [
    //                 'id' => $diachiid,
    //                 'ward' => $oldWard,
    //                 'district' => $district,
    //                 'province' => $oldProvince,
    //                 'reason' => 'Thiếu thông tin tỉnh hoặc phường/xã'
    //             ];
    //             continue;
    //         }

    //         $newWard = $this->findNewWard($oldProvince, $oldWard);
    //         $newProvince = $this->provinceMergeMap[$this->normalizeName($oldProvince)] ?? $oldProvince;

    //         $convertedRow = $rowData;
    //         if ($newWard) {
    //             $convertedRow['cf_864'] = $newWard;
    //             $converted++;
    //         } else {
    //             $failed++;
    //             $failedRecords[] = [
    //                 'id' => $diachiid,
    //                 'ward' => $oldWard,
    //                 'district' => $district,
    //                 'province' => $oldProvince,
    //                 'reason' => 'Không tìm thấy phường/xã tương ứng'
    //             ];
    //         }

    //         if ($this->normalizeName($newProvince) !== $this->normalizeName($oldProvince)) {
    //             $convertedRow['cf_860'] = $newProvince;
    //             $provinceUpdated++;
    //         }

    //         $convertedData[] = [
    //             'table' => $entry['table'],
    //             'data' => $convertedRow
    //         ];
    //     }

    //     return [
    //         'converted_data' => $convertedData,
    //         'converted' => $converted,
    //         'failed' => $failed,
    //         'province_updated' => $provinceUpdated,
    //         'failed_records' => $failedRecords
    //     ];
    // }

    // protected function generateOutputSql($convertedData)
    // {
    //     $output = "";

    //     foreach ($convertedData as $entry) {
    //         $columns = array_keys($entry['data']);
    //         $values = array_values($entry['data']);

    //         // Escape các giá trị
    //         $escapedValues = array_map(function ($value) {
    //             if ($value === null) return 'NULL';
    //             return "'" . str_replace("'", "''", $value) . "'";
    //         }, $values);

    //         $columnsStr = '`' . implode('`, `', $columns) . '`';
    //         $valuesStr = implode(', ', $escapedValues);

    //         $output .= "INSERT INTO `{$entry['table']}` ($columnsStr) VALUES ($valuesStr);\n";
    //     }

    //     return $output;
    // }
    // public function processFile(Request $request)
    // {
    //     $request->validate([
    //         'sql_file' => [
    //             'required',
    //             'file',
    //             'mimetypes:text/plain,text/x-sql',
    //             'mimes:sql,txt',
    //             'max:10240' // 10MB
    //         ]
    //     ]);

    //     set_time_limit(1000);
    //     $this->loadWardMappingsFromApi();

    //     try {
    //         // 1. Tạo bảng tạm với cấu trúc giống vtiger_diachicf
    //         $tempTable = 'temp_address_' . uniqid();

    //         DB::statement("CREATE TABLE $tempTable LIKE vtiger_diachicf");

    //         // 2. Import file SQL vào bảng tạm
    //         $filePath = $request->file('sql_file')->getRealPath();
    //         $importCommand = "mysql -u " . env('DB_USERNAME') .
    //             " -p" . env('DB_PASSWORD') .
    //             " " . env('DB_DATABASE') .
    //             " < $filePath";

    //         // Thay thế INSERT INTO vtiger_diachicf thành bảng tạm
    //         $sqlContent = file_get_contents($filePath);
    //         $sqlContent = str_replace(
    //             'INSERT INTO `vtiger_diachicf`',
    //             'INSERT INTO `' . $tempTable . '`',
    //             $sqlContent
    //         );

    //         $tmpFilePath = tempnam(sys_get_temp_dir(), 'sql_');
    //         file_put_contents($tmpFilePath, $sqlContent);

    //         exec("mysql -u " . env('DB_USERNAME') .
    //             " -p" . env('DB_PASSWORD') .
    //             " " . env('DB_DATABASE') .
    //             " < $tmpFilePath", $output, $returnVar);

    //         if ($returnVar !== 0) {
    //             throw new \Exception("Lỗi import SQL vào database");
    //         }

    //         // 3. Xử lý chuyển đổi trong DB
    //         $converted = 0;
    //         $failed = 0;

    //         $records = DB::table($tempTable)->get();
    //         foreach ($records as $record) {
    //             $oldProvince = $record->cf_860;
    //             $oldWard = $record->cf_864;

    //             if (!$oldProvince || !$oldWard) {
    //                 $failed++;
    //                 continue;
    //             }

    //             $newWard = $this->findNewWard($oldProvince, $oldWard);
    //             $newProvince = $this->provinceMergeMap[$this->normalizeName($oldProvince)] ?? $oldProvince;

    //             $updateData = [];
    //             if ($newWard) {
    //                 $updateData['cf_864'] = $newWard;
    //                 $converted++;
    //             }

    //             if ($this->normalizeName($newProvince) !== $this->normalizeName($oldProvince)) {
    //                 $updateData['cf_860'] = $newProvince;
    //             }

    //             if (!empty($updateData)) {
    //                 DB::table($tempTable)
    //                     ->where('diachiid', $record->diachiid)
    //                     ->update($updateData);
    //             }
    //         }

    //         // 4. Export bảng tạm ra file SQL
    //         $exportPath = storage_path('app/converted_' . $tempTable . '.sql');
    //         exec("mysqldump -u " . env('DB_USERNAME') .
    //             " -p" . env('DB_PASSWORD') .
    //             " " . env('DB_DATABASE') .
    //             " $tempTable > $exportPath");

    //         // 5. Xóa bảng tạm
    //         DB::statement("DROP TABLE IF EXISTS $tempTable");

    //         // 6. Trả file cho người dùng
    //         return response()->download($exportPath)
    //             ->deleteFileAfterSend(true);
    //     } catch (\Exception $e) {
    //         Log::error("Process error: " . $e->getMessage());
    //         return back()->with('error', 'Lỗi xử lý: ' . $e->getMessage());
    //     }
    // }


    public function convertAddresses(Request $request)
    {
        set_time_limit(1000);

        $this->loadWardMappingsFromApi();

        $converted = 0;
        $failed = 0;
        $provinceUpdated = 0;
        $failedRecords = []; // Thêm mảng để lưu các bản ghi thất bại
        // Lấy tổng số bản ghi để tính %
        $totalRecords = DB::table('vtiger_diachicf')->count();

        DB::table('vtiger_diachicf')
            ->orderBy('diachiid')
            ->chunk(100, function ($records) use (&$converted, &$failed, &$provinceUpdated, &$failedRecords) {
                foreach ($records as $record) {
                    $oldProvince = $record->cf_860;
                    $oldWard = $record->cf_864;

                    if (!$oldProvince || !$oldWard) {
                        $failed++;
                        $failedRecords[] = [
                            'id' => $record->diachiid,
                            'ward' => $oldWard,
                            'district' => $record->cf_862,
                            'province' => $oldProvince,
                            'reason' => 'Thiếu thông tin tỉnh hoặc phường/xã'
                        ];
                        continue;
                    }

                    $newWard = $this->findNewWard($oldProvince, $oldWard);
                    $newProvince = $this->provinceMergeMap[$this->normalizeName($oldProvince)] ?? $oldProvince;

                    $updateData = [];

                    if ($newWard) {
                        $updateData['cf_864'] = $newWard;
                        $converted++;
                        Log::info("Converted successfully ID {$record->diachiid}: $oldWard => $newWard");
                    } else {
                        $failed++;
                        $failedRecords[] = [
                            'id' => $record->diachiid,
                            'ward' => $oldWard,
                            'district' => $record->cf_862,
                            'province' => $oldProvince,
                            'reason' => 'Không tìm thấy phường/xã tương ứng'
                        ];
                        Log::warning("Convert failed for ID {$record->diachiid}: Ward not found for $oldWard, $record->cf_862, $oldProvince");
                    }

                    if ($this->normalizeName($newProvince) !== $this->normalizeName($oldProvince)) {
                        $updateData['cf_860'] = $newProvince;
                        $provinceUpdated++;
                    }

                    if (!empty($updateData)) {
                        DB::table('vtiger_diachicf')
                            ->where('diachiid', $record->diachiid)
                            ->update($updateData);
                    }
                }
            });

        // Lưu danh sách thất bại vào session (chỉ lấy 1000 bản ghi đầu để tránh quá lớn)
        $request->session()->flash('failed_records', array_slice($failedRecords, 0, 1000));
        $request->session()->flash('total_failed', $failed);
        // Trong controller
        $request->session()->flash('converted', $converted);
        $request->session()->flash('total_records', $totalRecords);
        $successRate = $totalRecords > 0 ? round($converted / $totalRecords * 100, 2) : 0;
        $request->session()->flash('success_rate', $successRate);

        return back()->with('success', "Đã chuyển đổi $converted bản ghi thành công (trong đó cập nhật $provinceUpdated tỉnh/thành phố), $failed bản ghi thất bại.");
    }
    protected function loadWardMappingsFromApi()
    {
        // Đường dẫn đến file JSON
        $jsonFilePath = storage_path('app/address_data.json');

        if (!file_exists($jsonFilePath)) {
            abort(500, 'Không tìm thấy file dữ liệu địa chỉ: ' . $jsonFilePath);
        }

        $jsonContent = file_get_contents($jsonFilePath);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            abort(500, 'Lỗi đọc file JSON: ' . json_last_error_msg());
        }

        if (!isset($data['data']) || !is_array($data['data'])) {
            abort(500, 'Cấu trúc dữ liệu không hợp lệ');
        }

        foreach ($data['data'] as $provinceData) {
            $province = $provinceData['province'] ?? null;
            if (!$province || !isset($provinceData['wards']) || !is_array($provinceData['wards'])) {
                continue;
            }

            $province = trim($province);

            foreach ($provinceData['wards'] as $ward) {
                $newWardName = trim($ward['name']);
                $mergedFromList = $ward['mergedFrom'] ?? [];

                // Chuẩn hóa mergedFromList giống API
                if (is_string($mergedFromList)) {
                    $mergedFromList = [$mergedFromList];
                }

                // Xử lý cả 2 trường hợp "Giữ nguyên" và "Giữ nguyên hiện trạng"
                $isPreserved = in_array('Giữ nguyên', $mergedFromList, true) ||
                    in_array('Giữ nguyên trạng', $mergedFromList, true) ||
                    in_array('Giữ nguyên hiện trạng', $mergedFromList, true);

                if ($isPreserved) {
                    $this->wardMappings[$province][$this->normalizeName($newWardName)] = $newWardName;
                    continue;
                }

                foreach ($mergedFromList as $mergedItem) {
                    // Xử lý chuỗi phức tạp giống API
                    $parts = preg_split('/\s+và\s+/u', $mergedItem);
                    foreach ($parts as $part) {
                        // Xử lý phần trong ngoặc đơn giống API
                        if (preg_match('/^(.*?)(?:\s*\(|$)/u', $part, $matches)) {
                            $part = trim($matches[1]);
                        }

                        $normalized = $this->normalizeName($part);
                        if ($normalized !== '') {
                            // Thêm cả phiên bản gốc và phiên bản đã chuẩn hóa
                            $this->wardMappings[$province][$normalized] = $newWardName;
                            $this->wardMappings[$province][$part] = $newWardName;
                        }
                    }
                }

                // Thêm mapping cho chính tên mới
                $this->wardMappings[$province][$this->normalizeName($newWardName)] = $newWardName;
                $this->wardMappings[$province][$newWardName] = $newWardName;
            }
        }

        $total = 0;
        foreach ($this->wardMappings as $province => $wards) {
            $total += count($wards);
        }
        Log::info("Ward mappings loaded from file: tổng cộng {$total} bản ghi");
    }

    protected function findNewWard($province, $oldWard)
    {
        $normalizedProvince = $this->normalizeName($province);
        $mergedProvince = $this->provinceMergeMap[$normalizedProvince] ?? $normalizedProvince;

        // Danh sách các biến thể tên cần thử
        $wardVariants = [
            $oldWard, // Tên gốc
            $this->normalizeName($oldWard), // Đã bỏ tiền tố
            preg_replace('/^(Xã|Phường|Thị trấn)\s+/ui', '', $oldWard) // Chỉ bỏ một số tiền tố cụ thể
        ];

        // Loại bỏ các giá trị trùng lặp và rỗng
        $wardVariants = array_unique(array_filter($wardVariants));

        foreach ($wardVariants as $wardVariant) {
            $normalizedWard = $this->normalizeName($wardVariant);
            $lowerWard = mb_strtolower($normalizedWard);
            $unsignedWard = $this->removeDiacritics($lowerWard);

            // 1. Tìm kiếm chính xác
            if (isset($this->wardMappings[$mergedProvince][$normalizedWard])) {
                return $this->wardMappings[$mergedProvince][$normalizedWard];
            }

            if (!isset($this->wardMappings[$mergedProvince])) {
                continue;
            }

            // 2. Tìm kiếm lỏng hơn - chỉ cần chứa từ khóa
            foreach ($this->wardMappings[$mergedProvince] as $key => $value) {
                $lowerKey = mb_strtolower($key);
                $unsignedKey = $this->removeDiacritics($lowerKey);

                // Kiểm tra các trường hợp:
                // - Tên cũ là một phần của tên mới (hoặc ngược lại)
                // - Không phân biệt hoa thường
                // - Không phân biệt dấu
                if (
                    $this->isPartialMatch($lowerWard, $lowerKey) ||
                    $this->isPartialMatch($unsignedWard, $unsignedKey)
                ) {
                    return $value;
                }
            }
        }

        return null;
    }

    protected function isPartialMatch($needle, $haystack)
    {
        // Kiểm tra xem needle có xuất hiện trong haystack không
        // hoặc haystack có xuất hiện trong needle không
        return str_contains($haystack, $needle) || str_contains($needle, $haystack);
    }

    protected function removeDiacritics($str)
    {
        $str = preg_replace('/[àáạảãâầấậẩẫăằắặẳẵ]/u', 'a', $str);
        $str = preg_replace('/[èéẹẻẽêềếệểễ]/u', 'e', $str);
        $str = preg_replace('/[ìíịỉĩ]/u', 'i', $str);
        $str = preg_replace('/[òóọỏõôồốộổỗơờớợởỡ]/u', 'o', $str);
        $str = preg_replace('/[ùúụủũưừứựửữ]/u', 'u', $str);
        $str = preg_replace('/[ỳýỵỷỹ]/u', 'y', $str);
        $str = preg_replace('/đ/u', 'd', $str);
        $str = preg_replace('/[ÀÁẠẢÃÂẦẤẬẨẪĂẰẮẶẲẴ]/u', 'A', $str);
        $str = preg_replace('/[ÈÉẸẺẼÊỀẾỆỂỄ]/u', 'E', $str);
        $str = preg_replace('/[ÌÍỊỈĨ]/u', 'I', $str);
        $str = preg_replace('/[ÒÓỌỎÕÔỒỐỘỔỖƠỜỚỢỞỠ]/u', 'O', $str);
        $str = preg_replace('/[ÙÚỤỦŨƯỪỨỰỬỮ]/u', 'U', $str);
        $str = preg_replace('/[ỲÝỴỶỸ]/u', 'Y', $str);
        $str = preg_replace('/Đ/u', 'D', $str);
        return $str;
    }

    protected function normalizeName($text)
    {
        $text = trim($text);
        $text = preg_replace('/^(Thành phố|Tỉnh|Quận|Huyện|Phường|Xã|Thị xã|Thị trấn)\s+/u', '', $text);
        return $text;
    }

    // protected function loadWardMappingsFromApi()
    // {
    //     $response = Http::get('https://vietnamlabs.com/api/vietnamprovince');

    //     if (!$response->successful()) {
    //         abort(500, 'Không thể tải dữ liệu từ API');
    //     }

    //     $data = $response->json('data');

    //     foreach ($data as $provinceData) {
    //         $province = $provinceData['province'] ?? null;
    //         if (!$province || !isset($provinceData['wards']) || !is_array($provinceData['wards'])) {
    //             continue;
    //         }

    //         $province = trim($province);

    //         foreach ($provinceData['wards'] as $ward) {
    //             $newWardName = trim($ward['name']);
    //             $mergedFromList = $ward['mergedFrom'] ?? [];

    //             // Nếu là chuỗi, ép thành mảng 1 phần tử
    //             if (is_string($mergedFromList)) {
    //                 $mergedFromList = [$mergedFromList];
    //             }

    //             // Trường hợp "Giữ nguyên" thì ánh xạ chính nó
    //             if (is_array($mergedFromList) && in_array('Giữ nguyên hiện trạng', $mergedFromList, true)) {
    //                 $this->wardMappings[$province][$this->normalizeName($newWardName)] = $newWardName;
    //                 continue;
    //             }

    //             foreach ($mergedFromList as $mergedItem) {
    //                 // Xử lý các trường hợp phức tạp như "xã Bình Hưng (huyện Bình Chánh) và phần còn lại của phường 7 (quận 8)"
    //                 $parts = preg_split('/\s+và\s+/u', $mergedItem);
    //                 foreach ($parts as $part) {
    //                     // Lọc ra tên chính từ phần phức tạp (ví dụ: "xã Bình Hưng (huyện Bình Chánh)" => "xã Bình Hưng")
    //                     if (preg_match('/^(.*?)\s*\(/u', $part, $matches)) {
    //                         $part = trim($matches[1]);
    //                     }

    //                     $normalized = $this->normalizeName($part);
    //                     if ($normalized !== '') {
    //                         $this->wardMappings[$province][$normalized] = $newWardName;
    //                     }
    //                 }
    //             }

    //             // Thêm mapping cho chính tên mới (để tìm kiếm không phân biệt hoa thường)
    //             $this->wardMappings[$province][$this->normalizeName($newWardName)] = $newWardName;
    //         }
    //     }

    //     // Log tổng số ward mappings để kiểm tra nhanh
    //     $total = 0;
    //     foreach ($this->wardMappings as $province => $wards) {
    //         $total += count($wards);
    //     }
    //     Log::info("Ward mappings loaded: tổng cộng {$total} bản ghi");
    // }
}
