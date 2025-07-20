<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
        $response = Http::get('https://vietnamlabs.com/api/vietnamprovince');

        if (!$response->successful()) {
            abort(500, 'Không thể tải dữ liệu từ API');
        }

        $data = $response->json('data');

        foreach ($data as $provinceData) {
            $province = $provinceData['province'] ?? null;
            if (!$province || !isset($provinceData['wards']) || !is_array($provinceData['wards'])) {
                continue;
            }

            $province = trim($province);

            foreach ($provinceData['wards'] as $ward) {
                $newWardName = trim($ward['name']);
                $mergedFromList = $ward['mergedFrom'] ?? [];

                // Nếu là chuỗi, ép thành mảng 1 phần tử
                if (is_string($mergedFromList)) {
                    $mergedFromList = [$mergedFromList];
                }

                // Trường hợp "Giữ nguyên" thì ánh xạ chính nó
                if (is_array($mergedFromList) && in_array('Giữ nguyên hiện trạng', $mergedFromList, true)) {
                    $this->wardMappings[$province][$this->normalizeName($newWardName)] = $newWardName;
                    continue;
                }

                foreach ($mergedFromList as $mergedItem) {
                    // Xử lý các trường hợp phức tạp như "xã Bình Hưng (huyện Bình Chánh) và phần còn lại của phường 7 (quận 8)"
                    $parts = preg_split('/\s+và\s+/u', $mergedItem);
                    foreach ($parts as $part) {
                        // Lọc ra tên chính từ phần phức tạp (ví dụ: "xã Bình Hưng (huyện Bình Chánh)" => "xã Bình Hưng")
                        if (preg_match('/^(.*?)\s*\(/u', $part, $matches)) {
                            $part = trim($matches[1]);
                        }

                        $normalized = $this->normalizeName($part);
                        if ($normalized !== '') {
                            $this->wardMappings[$province][$normalized] = $newWardName;
                        }
                    }
                }

                // Thêm mapping cho chính tên mới (để tìm kiếm không phân biệt hoa thường)
                $this->wardMappings[$province][$this->normalizeName($newWardName)] = $newWardName;
            }
        }

        // Log tổng số ward mappings để kiểm tra nhanh
        $total = 0;
        foreach ($this->wardMappings as $province => $wards) {
            $total += count($wards);
        }
        Log::info("Ward mappings loaded: tổng cộng {$total} bản ghi");
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
}
