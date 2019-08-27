<?php
class Rsa
{
    // 后台私钥
    private static $PRIVATE_KEY = '-----BEGIN RSA PRIVATE KEY-----
MIICXgIBAAKBgQCsr+2TFd+AnMEyfQuwJiwAHc6QQvzorme66XQSIoiwEpFKvRkt
iwAWqnw0Y84JTdBQTS6jknJu585/dI+qJKj4TBKkRl2bcqouPbqveJRA929872qs
kIKaiymmzAqEXP6HsxfDk7zVoZnEZAOm5CQU6+5aK19SXzQSF1Lh0tEV+wIDAQAB
AoGAavisDWIOSmhQEUt+swZiWUwdiiXE7woifZlh6l30GEXYNNkAwMKLMn83Y2iz
1/WY5jV5f6AXPPZhZ3i4CeidtfUsjwvzwtDj5CSiyQODVQgB1z/tauwd/YCvxP/+
8NGzdt6l4QYBo/1QpcOaytXGiRoVC55jeQ/wYK8Z1YpxJxECQQDX5W+DF/KVHWQf
cNvrFVOUU1Yje3F/bhnMY22hVAXasdyWXMrUo1BU8wN3kOWBoT4/wLhksK1QHaVi
TaelP0LDAkEAzMPDln0qXYLE7npFOIC/CwYcYXluczNAt6DGTn/HeGddKhtSQ/q6
pcF9dJOBYf97QJR7mPhjpTi8wd5Y8VM8aQJBAJIxYNNqce+bWWMY7zI+3LvBusCI
JJDfo0SNx3zJArXWXsLKzuYyOIFtlVlvmpmu8BIHlFVKdfGcQZNRdkYlkjUCQQCZ
MKQ1A/Mb/mdimqsKuJc0oh+9dOGC4gc62ddChyouE/aJN+N15DCbLYS0IF6deEs3
Z16IdNvnkej1iWk1MjZxAkEAsvrGvnZc1ZuBAFKaki8TFqpaZGAi9nfP6/TaOBF3
qkCH8R0cWHRTZC/J+vsX/UjkJZ8Ar3QC2XW6vXZzn4JF8Q==
-----END RSA PRIVATE KEY-----
';

    // 前端公钥
    private static $PUBLIC_KEY = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDxfxnNKmefpTaggS+w3xjLCVH
SHib+FhdzE5bGWck+WEdYpqcBsVccNhA1yKqFv7874Cj9yGZLXLoXz2+sXsCDrug
ep7AtKwJdxdzZUS76p8l50q07I0kzz4qkkEdOjbb1fBpgKwxItxmeFmMBQkxArU/
0/fvRs3rtOIY0ANx/QIDAQAB
-----END PUBLIC KEY-----
';
    /**
     * 获取私钥
     * @return bool|resource
     */
    public static function getPrivateKey()
    {
        $privKey = self::$PRIVATE_KEY;
        return openssl_pkey_get_private($privKey);
    }

    /**
     * 获取公钥
     * @return bool|resource
     */
    public static function getPublicKey()
    {
        $publicKey = self::$PUBLIC_KEY;
        return openssl_pkey_get_public($publicKey);
    }

    /**
     * 私钥加密
     * @param string $data
     * @return null|string
     */
    public static function privEncrypt($data = '')
    {
        if (!is_string($data)) {
            return null;
        }
        return openssl_private_encrypt($data, $encrypted, self::getPrivateKey()) ? base64_encode($encrypted) : null;
    }

    /**
     * 公钥加密
     * @param string $data
     * @return null|string
     */
    public static function publicEncrypt($data = '')
    {
        if (!is_string($data)) {
            return null;
        }
        return openssl_public_encrypt($data, $encrypted, self::getPublicKey()) ? base64_encode($encrypted) : null;
    }

    /**
     * 私钥解密
     * @param string $encrypted
     * @return null
     */
    public static function privDecrypt($encrypted = '')
    {
        if (!is_string($encrypted)) {
            return null;
        }
        return (openssl_private_decrypt(base64_decode($encrypted), $decrypted, self::getPrivateKey())) ? $decrypted : null;
    }

    /**
     * 公钥解密
     * @param string $encrypted
     * @return null
     */
    public static function publicDecrypt($encrypted = '')
    {
        if (!is_string($encrypted)) {
            return null;
        }
        return (openssl_public_decrypt(base64_decode($encrypted), $decrypted, self::getPublicKey())) ? $decrypted : null;
    }
}