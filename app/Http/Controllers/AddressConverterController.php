<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class AddressConverterController extends Controller
{
    // Biến lưu trữ ánh xạ các phường/xã
    protected $wardMappings = [];
    // Bản đồ hợp nhất các tỉnh thành
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
    /**
     * Hiển thị trang converter
     */
    public function showConverter()
    {
        return view('address-converter');
    }

    /**
     * Xử lý chuyển đổi địa chỉ hàng loạt
     */
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

        $converted = 0;
        $failed = 0;
        $provinceUpdated = 0;
        $failedRecords = [];
        $duplicatesRemoved = 0;

        // Lấy tổng số bản ghi để tính %
        $totalRecords = DB::table($tableName)->count();

        // Chuyển đổi địa chỉ
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
                    // Chuẩn hóa tên phường/xã trước khi xử lý
                    $normalizedWard = $this->normalizeWardName($oldWard);
                    // Khởi tạo giá trị mặc định
                    $errorMessage = null;

                    // Thay thế đoạn kiểm tra điều kiện hiện tại bằng:
                    if (!$oldProvince || !$oldWard) {
                        if (!$oldProvince && !$oldWard) {
                            $errorMessage = 'Thiếu thông tin tỉnh và phường/xã';
                        } elseif (!$oldProvince) {
                            $errorMessage = 'Thiếu thông tin tỉnh/thành phố';
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
                        continue;
                    }
                    $newWard = $this->findNewWard($oldProvince, $normalizedWard, $district);
                    $newProvince = $this->provinceMergeMap[$this->normalizeName($oldProvince)] ?? $oldProvince;
                    // Chuẩn hóa tên tỉnh/thành phố
                    $newProvince = $this->normalizeProvinceName($newProvince);
                    $oldProvinceNormalized = $this->normalizeProvinceName($oldProvince);

                    $updateData = [
                        $wardField => null // Luôn xóa trường ward_field
                    ];

                    if ($newWard) {
                        // Trường hợp chuyển đổi thành công
                        $updateData[$districtField] = $newWard;
                        $converted++;
                        Log::info("Converted successfully ID {$recordId}: $oldWard, $district, $oldProvince => $newWard");
                    } else {
                        // Trường hợp không chuyển đổi được
                        $newDistrict = !empty($district) ? trim($district . ', ' . $oldWard) : $oldWard;
                        $updateData[$districtField] = $newDistrict;

                        $errorMessage = $this->getDetailedErrorMessage($oldProvince, $district, $normalizedWard);
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

                    DB::table($tableName)
                        ->where($idField, $recordId)
                        ->update($updateData);
                }
            });

        // Xóa các bản ghi trùng lặp sau khi đã cập nhật xong
        // Chỉ thực hiện nếu wardField đã được xóa (set thành null or rỗng)
        $hasWardField = Schema::hasColumn($tableName, $wardField);

        if (!$hasWardField || DB::table($tableName)->whereRaw("$wardField IS NOT NULL AND $wardField <> ''")->count() === 0) {
            // Tìm các bản ghi trùng lặp
            $duplicates = DB::table($tableName)
                ->select($provinceField, $districtField)
                ->selectRaw('COUNT(*) as count')
                ->groupBy($provinceField, $districtField)
                ->having('count', '>', 1)
                ->get();

            foreach ($duplicates as $duplicate) {
                // Lấy tất cả ID của các bản ghi trùng lặp
                $ids = DB::table($tableName)
                    ->where($provinceField, $duplicate->{$provinceField})
                    ->where($districtField, $duplicate->{$districtField})
                    ->orderBy($idField)
                    ->pluck($idField)
                    ->toArray();

                // Giữ lại bản ghi đầu tiên, xóa các bản ghi còn lại
                if (count($ids) > 1) {
                    $idsToDelete = array_slice($ids, 1);
                    DB::table($tableName)->whereIn($idField, $idsToDelete)->delete();
                    $duplicatesRemoved += count($idsToDelete);
                    Log::info("Removed " . count($idsToDelete) . " duplicates for {$duplicate->{$provinceField}} - {$duplicate->{$districtField}}");
                }
            }
        }

        // Lưu danh sách thất bại vào session
        $request->session()->flash('failed_records', array_slice($failedRecords, 0, 1000));
        $request->session()->flash('total_failed', $failed);
        $request->session()->flash('converted', $converted);
        $request->session()->flash('total_records', $totalRecords);
        $request->session()->flash('success_rate', $totalRecords > 0 ? round($converted / $totalRecords * 100, 2) : 0);
        $request->session()->flash('duplicates_removed', $duplicatesRemoved);
        $request->session()->flash('table_config', [
            'table_name' => $tableName,
            'id_field' => $idField,
            'province_field' => $provinceField,
            'district_field' => $districtField,
            'ward_field' => $wardField
        ]);

        return back()->with('success', "Đã chuyển đổi $converted bản ghi thành công (trong đó cập nhật $provinceUpdated tỉnh/thành phố), $failed bản ghi thất bại. Đã xóa $duplicatesRemoved bản ghi trùng lặp.");
    }

    // ========== CÁC PHƯƠNG THỨC HỖ TRỢ ==========

    /**
     * Tải dữ liệu ánh xạ từ file JSON
     */
    protected function loadWardMappingsFromApi()
    {
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
                $districts = $ward['district'] ?? ['Không xác định'];

                // Đảm bảo $mergedFromList luôn là array
                if (!is_array($mergedFromList)) {
                    $mergedFromList = [$mergedFromList];
                }

                // Xử lý trường hợp "Giữ nguyên" và "Giữ nguyên hiện trạng"
                $isPreserved = in_array('Giữ nguyên', $mergedFromList, true) ||
                    in_array('Giữ nguyên trạng', $mergedFromList, true) ||
                    in_array('Giữ nguyên hiện trạng', $mergedFromList, true);

                if ($isPreserved) {
                    $this->addWardMapping($province, $newWardName, $newWardName, $districts);
                    continue;
                }

                foreach ($mergedFromList as $mergedItem) {
                    // Bỏ qua nếu là "một phần của" hoặc "phần còn lại của"
                    if ($this->shouldIgnoreWard($mergedItem)) {
                        continue;
                    }

                    $parts = preg_split('/\s+và\s+/u', $mergedItem);
                    foreach ($parts as $part) {
                        if (preg_match('/^(.*?)(?:\s*\(|$)/u', $part, $matches)) {
                            $part = trim($matches[1]);
                        }

                        // Tiếp tục bỏ qua nếu có cụm từ đặc biệt
                        if ($this->shouldIgnoreWard($part)) {
                            continue;
                        }

                        $normalized = $this->normalizeName($part);
                        if ($normalized !== '') {
                            $this->addWardMapping($province, $normalized, $newWardName, $districts);
                            $this->addWardMapping($province, $part, $newWardName, $districts);
                        }
                    }
                }

                // Thêm mapping cho chính tên mới
                $this->addWardMapping($province, $newWardName, $newWardName, $districts);
                $this->addWardMapping($province, $this->normalizeName($newWardName), $newWardName, $districts);
            }
        }

        $total = 0;
        foreach ($this->wardMappings as $province => $districts) {
            foreach ($districts as $district => $wards) {
                $total += count($wards);
            }
        }
        Log::info("Ward mappings loaded from file: tổng cộng {$total} bản ghi");
    }

    /**
     * Tìm tên phường/xã mới dựa trên thông tin cũ
     */
    protected function findNewWard($province, $oldWard, $district)
    {
        $normalizedProvince = $this->normalizeName($province);
        $mergedProvince = $this->provinceMergeMap[$normalizedProvince] ?? $normalizedProvince;
        $normalizedDistrict = $this->normalizeName($district);

        // Danh sách các biến thể tên cần thử
        $wardVariants = [
            $oldWard,
            $this->normalizeName($oldWard),
            preg_replace('/^(Xã|Phường|Thị trấn)\s+/ui', '', $oldWard)
        ];
        $wardVariants = array_unique(array_filter($wardVariants));

        foreach ($wardVariants as $wardVariant) {
            // Bỏ qua nếu là "một phần của" hoặc "phần còn lại của"
            if ($this->shouldIgnoreWard($wardVariant)) {
                continue;
            }

            $normalizedWard = $this->normalizeName($wardVariant);
            $lowerWard = mb_strtolower($normalizedWard);
            $unsignedWard = $this->removeDiacritics($lowerWard);

            // 1. Tìm kiếm chính xác trong quận/huyện cụ thể
            if (isset($this->wardMappings[$mergedProvince][$normalizedDistrict][$normalizedWard])) {
                return $this->wardMappings[$mergedProvince][$normalizedDistrict][$normalizedWard];
            }

            // 2. Tìm kiếm trong tất cả các quận/huyện (khi district = 'Không xác định')
            if (isset($this->wardMappings[$mergedProvince]['*'][$normalizedWard])) {
                return $this->wardMappings[$mergedProvince]['*'][$normalizedWard];
            }

            if (!isset($this->wardMappings[$mergedProvince])) {
                continue;
            }

            // 3. Tìm kiếm lỏng hơn - chỉ cần chứa từ khóa
            $searchAreas = [
                $normalizedDistrict => $this->wardMappings[$mergedProvince][$normalizedDistrict] ?? [],
                '*' => $this->wardMappings[$mergedProvince]['*'] ?? []
            ];

            foreach ($searchAreas as $searchDistrict => $wards) {
                foreach ($wards as $key => $value) {
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
        }

        return null;
    }

    /**
     * Thêm một ánh xạ phường/xã vào danh sách
     */
    protected function addWardMapping($province, $oldWard, $newWard, $districts)
    {
        foreach ($districts as $district) {
            $normalizedDistrict = $this->normalizeName($district);
            if ($normalizedDistrict === 'Không xác định') {
                $normalizedDistrict = '*'; // Đại diện cho tất cả các quận/huyện
            }

            if (!isset($this->wardMappings[$province][$normalizedDistrict])) {
                $this->wardMappings[$province][$normalizedDistrict] = [];
            }

            $this->wardMappings[$province][$normalizedDistrict][$oldWard] = $newWard;
        }
    }

    /**
     * Chuẩn hóa tên phường/xã
     */
    protected function normalizeWardName($wardName)
    {
        $wardName = trim($wardName);

        // Nếu chỉ là số (vd: "8")
        if (preg_match('/^\d+$/', $wardName)) {
            return 'Phường ' . str_pad($wardName, 2, '0', STR_PAD_LEFT);
        }

        // Nếu có dạng "Phường X" với X < 10 (vd: "Phường 6")
        if (preg_match('/^Phường\s+(\d+)$/u', $wardName, $matches)) {
            $number = $matches[1];
            if ($number < 10) {
                return 'Phường ' . str_pad($number, 2, '0', STR_PAD_LEFT);
            }
        }

        // Các trường hợp khác giữ nguyên
        return $wardName;
    }

    /**
     * Chuẩn hóa tên (bỏ các tiền tố)
     */
    protected function normalizeName($text)
    {
        $text = trim($text);
        $text = preg_replace('/^(Thành phố|Tỉnh|Quận|Huyện|Phường|Xã|Thị xã|Thị trấn)\s+/u', '', $text);
        return $text;
    }

    /**
     * Chuẩn hóa tên tỉnh/thành phố
     */
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

    /**
     * Tạo thông báo lỗi chi tiết
     */
    protected function getDetailedErrorMessage($province, $district, $ward)
    {
        $normalizedProvince = $this->normalizeName($province);
        $mergedProvince = $this->provinceMergeMap[$normalizedProvince] ?? $normalizedProvince;
        $normalizedDistrict = $this->normalizeName($district);

        if (!isset($this->wardMappings[$mergedProvince])) {
            return "Tỉnh/thành phố '$province' không hợp lệ hoặc không có trong danh sách chuyển đổi";
        }

        $provinceExists = !empty($province);
        $districtExists = !empty($district);
        $wardExists = !empty($ward);

        if ($provinceExists && $districtExists && $wardExists) {
            if (
                !isset($this->wardMappings[$mergedProvince][$normalizedDistrict]) &&
                !isset($this->wardMappings[$mergedProvince]['*'])
            ) {
                return "Không tìm thấy quận/huyện '$district' thuộc '$province' trong danh sách chuyển đổi";
            }
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
     * Kiểm tra xem có nên bỏ qua tên phường/xã không
     */
    protected function shouldIgnoreWard($wardName)
    {
        $ignorePatterns = [
            '/một phần (của )?/iu',
            '/phần còn lại (của )?/iu',
            '/\bvà\b.*(một phần|phần còn lại)/iu'
        ];

        foreach ($ignorePatterns as $pattern) {
            if (preg_match($pattern, $wardName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kiểm tra khớp một phần
     */
    protected function isPartialMatch($needle, $haystack)
    {
        // Kiểm tra xem needle có xuất hiện trong haystack không
        // hoặc haystack có xuất hiện trong needle không
        return str_contains($haystack, $needle) || str_contains($needle, $haystack);
    }

    /**
     * Xóa dấu tiếng Việt
     */
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
}
