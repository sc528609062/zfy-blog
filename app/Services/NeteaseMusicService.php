<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class NeteaseMusicService
{
    /**
     * @var array<string, array{url: string, cover: string}>|null
     */
    private ?array $audioMediaIndex = null;

    public function playlist(string $id): array
    {
        $id = $this->musicId($id);
        abort_if($id === '', 404, '网易云歌单 ID 不正确。');

        $payload = $this->readJson($this->playlistPath($id));
        $audio = $payload ? $this->audioList($payload) : [];

        if ($payload === null || $audio === []) {
            $remotePayload = $this->fetchNeteasePlaylist($id, $payload === null);

            if ($remotePayload) {
                $payload = $remotePayload;
                $audio = $this->audioList($payload);
            }
        }

        abort_if($payload === null, 404, '未找到歌单数据，请检查网易云歌单 ID。');

        return [
            'id' => $id,
            'type' => 'playlist',
            'name' => (string) data_get($payload, 'name', data_get($payload, 'playlist.name', '本地歌单')),
            'audio' => $audio,
            'tracks' => $this->trackList($payload),
        ];
    }

    public function song(string $id): array
    {
        $id = $this->musicId($id);
        abort_if($id === '', 404, '网易云歌曲 ID 不正确。');

        $audio = $this->findAudio($id);
        $audio ??= $this->audioFromLocalTrack($this->fetchNeteaseSong($id, false) ?: []);
        abort_if($audio === null, 404, '未找到本地歌曲数据，请先在本地歌单或歌曲 JSON 中配置音频文件。');

        return [
            'id' => $id,
            'type' => 'song',
            'name' => $audio['name'],
            'audio' => [$audio],
        ];
    }

    public function streamUrl(string $id): string
    {
        $id = $this->musicId($id);
        abort_if($id === '', 404, '网易云歌曲 ID 不正确。');

        $source = $this->findSourceUrl($id);

        return $source !== '' ? $source : $this->neteaseStreamUrl($id);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, string>>
     */
    private function audioList(array $payload): array
    {
        return collect($this->tracksFromPayload($payload))
            ->map(fn ($track): ?array => is_array($track) ? $this->audioFromLocalTrack($track) : null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, string>>
     */
    private function trackList(array $payload): array
    {
        return collect($this->tracksFromPayload($payload))
            ->map(fn ($track): ?array => is_array($track) ? $this->trackFromLocalTrack($track) : null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>|null
     */
    private function findAudio(string $id): ?array
    {
        $song = $this->readJson($this->songPath($id));
        $audio = $song ? $this->audioFromLocalTrack($song) : null;

        if ($audio) {
            return $audio;
        }

        foreach ($this->playlistFiles() as $path) {
            $playlist = $this->readJson($path);
            if (! $playlist) {
                continue;
            }

            $audio = collect($this->audioList($playlist))->firstWhere('id', $id);

            if (is_array($audio)) {
                return $audio;
            }
        }

        return null;
    }

    private function findSourceUrl(string $id): string
    {
        $song = $this->readJson($this->songPath($id));
        $source = is_array($song) ? $this->sourceUrlFromTrack($song) : '';

        if ($source !== '') {
            return $source;
        }

        foreach ($this->playlistFiles() as $path) {
            $playlist = $this->readJson($path);
            $track = $playlist ? $this->findRawTrack($playlist, $id) : null;
            $source = $track ? $this->sourceUrlFromTrack($track) : '';

            if ($source !== '') {
                return $source;
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $track
     * @return array<string, string>|null
     */
    private function audioFromLocalTrack(array $track): ?array
    {
        $id = $this->trackId($track);
        $source = $this->sourceUrlFromTrack($track);

        if ($id === '' || $source === '') {
            return null;
        }

        return [
            'id' => $id,
            'name' => (string) (data_get($track, 'name') ?: data_get($track, 'title') ?: '本地歌曲'),
            'artist' => $this->artists($track),
            'url' => '/api/v1/netease/song/'.$id.'/stream',
            'cover' => $this->coverUrlFromTrack($track),
            'lrc' => $this->lyricValue((string) (data_get($track, 'lrc') ?: data_get($track, 'lyric') ?: '')),
        ];
    }

    /**
     * @param  array<string, mixed>  $track
     * @return array<string, string>|null
     */
    private function trackFromLocalTrack(array $track): ?array
    {
        $name = trim((string) (data_get($track, 'name') ?: data_get($track, 'title') ?: ''));

        if ($name === '') {
            return null;
        }

        return [
            'id' => $this->trackId($track),
            'name' => $name,
            'artist' => $this->artists($track),
            'cover' => $this->coverUrlFromTrack($track),
            'playable' => $this->sourceUrlFromTrack($track) !== '' ? '1' : '0',
        ];
    }

    /**
     * @param  array<string, mixed>  $track
     */
    private function artists(array $track): string
    {
        $artist = (string) (data_get($track, 'artist') ?: data_get($track, 'author') ?: '');

        if ($artist !== '') {
            return $artist;
        }

        $artists = data_get($track, 'artists', data_get($track, 'ar', []));

        $names = collect(is_array($artists) ? $artists : [])
            ->map(fn ($artist): string => is_array($artist) ? (string) ($artist['name'] ?? '') : '')
            ->filter()
            ->implode(' / ');

        return $names !== '' ? $names : '本地音乐';
    }

    private function sourceUrlFromTrack(array $track): string
    {
        $source = $this->localUrl((string) (data_get($track, 'url') ?: data_get($track, 'src') ?: data_get($track, 'audio') ?: data_get($track, 'file') ?: ''));

        if ($source !== '') {
            return $source;
        }

        $source = $this->matchedMediaValue($track, 'url');

        if ($source !== '') {
            return $source;
        }

        $neteaseId = $this->musicId((string) data_get($track, 'id', ''));

        return $neteaseId !== '' ? $this->neteaseStreamUrl($neteaseId) : '';
    }

    /**
     * @param  array<string, mixed>  $track
     */
    private function coverUrlFromTrack(array $track): string
    {
        $cover = $this->localUrl((string) (data_get($track, 'cover') ?: data_get($track, 'pic') ?: data_get($track, 'poster') ?: data_get($track, 'album.picUrl') ?: data_get($track, 'al.picUrl') ?: ''));

        return $cover !== '' ? $cover : $this->matchedMediaValue($track, 'cover');
    }

    private function lyricValue(string $value): string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return '';
        }

        if (str_contains($value, "\n") || str_starts_with($trimmed, '[')) {
            return $value;
        }

        return $this->localUrl($trimmed);
    }

    /**
     * @return array<string, mixed>
     */
    private function readJson(string $path): ?array
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            return $this->readResourceJson($path);
        }

        $payload = json_decode((string) $disk->get($path), true);
        abort_unless(is_array($payload), 422, '本地音乐 JSON 格式不正确。');

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readResourceJson(string $path): ?array
    {
        $fullPath = resource_path('data/'.$path);

        if (! is_file($fullPath)) {
            return null;
        }

        $payload = json_decode((string) file_get_contents($fullPath), true);
        abort_unless(is_array($payload), 422, '本地音乐 JSON 格式不正确。');

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchNeteasePlaylist(string $id, bool $abortOnFailure = true): ?array
    {
        $payload = Cache::remember(
            'zfy.netease.official.playlist.'.$id,
            now()->addDays(7),
            fn (): array => $this->neteaseGet('https://music.163.com/api/playlist/detail', ['id' => $id], $abortOnFailure)
        );

        if (! $payload) {
            return null;
        }

        $tracks = collect(data_get($payload, 'result.tracks', []))
            ->map(fn ($track): ?array => is_array($track) ? $this->neteaseTrack($track) : null)
            ->filter()
            ->values()
            ->all();

        if ($tracks === []) {
            return null;
        }

        return [
            'id' => $id,
            'name' => (string) data_get($payload, 'result.name', '网易云歌单'),
            'tracks' => $tracks,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchNeteaseSong(string $id, bool $abortOnFailure = true): ?array
    {
        $payload = Cache::remember(
            'zfy.netease.official.song.'.$id,
            now()->addDays(7),
            fn (): array => $this->neteaseGet('https://music.163.com/api/song/detail', [
                'id' => $id,
                'ids' => '['.$id.']',
            ], $abortOnFailure)
        );

        $track = data_get($payload, 'songs.0', []);

        return is_array($track) ? $this->neteaseTrack($track) : null;
    }

    /**
     * @param  array<string, mixed>  $track
     * @return array<string, mixed>|null
     */
    private function neteaseTrack(array $track): ?array
    {
        $id = $this->musicId((string) data_get($track, 'id', ''));

        if ($id === '') {
            return null;
        }

        return [
            'id' => $id,
            'name' => (string) (data_get($track, 'name') ?: '网易云歌曲'),
            'artist' => $this->artists($track),
            'cover' => $this->trustedRemoteUrl((string) (data_get($track, 'album.picUrl') ?: data_get($track, 'al.picUrl') ?: data_get($track, 'album.blurPicUrl') ?: '')),
        ];
    }

    /**
     * @param  array<string, string|int>  $query
     * @return array<string, mixed>
     */
    private function neteaseGet(string $url, array $query, bool $abortOnFailure): array
    {
        $response = Http::timeout(12)
            ->retry(1, 200)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0',
                'Referer' => 'https://music.163.com/',
            ])
            ->get($url, $query);

        if (! $response->successful()) {
            abort_if($abortOnFailure, 502, '网易云官方数据获取失败。');

            return [];
        }

        return $response->json() ?: [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function findRawTrack(array $payload, string $id): ?array
    {
        return collect($this->tracksFromPayload($payload))
            ->first(fn ($track): bool => is_array($track) && $this->trackId($track) === $id);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, mixed>
     */
    private function tracksFromPayload(array $payload): array
    {
        $tracks = $payload;

        if (Arr::isAssoc($payload)) {
            $tracks = data_get($payload, 'audio', data_get($payload, 'tracks', data_get($payload, 'playlist.tracks', [])));
        }

        return is_array($tracks) ? $tracks : [];
    }

    /**
     * @return array<int, string>
     */
    private function playlistFiles(): array
    {
        return collect(Storage::disk('local')->files('netease/playlists'))
            ->merge($this->resourcePlaylistFiles())
            ->filter(fn (string $path): bool => Str::endsWith($path, '.json'))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function resourcePlaylistFiles(): array
    {
        return collect(glob(resource_path('data/netease/playlists/*.json')) ?: [])
            ->map(fn (string $path): string => 'netease/playlists/'.basename($path))
            ->values()
            ->all();
    }

    private function musicId(string $id): string
    {
        $id = trim($id);

        return preg_match('/^\d+$/', $id) === 1 ? $id : '';
    }

    /**
     * @param  array<string, mixed>  $track
     */
    private function trackId(array $track): string
    {
        $id = $this->musicId((string) data_get($track, 'id', ''));

        if ($id !== '') {
            return $id;
        }

        $name = trim((string) (data_get($track, 'name') ?: data_get($track, 'title') ?: ''));
        $artist = trim($this->artists($track));

        if ($name === '') {
            return '';
        }

        return sprintf('%u', crc32(Str::lower($name.'|'.$artist)));
    }

    private function localUrl(string $url): string
    {
        $url = trim(str_replace('\\', '/', $url));

        if ($url === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $url) === 1) {
            return $this->sameSiteUrl($url) ?: $this->trustedRemoteUrl($url);
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url) === 1 || str_starts_with($url, '//')) {
            return '';
        }

        $path = $this->cleanPath($url);

        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, 'storage/media/')) {
            $path = $this->mediaRoot().'/'.substr($path, strlen('storage/media/'));
        }

        if (str_starts_with($url, '/') || str_starts_with($path, $this->mediaRoot().'/')) {
            return url('/'.$path);
        }

        return url('/'.$this->mediaRoot().'/'.$path);
    }

    private function neteaseStreamUrl(string $id): string
    {
        return 'https://music.163.com/song/media/outer/url?id='.$id.'.mp3';
    }

    private function trustedRemoteUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        $host = (string) parse_url($url, PHP_URL_HOST);

        if ($host === 'music.163.com' || Str::endsWith($host, '.music.126.net')) {
            return str_starts_with($url, 'http://') ? 'https://'.substr($url, 7) : $url;
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $track
     */
    private function matchedMediaValue(array $track, string $key): string
    {
        foreach ($this->mediaLookupKeys($track) as $lookupKey) {
            $media = $this->audioMediaIndex()[$lookupKey] ?? null;

            if (is_array($media) && ($media[$key] ?? '') !== '') {
                return $media[$key];
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $track
     * @return array<int, string>
     */
    private function mediaLookupKeys(array $track): array
    {
        $name = (string) (data_get($track, 'name') ?: data_get($track, 'title') ?: '');
        $artist = $this->artists($track);
        $artists = collect(preg_split('#\s*/\s*#', $artist) ?: [])
            ->map(fn (string $value): string => trim($value))
            ->filter()
            ->values();

        $candidates = collect([
            $name,
            $artist.' - '.$name,
            $name.' - '.$artist,
        ]);

        $artists->each(function (string $singleArtist) use ($candidates, $name): void {
            $candidates->push($singleArtist.' - '.$name);
            $candidates->push($name.' - '.$singleArtist);
        });

        return $candidates
            ->map(fn (string $value): string => $this->normalizeMediaKey($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, array{url: string, cover: string}>
     */
    private function audioMediaIndex(): array
    {
        if ($this->audioMediaIndex !== null) {
            return $this->audioMediaIndex;
        }

        $this->audioMediaIndex = [];

        Media::query()
            ->where('type', 'audio')
            ->get(['name', 'path', 'metadata'])
            ->each(function (Media $media): void {
                $url = $this->mediaUrlFromPath((string) $media->path);
                $cover = (string) data_get($media->metadata, 'cover_url', '');

                collect([
                    (string) $media->name,
                    pathinfo((string) $media->path, PATHINFO_FILENAME),
                    pathinfo((string) data_get($media->metadata, 'original_name', ''), PATHINFO_FILENAME),
                    pathinfo((string) data_get($media->metadata, 'stored_name', ''), PATHINFO_FILENAME),
                ])
                    ->map(fn (string $value): string => $this->normalizeMediaKey($value))
                    ->filter()
                    ->unique()
                    ->each(function (string $key) use ($url, $cover): void {
                        $this->audioMediaIndex[$key] ??= [
                            'url' => $url,
                            'cover' => $cover,
                        ];
                    });
            });

        return $this->audioMediaIndex;
    }

    private function normalizeMediaKey(string $value): string
    {
        $value = Str::lower(trim($value));
        $value = preg_replace('#\.(mp3|wav|ogg|oga|m4a|aac|flac)$#i', '', $value) ?? $value;
        $value = preg_replace('#[\[\(（【].*?[\]\)）】]#u', '', $value) ?? $value;
        $value = str_replace(['_', '–', '—', '－'], [' ', '-', '-', '-'], $value);
        $value = preg_replace('#\s*-\s*#u', ' - ', $value) ?? $value;
        $value = preg_replace('#\s+#u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function mediaUrlFromPath(string $path): string
    {
        $relativePath = implode('/', array_map('rawurlencode', explode('/', $this->mediaRelativePath($path))));

        return url('/'.$this->mediaRoot().'/'.$relativePath);
    }

    private function mediaRelativePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path), '/');
        $root = $this->mediaRoot();

        if ($root !== '' && Str::startsWith($path, $root.'/')) {
            return substr($path, strlen($root) + 1);
        }

        return $path;
    }

    private function sameSiteUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        $allowedHosts = array_filter([
            parse_url((string) config('app.url'), PHP_URL_HOST),
            request()?->getHost(),
        ]);

        if (! $host || ! in_array($host, $allowedHosts, true)) {
            return '';
        }

        $path = $this->cleanPath((string) parse_url($url, PHP_URL_PATH));
        $query = parse_url($url, PHP_URL_QUERY);

        return url('/'.$path.($query ? '?'.$query : ''));
    }

    private function cleanPath(string $path): string
    {
        $path = rawurldecode($path);
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#\.\.+#', '', $path) ?? '';
        $path = preg_replace('#/+#', '/', $path) ?? '';

        return trim($path, '/');
    }

    private function mediaRoot(): string
    {
        return trim((string) config('zfy.editor.media.storage_root', 'media'), '/') ?: 'media';
    }

    private function playlistPath(string $id): string
    {
        return 'netease/playlists/'.$id.'.json';
    }

    private function songPath(string $id): string
    {
        return 'netease/songs/'.$id.'.json';
    }
}
