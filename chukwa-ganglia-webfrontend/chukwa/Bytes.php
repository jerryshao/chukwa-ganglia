<?php
class Bytes {
    public static function toLong($bytes) {
        $data = unpack("Nfirst/Nsecond", $bytes);
        $val = (float)($data['first']) * (1 << 32) + (float)($data['second']);
        return $val;
    }
}


?>
