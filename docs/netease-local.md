# 网易云播放器本地数据

播放器不请求第三方中转音乐 API。歌单和单曲优先读取本地 JSON、本地媒体库；没有本地音频时，播放流会回到网易云官方音频源。

## 歌单

歌单文件放在：

```text
storage/app/private/netease/playlists/{歌单ID}.json
```

也可以放内置数据：

```text
resources/data/netease/playlists/{歌单ID}.json
```

示例：

```json
{
  "name": "本地歌单",
  "audio": [
    {
      "id": "3316941396",
      "name": "TOUCH",
      "artist": "當山みれい / 刘炫廷",
      "url": "editor/files/2026/05/touch.mp3",
      "cover": "editor/images/2026/05/touch.jpg",
      "lrc": "editor/files/2026/05/touch.lrc"
    }
  ]
}
```

## 单曲

单曲文件放在：

```text
storage/app/private/netease/songs/{歌曲ID}.json
```

示例：

```json
{
  "id": "3316941396",
  "title": "TOUCH",
  "author": "當山みれい",
  "src": "/media/editor/files/2026/05/touch.mp3",
  "pic": "/media/editor/images/2026/05/touch.jpg",
  "lyric": "[00:00.00]本地歌词"
}
```

`url`、`cover`、`lrc` 支持媒体库相对路径，例如 `editor/files/song.mp3`，也支持本站路径，例如 `/media/editor/files/song.mp3`。第三方中转 API 地址不会使用。

如果歌单有网易云歌曲 `id`，但没有本地音频，页面会使用本站接口跳转到网易云官方音频源播放。

如果本地歌单只有 `name` 和 `artist`，系统会先尝试按歌单 ID 从网易云官方接口补全歌曲 ID；如果补全失败，页面会显示本地歌单列表但不会播放。

也可以不写 `url`，直接把音频上传到媒体库。系统会按以下名称自动匹配：

```text
歌曲名.mp3
歌手 - 歌曲名.mp3
歌曲名 - 歌手.mp3
```

例如歌单里有 `TOUCH / 當山みれい`，媒体库上传 `TOUCH.mp3` 或 `當山みれい - TOUCH.mp3` 后，这首歌会自动出现在 APlayer 播放列表中。
