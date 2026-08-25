<?php
/**
 * mfa-helper.php - Multi-Factor Authentication (MFA) Helper for Khmer Payment Tracker
 * 
 * ឯកសារជំនួយសម្រាប់ការគ្រប់គ្រងការផ្ទៀងផ្ទាត់ពីរជំហាន (MFA) ដោយប្រើប្រាស់ Google Authenticator TOTP។
 * គាំទ្រដល់ការបង្កើត Secret Key, ការបង្កើត QR Code URL, និងការផ្ទៀងផ្ទាត់លេខកូដសម្ងាត់ ៦ ខ្ទង់។
 */

class MFAHelper {
    
    /**
     * បង្កើត random 16-character Base32 Secret Key សម្រាប់គណនីនីមួយៗ
     * Generate a random 16-character Base32 Secret Key for Google Authenticator
     */
    public static function generateSecret($length = 16) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * មុខងារបំប្លែង Base32 ទៅជាអក្សរធម្មតា (Binary)
     * Decode Base32 string to binary
     */
    private static function base32Decode($secret) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper($secret);
        $buf = '';
        $val = 0;
        $val_len = 0;
        
        for ($i = 0; $i < strlen($secret); $i++) {
            $c = $secret[$i];
            $v = strpos($alphabet, $c);
            if ($v === false) continue;
            $val = ($val << 5) | $v;
            $val_len += 5;
            if ($val_len >= 8) {
                $val_len -= 8;
                $buf .= chr(($val >> $val_len) & 0xFF);
            }
        }
        return $buf;
    }

    /**
     * ផ្ទៀងផ្ទាត់លេខកូដ ៦ ខ្ទង់ពី Google Authenticator
     * Verify a 6-digit TOTP code against a Base32 Secret Key.
     * គាំទ្រការត្រួតពិនិត្យភាពលម្អៀងនៃពេលវេលា (Time Drift) +/- 30 វិនាទីតាមលំនាំដើម ($discrepancy = 1)
     */
    public static function verifyCode($secret, $code, $discrepancy = 1) {
        // លុបចន្លោះប្រហោង និងត្រួតពិនិត្យលក្ខខណ្ឌលេខកូដ
        $code = trim($code);
        if (strlen($code) !== 6 || !is_numeric($code)) {
            return false;
        }

        $key = self::base32Decode($secret);
        $currentTimeSlice = floor(time() / 30);

        // ស្វែងរកលេខកូដក្នុងរង្វង់ពេលវេលាអនុញ្ញាត (Time Window)
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $timeSlice = $currentTimeSlice + $i;
            
            // បំប្លែង Time slice ទៅជា 8-byte Binary
            $time_bytes = pack('N*', 0) . pack('N*', $timeSlice);
            
            // គណនា Hash ដោយប្រើប្រាស់ HMAC-SHA1
            $hash = hash_hmac('sha1', $time_bytes, $key, true);
            
            // កំណត់ Offset
            $offset = ord($hash[19]) & 0xf;
            
            // ស្រង់យកតម្លៃ ៤ បៃ (4 Bytes)
            $otp = (
                (ord($hash[$offset+0]) & 0x7f) << 24 |
                (ord($hash[$offset+1]) & 0xff) << 16 |
                (ord($hash[$offset+2]) & 0xff) << 8 |
                (ord($hash[$offset+3]) & 0xff)
            ) % 1000000;

            $calculatedCode = str_pad($otp, 6, '0', STR_PAD_LEFT);

            // ផ្ទៀងផ្ទាត់ដោយប្រើប្រាស់ hash_equals ដើម្បីការពារការវាយប្រហារបែប Timing Attacks
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * បង្កើត Google Charts QR Code URL សម្រាប់ឱ្យអ្នកប្រើប្រាស់ស្កេនភ្ជាប់គណនី
     * Generate Google Charts QR Code URL for easy scanning with Google Authenticator App
     */
    public static function getQRUrl($username, $secret, $issuer = 'KhmerPaymentTracker') {
        $encodedUsername = rawurlencode($username);
        $encodedIssuer = rawurlencode($issuer);
        
        // ទម្រង់ស្តង់ដារ OTPAuth URL
        $otpauthUrl = "otpauth://totp/{$encodedIssuer}:{$encodedUsername}?secret={$secret}&issuer={$encodedIssuer}";
        
        // ប្រើប្រាស់ Google Charts API សម្រាប់បំប្លែងជា QR Code (ទំហំ 200x200px)
        return "https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=" . urlencode($otpauthUrl);
    }
}
?>