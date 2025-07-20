<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConvertAddressCommand extends Command
{
    protected $signature = 'address:convert';
    protected $description = 'Chuyển đổi địa chỉ từ định dạng cũ sang mới';

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

    public function handle()
    {
        $this->info('Bắt đầu chuyển đổi địa chỉ...');
        $this->loadWardMappingsFromApi();

        $converted = 0;
        $failed = 0;
        $provinceUpdated = 0;
        $totalRecords = DB::table('vtiger_diachicf')->count();

        $records = DB::table('vtiger_diachicf')->orderBy('diachiid')->get();

        $bar = $this->output->createProgressBar($totalRecords);
        $bar->start();

        foreach ($records as $record) {
            $oldProvince = $record->cf_860;
            $oldWard = $record->cf_864;

            if (!$oldProvince || !$oldWard) {
                $failed++;
                $bar->advance();
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

            $bar->advance();
        }

        $bar->finish();

        $successRate = $totalRecords > 0 ? round($converted / $totalRecords * 100, 2) : 0;

        $this->newLine(2);
        $this->info('Kết quả chuyển đổi:');
        $this->table(
            ['Thông số', 'Giá trị'],
            [
                ['Tổng số bản ghi', number_format($totalRecords)],
                ['Chuyển đổi thành công', number_format($converted) . " ($successRate%)"],
                ['Cập nhật tỉnh/thành phố', number_format($provinceUpdated)],
                ['Thất bại', number_format($failed)],
            ]
        );

        $this->info('Chuyển đổi địa chỉ hoàn tất!');
    }

    protected function loadWardMappingsFromApi()
    {
        $this->info('Đang tải dữ liệu địa chỉ từ API...');

        $response = Http::get('https://vietnamlabs.com/api/vietnamprovince');

        if (!$response->successful()) {
            $this->error('Không thể tải dữ liệu từ API');
            return;
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

                if (is_string($mergedFromList)) {
                    $mergedFromList = [$mergedFromList];
                }

                if (is_array($mergedFromList) && in_array('Giữ nguyên hiện trạng', $mergedFromList, true)) {
                    $this->wardMappings[$province][$this->normalizeName($newWardName)] = $newWardName;
                    continue;
                }

                foreach ($mergedFromList as $mergedItem) {
                    $parts = preg_split('/\s+và\s+/u', $mergedItem);
                    foreach ($parts as $part) {
                        if (preg_match('/^(.*?)\s*\(/u', $part, $matches)) {
                            $part = trim($matches[1]);
                        }

                        $normalized = $this->normalizeName($part);
                        if ($normalized !== '') {
                            $this->wardMappings[$province][$normalized] = $newWardName;
                        }
                    }
                }

                $this->wardMappings[$province][$this->normalizeName($newWardName)] = $newWardName;
            }
        }

        $total = 0;
        foreach ($this->wardMappings as $province => $wards) {
            $total += count($wards);
        }

        $this->info("Đã tải $total mapping phường/xã từ API");
    }

    protected function findNewWard($province, $oldWard)
    {
        $normalizedProvince = $this->normalizeName($province);
        $mergedProvince = $this->provinceMergeMap[$normalizedProvince] ?? $normalizedProvince;

        $wardVariants = [
            $oldWard,
            $this->normalizeName($oldWard),
            preg_replace('/^(Xã|Phường|Thị trấn)\s+/ui', '', $oldWard)
        ];

        $wardVariants = array_unique(array_filter($wardVariants));

        foreach ($wardVariants as $wardVariant) {
            $normalizedWard = $this->normalizeName($wardVariant);
            $lowerWard = mb_strtolower($normalizedWard);
            $unsignedWard = $this->removeDiacritics($lowerWard);

            if (isset($this->wardMappings[$mergedProvince][$normalizedWard])) {
                return $this->wardMappings[$mergedProvince][$normalizedWard];
            }

            if (!isset($this->wardMappings[$mergedProvince])) {
                continue;
            }

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
