<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
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

    public function convertAddresses(Request $request)
    {
        set_time_limit(1000);

        $this->loadWardMappingsFromApi();

        // Lấy thông tin cấu hình từ form
        $tableName = $request->input('table_name', 'vtiger_diachicf');
        $idField = $request->input('id_field', 'diachiid');
        $provinceField = $request->input('province_field', 'cf_860');
        $districtField = $request->input('district_field', 'cf_862');
        $wardField = $request->input('ward_field', 'cf_864');

        // Kiểm tra bảng và các trường có tồn tại không
        try {
            $this->checkAndAddColumns($tableName);
        } catch (\Exception $e) {
            return back()->withErrors([
                'database_error' => $e->getMessage()
            ])->withInput();
        }

        $converted = 0;
        $failed = 0;
        $provinceUpdated = 0;
        $failedRecords = [];

        // Lấy tổng số bản ghi để tính %
        $totalRecords = DB::table($tableName)->count();

        DB::table($tableName)
            ->orderBy($idField)
            ->chunk(100, function ($records) use (
                &$converted,
                &$failed,
                &$provinceUpdated,
                &$failedRecords,
                $tableName,
                $idField,
                $provinceField,
                $districtField,
                $wardField,
            ) {
                foreach ($records as $record) {
                    $recordId = $record->{$idField};
                    $oldProvince = $record->{$provinceField};
                    $oldWard = $record->{$wardField};
                    $district = $record->{$districtField};

                    // Khởi tạo giá trị mặc định
                    $status = 'success';
                    $errorMessage = null;

                    if (!$oldProvince || !$oldWard || !$district) {
                        $status = 'failed';

                        if (!$oldProvince && !$oldWard && !$district) {
                            $errorMessage = 'Thiếu thông tin tỉnh, quận/huyện và phường/xã';
                        } elseif (!$oldProvince && !$district) {
                            $errorMessage = 'Thiếu thông tin tỉnh và quận/huyện';
                        } elseif (!$oldProvince && !$oldWard) {
                            $errorMessage = 'Thiếu thông tin tỉnh và phường/xã';
                        } elseif (!$district && !$oldWard) {
                            $errorMessage = 'Thiếu thông tin quận/huyện và phường/xã';
                        } elseif (!$oldProvince) {
                            $errorMessage = 'Thiếu thông tin tỉnh/thành phố';
                        } elseif (!$district) {
                            $errorMessage = 'Thiếu thông tin quận/huyện';
                        } else {
                            $errorMessage = 'Thiếu thông tin phường/xã';
                        }

                        $failed++;
                        $failedRecords[] = [
                            'id' => $recordId,
                            'ward' => $oldWard,
                            'district' => $district,
                            'province' => $oldProvince,
                            'reason' => $errorMessage
                        ];

                        // Cập nhật trạng thái và lỗi vào DB
                        $this->updateRecordStatus($tableName, $idField, $recordId, $status, $errorMessage);
                        continue;
                    }

                    $newWard = $this->findNewWard($oldProvince, $oldWard);
                    $newProvince = $this->provinceMergeMap[$this->normalizeName($oldProvince)] ?? $oldProvince;

                    // Chuẩn hóa tên tỉnh/thành phố
                    $newProvince = $this->normalizeProvinceName($newProvince);
                    $oldProvinceNormalized = $this->normalizeProvinceName($oldProvince);

                    $updateData = [];

                    if ($newWard) {
                        $updateData[$wardField] = $newWard;
                        $converted++;
                        Log::info("Converted successfully ID {$recordId}: $oldWard => $newWard");
                    } else {
                        $status = 'failed';
                        $errorMessage = $this->getDetailedErrorMessage($oldProvince, $district, $oldWard);

                        $failed++;
                        $failedRecords[] = [
                            'id' => $recordId,
                            'ward' => $oldWard,
                            'district' => $district,
                            'province' => $oldProvince,
                            'reason' => $errorMessage
                        ];
                        Log::warning("Convert failed for ID {$recordId}: $errorMessage");
                    }

                    if ($newProvince !== $oldProvinceNormalized) {
                        $updateData[$provinceField] = $newProvince;
                        $provinceUpdated++;
                    }

                    // Luôn cập nhật trạng thái và thông báo lỗi
                    $updateData['convert_status'] = $status;
                    $updateData['convert_error'] = $errorMessage ?? '';

                    DB::table($tableName)
                        ->where($idField, $recordId)
                        ->update($updateData);
                }
            });

        // Lưu danh sách thất bại vào session
        $request->session()->flash('failed_records', array_slice($failedRecords, 0, 1000));
        $request->session()->flash('total_failed', $failed);
        $request->session()->flash('converted', $converted);
        $request->session()->flash('total_records', $totalRecords);
        $request->session()->flash('success_rate', $totalRecords > 0 ? round($converted / $totalRecords * 100, 2) : 0);
        $request->session()->flash('table_config', [
            'table_name' => $tableName,
            'id_field' => $idField,
            'province_field' => $provinceField,
            'district_field' => $districtField,
            'ward_field' => $wardField
        ]);

        return back()->with('success', "Đã chuyển đổi $converted bản ghi thành công (trong đó cập nhật $provinceUpdated tỉnh/thành phố), $failed bản ghi thất bại.");
    }

    /**
     * Kiểm tra và thêm 2 cột mới nếu chưa tồn tại
     */
    protected function checkAndAddColumns($tableName)
    {
        if (!Schema::hasColumn($tableName, 'convert_status')) {
            Schema::table($tableName, function ($table) {
                $table->string('convert_status', 20)->nullable()->comment('Trạng thái chuyển đổi: success/failed');
            });
        }

        if (!Schema::hasColumn($tableName, 'convert_error')) {
            Schema::table($tableName, function ($table) {
                $table->text('convert_error')->nullable()->comment('Thông báo lỗi chuyển đổi');
            });
        }
    }

    /**
     * Phân loại thông báo lỗi chi tiết
     */
    protected function getDetailedErrorMessage($province, $district, $ward)
    {
        $normalizedProvince = $this->normalizeName($province);
        $mergedProvince = $this->provinceMergeMap[$normalizedProvince] ?? $normalizedProvince;

        // Kiểm tra xem tỉnh có trong danh sách mapping không
        if (!isset($this->wardMappings[$mergedProvince])) {
            return "Tỉnh/thành phố '$province' không hợp lệ hoặc không có trong danh sách chuyển đổi";
        }

        // Kiểm tra các trường hợp cụ thể
        $provinceExists = !empty($province);
        $districtExists = !empty($district);
        $wardExists = !empty($ward);

        if ($provinceExists && $districtExists && $wardExists) {
            return "Không tìm thấy phường/xã '$ward' thuộc '$district', '$province' trong danh sách chuyển đổi";
        }

        if ($provinceExists && $districtExists) {
            return "Thiếu thông tin phường/xã (có tỉnh '$province' và quận/huyện '$district')";
        }

        if ($provinceExists) {
            return "Thiếu thông tin quận/huyện và phường/xã (chỉ có tỉnh '$province')";
        }

        return "Thông tin địa chỉ không đầy đủ hoặc không hợp lệ";
    }

    /**
     * Cập nhật trạng thái và lỗi vào record
     */
    protected function updateRecordStatus($tableName, $idField, $recordId, $status, $errorMessage)
    {
        DB::table($tableName)
            ->where($idField, $recordId)
            ->update([
                'convert_status' => $status,
                'convert_error' => $errorMessage
            ]);
    }

    /**
     * Kiểm tra bảng và các trường có tồn tại trong database không
     */
    protected function validateTableAndFields($tableName, $idField, $provinceField, $districtField, $wardField)
    {
        // Kiểm tra bảng có tồn tại không
        if (!Schema::hasTable($tableName)) {
            throw new \Exception("Bảng '$tableName' không tồn tại trong database.");
        }

        // Lấy danh sách các cột trong bảng
        $columns = Schema::getColumnListing($tableName);

        // Kiểm tra các trường bắt buộc
        $requiredFields = [
            'ID' => $idField,
            'Tỉnh/Thành phố' => $provinceField,
            'Quận/Huyện' => $districtField,
            'Phường/Xã' => $wardField
        ];

        $missingFields = [];

        foreach ($requiredFields as $fieldName => $field) {
            if (!in_array($field, $columns)) {
                $missingFields[] = "$fieldName ($field)";
            }
        }

        if (!empty($missingFields)) {
            $fieldsList = implode(', ', $missingFields);
            throw new \Exception("Các trường sau không tồn tại trong bảng '$tableName': $fieldsList");
        }
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

                // Xử lý trường hợp "Giữ nguyên" và "Giữ nguyên hiện trạng"
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

    protected function normalizeProvinceName($provinceName)
    {
        $provinceName = trim($provinceName);

        // Danh sách các thành phố trực thuộc trung ương
        $cities = ['Hà Nội', 'Hồ Chí Minh', 'Hải Phòng', 'Đà Nẵng', 'Cần Thơ'];

        // Loại bỏ các tiền tố hiện có nếu có
        $provinceName = preg_replace('/^(Tỉnh|Thành phố)\s+/u', '', $provinceName);

        // Thêm tiền tố phù hợp
        if (in_array($provinceName, $cities)) {
            return "Thành phố $provinceName";
        } else {
            return "Tỉnh $provinceName";
        }
    }
}
