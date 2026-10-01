/**
 * RetroGameEmulator - 后台编辑器工具栏按钮 + 弹窗
 */
(function () {
    'use strict';

    // 等待编辑器工具栏就绪
    function waitForToolbar(callback) {
        var timer = setInterval(function () {
            // Typecho 编辑器工具栏 .wmd-button-row
            var toolbar = document.getElementById('wmd-button-row');
            if (toolbar) {
                clearInterval(timer);
                callback(toolbar);
            }
        }, 200);
        // 10 秒超时
        setTimeout(function () { clearInterval(timer); }, 10000);
    }

    waitForToolbar(function (toolbar) {
        // 创建工具栏按钮
        var btn = document.createElement('li');
        btn.className = 'wmd-button';
        btn.id = 'wmd-emulator-button';
        btn.title = '插入模拟器';
        btn.innerHTML = '<span style="font-size:14px;line-height:20px;">🎮</span>';
        toolbar.appendChild(btn);

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            showModal();
        });
    });

    // =========================================================
    //  弹窗
    // =========================================================
    function showModal() {
        // 若已存在则移除
        var old = document.getElementById('ejs-modal-overlay');
        if (old) old.remove();

        var overlay = document.createElement('div');
        overlay.id = 'ejs-modal-overlay';
        overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:99999;display:flex;align-items:center;justify-content:center;';

        var modal = document.createElement('div');
        modal.style.cssText = 'background:#fff;border-radius:8px;padding:24px 28px;width:480px;max-width:90vw;box-shadow:0 4px 24px rgba(0,0,0,0.2);font-family:Arial,sans-serif;';

        modal.innerHTML = [
            '<h3 style="margin:0 0 16px;font-size:18px;color:#333;">插入街机模拟器</h3>',
            '<div style="margin-bottom:12px;">',
            '  <label style="display:block;margin-bottom:4px;font-size:13px;color:#555;">ROM 地址 (sourceUrl)</label>',
            '  <input id="ejs-sourceUrl" type="text" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:13px;box-sizing:border-box;" placeholder="https://example.com/roms/game.zip" />',
            '</div>',
            '<div style="margin-bottom:12px;">',
            '  <label style="display:block;margin-bottom:4px;font-size:13px;color:#555;">ROM 文件名 (fileName)</label>',
            '  <input id="ejs-fileName" type="text" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:13px;box-sizing:border-box;" placeholder="kovsh.zip" />',
            '</div>',
            '<div style="margin-bottom:12px;">',
            '  <label style="display:block;margin-bottom:4px;font-size:13px;color:#555;">模拟器核心 (core)</label>',
            '  <select id="ejs-core" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:13px;box-sizing:border-box;background:#fff;height:37px;">',
            '    <optgroup label="街机 / 主机">',
            '      <option value="arcade">arcade - 街机 (FBNeo)</option>',
            '      <option value="mame2003">mame2003 - 街机 (MAME 2003)</option>',
            '      <option value="nes">nes - 红白机 (FC)</option>',
            '      <option value="snes">snes - 超级任天堂 (SFC)</option>',
            '      <option value="n64">n64 - 任天堂64</option>',
            '      <option value="nds">nds - 任天堂DS</option>',
            '      <option value="gba">gba - Game Boy Advance</option>',
            '      <option value="gb">gb - Game Boy</option>',
            '      <option value="psx">psx - PlayStation</option>',
            '      <option value="psp">psp - PSP</option>',
            '      <option value="3ds">3ds - 任天堂3DS</option>',
            '      <option value="segaMS">segaMS - Sega Master System</option>',
            '      <option value="segaMD">segaMD - Sega Mega Drive</option>',
            '      <option value="segaGG">segaGG - Sega Game Gear</option>',
            '      <option value="segaCD">segaCD - Sega CD</option>',
            '      <option value="sega32x">sega32x - Sega 32X</option>',
            '      <option value="segaSaturn">segaSaturn - Sega Saturn</option>',
            '    </optgroup>',
            '    <optgroup label="Atari">',
            '      <option value="atari2600">atari2600 - Atari 2600</option>',
            '      <option value="atari7800">atari7800 - Atari 7800</option>',
            '      <option value="a5200">a5200 - Atari 5200</option>',
            '      <option value="lynx">lynx - Atari Lynx</option>',
            '      <option value="jaguar">jaguar - Atari Jaguar</option>',
            '    </optgroup>',
            '    <optgroup label="Commodore">',
            '      <option value="c64">c64 - Commodore 64</option>',
            '      <option value="c128">c128 - Commodore 128</option>',
            '      <option value="vic20">vic20 - VIC-20</option>',
            '      <option value="pet">pet - PET</option>',
            '      <option value="plus4">plus4 - PLUS/4</option>',
            '      <option value="amiga">amiga - Amiga</option>',
            '    </optgroup>',
            '    <optgroup label="其他">',
            '      <option value="vb">vb - Virtual Boy</option>',
            '      <option value="3do">3do - 3DO</option>',
            '      <option value="coleco">coleco - ColecoVision</option>',
            '      <option value="pce">pce - PC Engine</option>',
            '      <option value="pcfx">pcfx - PC-FX</option>',
            '      <option value="ngp">ngp - Neo Geo Pocket</option>',
            '      <option value="ws">ws - WonderSwan</option>',
            '      <option value="dos">dos - DOSBox</option>',
            '    </optgroup>',
            '  </select>',
            '</div>',
            '<button id="ejs-advanced-btn" style="margin-bottom:12px;padding:6px 12px;border:none;border-radius:4px;background:#f5f7fa;color:#666;cursor:pointer;font-size:12px;text-decoration:none;">⚙️ 展开高级选项</button>',
            '<div id="ejs-advanced-panel" style="display:none;margin-bottom:16px;padding:12px;background:#f5f7fa;border-radius:6px;">',
            '<div style="margin-bottom:12px;">',
            '  <label style="display:block;margin-bottom:4px;font-size:13px;color:#555;">自定义核心 URL (coreUrl，可选)</label>',
            '  <input id="ejs-coreUrl" type="text" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:13px;box-sizing:border-box;" placeholder="留空使用默认核心" />',
            '</div>',
            '<div style="margin-bottom:12px;">',
            '  <label style="display:block;margin-bottom:4px;font-size:13px;color:#555;">BIOS 地址 (biosUrl，可选)</label>',
            '  <input id="ejs-biosUrl" type="text" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:13px;box-sizing:border-box;" placeholder="留空则不加载 BIOS" />',
            '</div>',
            '<div style="margin-bottom:12px;">',
            '  <label style="display:block;margin-bottom:4px;font-size:13px;color:#555;">BIOS 文件名 (biosName，可选)</label>',
            '  <input id="ejs-biosName" type="text" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:13px;box-sizing:border-box;" placeholder="如 pgm.zip，用于 fetch 拦截匹配" />',
            '</div>',
            '<div style="margin-bottom:12px;">',
            '  <label style="display:block;margin-bottom:4px;font-size:13px;color:#555;">补丁 (patchRoms，可选，支持 IPS/BPS/UPS)</label>',
            '  <textarea id="ejs-patchRoms" rows="3" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:13px;box-sizing:border-box;resize:vertical;" placeholder="每行一个：文件名|下载地址&#10;如：patch.ips|https://example.com/patch.ips&#10;如：hack.bps|https://example.com/hack.bps"></textarea>',
            '  <div style="margin-top:4px;font-size:12px;color:#888;">所有补丁将按顺序应用到ROM，每行一个，用 | 分隔文件名和地址</div>',
            '</div>',
            '<div style="margin-bottom:12px;">',
            '  <label style="display:block;margin-bottom:4px;font-size:13px;color:#555;">RomData 文件 (romData，可选，FBNeo Plus 核心)</label>',
            '  <textarea id="ejs-romData" rows="3" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:13px;box-sizing:border-box;resize:vertical;" placeholder="每行一个：文件名|下载地址&#10;如：dino.dat|https://example.com/dino.dat&#10;如：kovsh.ips|https://example.com/kovsh.ips"></textarea>',
            '  <div style="margin-top:4px;font-size:12px;color:#888;">FBNeo Plus 核心的 .dat 描述文件和 .ips 补丁，写入虚拟文件系统的 /fbneo/ips/游戏名/ 目录，核心选项中可切换启用</div>',
            '</div>',
            '</div>',
            '<div style="text-align:right;">',
            '  <button id="ejs-cancel" style="padding:8px 20px;margin-right:8px;border:1px solid #ddd;border-radius:4px;background:#fff;cursor:pointer;font-size:13px;">取消</button>',
            '  <button id="ejs-ok" style="padding:8px 20px;border:none;border-radius:4px;background:#06B6D4;color:#fff;cursor:pointer;font-size:13px;">确定</button>',
            '</div>'
        ].join('');

        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        // 关闭
        document.getElementById('ejs-cancel').addEventListener('click', function () {
            overlay.remove();
        });


        // 高级选项切换
        var advancedBtn = document.getElementById('ejs-advanced-btn');
        var advancedPanel = document.getElementById('ejs-advanced-panel');
        advancedBtn.addEventListener('click', function () {
            if (advancedPanel.style.display === 'none') {
                advancedPanel.style.display = 'block';
                advancedBtn.innerHTML = '⚙️ 收起高级选项';
            } else {
                advancedPanel.style.display = 'none';
                advancedBtn.innerHTML = '⚙️ 展开高级选项';
            }
        });

        // 确定
        document.getElementById('ejs-ok').addEventListener('click', function () {
            var sourceUrl = document.getElementById('ejs-sourceUrl').value.trim();
            var fileName  = document.getElementById('ejs-fileName').value.trim();
            var core      = document.getElementById('ejs-core').value.trim();
            var coreUrl   = document.getElementById('ejs-coreUrl').value.trim();
            var patchRoms = document.getElementById('ejs-patchRoms').value.trim();
            var romData   = document.getElementById('ejs-romData').value.trim();
            var biosUrl   = document.getElementById('ejs-biosUrl').value.trim();
            var biosName  = document.getElementById('ejs-biosName').value.trim();

            if (!sourceUrl || !fileName || !core) {
                alert('ROM地址、文件名和核心为必填项');
                return;
            }

            // 将所有参数 JSON 编码后 Base64 编码，避免特殊字符被 Markdown 干扰
            var config = {
                sourceUrl: sourceUrl,
                fileName: fileName,
                core: core,
                coreUrl: coreUrl,
                patchRoms: patchRoms,
                romData: romData,
                biosUrl: biosUrl,
                biosName: biosName
            };
            var encoded = btoa(unescape(encodeURIComponent(JSON.stringify(config))));
            var shortcode = '[emulator data="' + encoded + '"]';

            insertToEditor(shortcode);
            overlay.remove();
        });
    }

    // =========================================================
    //  插入短代码到编辑器
    // =========================================================
    function insertToEditor(text) {
        var textarea = document.getElementById('text');
        if (!textarea) return;

        var start = textarea.selectionStart;
        var end   = textarea.selectionEnd;
        var val   = textarea.value;

        textarea.value = val.substring(0, start) + text + val.substring(end);
        var newPos = start + text.length;
        textarea.setSelectionRange(newPos, newPos);
        textarea.focus();

        // 触发 input 事件
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }
})();
