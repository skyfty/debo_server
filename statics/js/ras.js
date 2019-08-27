function ras_val(data) {
    //公钥
    var pub_key ='-----BEGIN PUBLIC KEY-----\n' +
        'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCsr+2TFd+AnMEyfQuwJiwAHc6Q\n' +
        'Qvzorme66XQSIoiwEpFKvRktiwAWqnw0Y84JTdBQTS6jknJu585/dI+qJKj4TBKk\n' +
        'Rl2bcqouPbqveJRA929872qskIKaiymmzAqEXP6HsxfDk7zVoZnEZAOm5CQU6+5a\n' +
        'K19SXzQSF1Lh0tEV+wIDAQAB\n' +
        '-----END PUBLIC KEY-----';

    var jsencrypt = new JSEncrypt();
    //初始化公钥
    jsencrypt.setPublicKey(pub_key);
    //通过 公钥 加密
    var encrypted = jsencrypt.encrypt(data);
    return encrypted;
}