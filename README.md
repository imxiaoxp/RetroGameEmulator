# RetroGameEmulator - 街机模拟器插件

在 Typecho 文章中嵌入 [EmulatorJS](https://emulatorjs.org) 街机 / 主机游戏模拟器，后台编辑器一键生成短代码，前台自动渲染为可玩的网页模拟器。

- **版本**：1.0.3
- **作者**：xiao
- **兼容**：Typecho 1.2 / 1.3

## 功能特性

- **编辑器按钮**：Markdown 工具栏追加「🎮 插入模拟器」按钮，弹窗填写 ROM 信息后自动生成短代码，参数 Base64 编码避免被 Markdown 干扰
- **30+ 模拟器核心**：街机（FBNeo / MAME 2003）、任天堂（FC / SFC / N64 / NDS / GBA / GB / 3DS）、索尼（PSX / PSP）、世嘉全系列、Atari、Commodore、PC Engine、DOSBox 等
- **高级选项**：自定义核心 URL、BIOS 加载、IPS/BPS/UPS 补丁（按顺序应用到 ROM）、RomData 文件（FBNeo Plus 的 `.dat` 描述 + `.ips` 补丁写入虚拟文件系统）
- **前台渲染**：`[emulator]` 短代码渲染为 EmulatorJS 容器，通过 iframe 加载 `play.html` 运行模拟器（EmulatorJS 4.3.0-pre，CDN 加载）
- **ROM 本地注入**：`play.html` 并行下载 ROM / BIOS / 核心 / 补丁，通过 fetch / XHR 拦截注入本地 Blob，ROM 无需上传到 EmulatorJS CDN
- **摘要处理**：列表页摘要中自动移除模拟器（短代码原文与已渲染容器）
- **PJAX 兼容**：MutationObserver 监听 DOM 变化，PJAX 主题换页后自动初始化新容器；同时暴露 `window.initRetroGameEmulator()` 供主题手动调用
- **插件链安全**：生成的 HTML 以占位符保护，避免被 Markdown / AutoP 破坏；代码块内演示的短代码原样显示不解析；同时保护其他插件尚未处理的短代码

## 安装

1. 将 `RetroGameEmulator` 文件夹复制到 `usr/plugins/` 目录
2. 后台「控制台 → 插件」中启用

## 插件配置

| 配置项 | 默认值 | 说明 |
| --- | --- | --- |
| 联机模式 | 禁用 | 启用后模拟器出现联机交互控件，需自行配置信令服务器和 STUN/TURN 服务器，并修改 `play.html` 中联机配置部分代码 |

## 使用方法

1. 编辑文章 / 页面时点击工具栏「🎮 插入模拟器」
2. 填写必填项：
   - **ROM 地址**：ROM 压缩包的下载地址（如 `https://example.com/roms/game.zip`）
   - **ROM 文件名**：如 `kovsh.zip`（用于 fetch 拦截匹配，需与 URL 文件名一致）
   - **模拟器核心**：下拉选择，如 `arcade - 街机 (FBNeo)`；完整核心列表见[官方核心文档](https://emulatorjs.org/docs4devs/cores/)
3. 按需展开高级选项（自定义核心 / BIOS / 补丁 / RomData，每行一个，格式 `文件名|下载地址`）
4. 点击「确定」，编辑器光标处插入短代码：

   ```
   [emulator data="eyJzb3VyY2VVcmwiOiJodHRwczovLy4uLiJ9"]
   ```

前台浏览文章时，短代码位置会渲染为模拟器，加载 ROM 后即可游玩。

## 短代码格式

```
[emulator data="{Base64 编码的 JSON 配置}"]
```

JSON 配置字段：

| 字段 | 必填 | 说明 |
| --- | --- | --- |
| sourceUrl | 是 | ROM 下载地址 |
| fileName | 是 | ROM 文件名 |
| core | 否 | 模拟器核心，默认 `arcade` |
| coreUrl | 否 | 自定义核心 `.data` 文件地址（fetch 拦截替换） |
| biosUrl | 否 | BIOS 下载地址 |
| biosName | 否 | BIOS 文件名（省略时从 URL 自动提取） |
| patchRoms | 否 | IPS/BPS/UPS 补丁列表，每行 `文件名\|地址`，按顺序应用 |
| romData | 否 | FBNeo Plus 的 `.dat` / `.ips` 文件列表，每行 `文件名\|地址` |

参数通过编辑器弹窗自动编码，一般无需手写。

## 工作原理

```
文章内容 ──content 钩子──▶ [emulator] 短代码渲染为 <div class="ejs-container">
                                  │
前台页面 ──emulator.js──▶ 扫描 .ejs-container，按 data-* 参数拼 URL
                                  │
                                  ▼
                    <iframe src="play.html?sourceUrl=...&core=...">
                                  │
play.html ──▶ 并行下载 ROM/BIOS/核心/补丁 → fetch/XHR 拦截注入本地 Blob
          ──▶ 应用 IPS 补丁 → 设置 EJS_* 全局变量 → 加载 EmulatorJS loader.js
```

- 摘要（excerpt 钩子）中移除模拟器，列表页只显示文字
- EmulatorJS 本体（`loader.js` 及核心数据）从 `cdn.emulatorjs.org/4.3.0-pre` 加载，界面语言固定为中文

## 文件结构

```
RetroGameEmulator/
├── Plugin.php     # 插件主体：短代码解析、内容/摘要钩子、前台依赖输出
├── editor.js      # 后台编辑器按钮与弹窗
├── emulator.js    # 前台容器扫描、iframe 创建、PJAX 适配
├── emulator.css   # 前台容器与加载动画样式
├── play.html      # 模拟器运行页：ROM 下载、Blob 注入、补丁、联机配置
└── README.md
```

## 注意事项

- ROM 文件需托管在允许跨域（CORS）的地址上，`play.html` 用 fetch 下载（`mode: cors`）
- 联机模式默认信令服务器为站点自身地址，ICE 使用 Google STUN + OpenRelay TURN 公共服务，正式使用请替换 `play.html` 中的 `ICE_SERVERS` 并自建信令服务
- 主题需在 `header.php` 调用 `$this->header()`、`footer.php` 调用 `$this->footer()`，否则前台依赖不会输出
- 模拟器核心加载依赖公网 CDN，如需离线部署请自行修改 `play.html` 中的 `LOADER_URL` / `DATA_PATH`
