<?php

$accessKey = '_WW6qbGne-tRFmwru8ZaGrDxo_dRhcz4WxnZI_JN';
$secretKey = 'VErn_cNLcB1335tychrBQNiy6TBTlK83Qa1OnlVy';
$downloadUrl = 'https://skfgfjshdgsdurw.s3.cn-south-1.qiniucs.com/iiip.txt';
function s3SignUrl($accessKey, $secretKey, $originalUrl, $expires = 3600) {
    // 解析原始URL获取必要组件
    $parsed = parse_url($originalUrl);
    $region = extractRegionFromHost($parsed['host']);
    
    // 设置签名所需时间参数
    $timestamp = time();
    $date = gmdate('Ymd', $timestamp);
    $dateTime = gmdate('Ymd\THis\Z', $timestamp);
    
    // 生成规范请求
    $canonicalQuery = "X-Amz-Algorithm=AWS4-HMAC-SHA256"
        . "&X-Amz-Credential=" . rawurlencode("$accessKey/$date/$region/s3/aws4_request")
        . "&X-Amz-Date=$dateTime"
        . "&X-Amz-Expires=$expires"
        . "&X-Amz-SignedHeaders=host";
    
    // 生成待签字符串
    $canonicalRequest = "GET\n{$parsed['path']}\n$canonicalQuery\nhost:{$parsed['host']}\n\nhost\nUNSIGNED-PAYLOAD";
    $canonicalHash = hash('sha256', $canonicalRequest);
    
    $stringToSign = "AWS4-HMAC-SHA256\n$dateTime\n$date/$region/s3/aws4_request\n$canonicalHash";
    
    // 计算签名
    $signingKey = hash_hmac("sha256", "aws4_request",
        hash_hmac("sha256", "s3",
            hash_hmac("sha256", $region,
                hash_hmac("sha256", $date, "AWS4" . $secretKey, true),
                true),
            true),
        true
    );
    
    $signature = hash_hmac('sha256', $stringToSign, $signingKey);
    
    // 构建最终URL
    return "{$parsed['scheme']}://{$parsed['host']}{$parsed['path']}?$canonicalQuery&X-Amz-Signature=$signature";
}

// 从S3域名提取区域
function extractRegionFromHost($host) {
    preg_match('/s3-([\w-]+)\.qiniucs\.com/', $host, $matches);
    return $matches[1] ?? 'cn-east-1';
}

// 标准S3格式URL
echo s3SignUrl($accessKey, $secretKey, $downloadUrl, 86400 * 7) . "\n\n";