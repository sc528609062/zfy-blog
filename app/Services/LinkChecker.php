<?php

namespace App\Services;

use App\Models\Link;
use App\Models\LinkCheck;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class LinkChecker
{
    public function check(Link $link): LinkCheck
    {
        $status = 'failed';
        $code = null;
        $message = '连接失败或超时';
        try {
            $parts = parse_url($link->url);
            $host = strtolower($parts['host'] ?? '');
            if (! in_array($parts['scheme'] ?? '', ['http', 'https'], true) || isset($parts['user']) || isset($parts['pass']) || ! preg_match('/^[a-z0-9.-]+$/', $host)) {
                throw new RuntimeException('unsupported');
            }
            $port = $parts['port'] ?? ($parts['scheme'] === 'https' ? 443 : 80);
            if (! in_array($port, [80, 443], true)) {
                throw new RuntimeException('port');
            }
            $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : gethostbynamel($host);
            if (! $addresses) {
                throw new RuntimeException('dns');
            }
            foreach ($addresses as $address) {
                if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    throw new RuntimeException('address');
                }
            }
            // Pin the validated address and refuse redirects to prevent requests to internal hosts.
            $response = Http::connectTimeout(3)->timeout(8)->withOptions([
                'allow_redirects' => false,
                'curl' => [CURLOPT_RESOLVE => [$host.':'.$port.':'.$addresses[0]], CURLOPT_PROXY => ''],
            ])->head($link->url);
            $code = $response->status();
            $status = $code >= 200 && $code < 400 ? 'healthy' : 'failed';
            $message = $code >= 300 && $code < 400 ? '网站返回重定向' : 'HTTP '.$code;
        } catch (RuntimeException $exception) {
            $message = '地址无法解析或不允许检测';
        } catch (\Throwable $exception) {
            $message = '连接失败或超时';
        }
        $link->update(['checked_at' => now()]);

        return LinkCheck::create(['link_id' => $link->id, 'status' => $status, 'http_code' => $code, 'message' => $message, 'checked_at' => now()]);
    }
}
