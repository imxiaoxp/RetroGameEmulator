/**
 * RetroGameEmulator - 前台模拟器渲染
 */
(function () {
    'use strict';

    // play.html 的路径，由 Plugin.php outputHeader 动态设置到 window.__EJS_PLAY_HTML__
    var PLAY_HTML_URL = window.__EJS_PLAY_HTML__ || '';

    /**
     * 为单个容器创建 iframe
     */
    function bootContainer(container) {
        var sourceUrl = container.getAttribute('data-source-url');
        var fileName  = container.getAttribute('data-file-name');
        var core      = container.getAttribute('data-core');
        var biosUrl    = container.getAttribute('data-bios-url');
        var biosName   = container.getAttribute('data-bios-name');
        var netplay    = container.getAttribute('data-netplay') === '1';
        var coreUrl    = container.getAttribute('data-core-url') || '';
        var patchRoms  = container.getAttribute('data-patch-roms') || '';
        var romData    = container.getAttribute('data-rom-data') || '';

        if (!sourceUrl || !fileName || !PLAY_HTML_URL) return;

        // 构建 iframe URL
        var url = PLAY_HTML_URL
            + '?sourceUrl=' + encodeURIComponent(sourceUrl)
            + '&fileName='  + encodeURIComponent(fileName)
            + '&core='      + encodeURIComponent(core)
            + '&biosUrl='   + encodeURIComponent(biosUrl)
            + '&biosName='  + encodeURIComponent(biosName)
            + '&netplay='   + (netplay ? '1' : '0')
            + '&coreUrl='   + encodeURIComponent(coreUrl)
            + '&patchRoms=' + encodeURIComponent(patchRoms)
            + '&romData='   + encodeURIComponent(romData);

        // 隐藏 loading，创建 iframe
        var loadingDiv = container.querySelector('.ejs-loading');
        if (loadingDiv) loadingDiv.style.display = 'none';

        var gameDiv = container.querySelector('.ejs-game');
        if (!gameDiv) return;

        var iframe = document.createElement('iframe');
        iframe.src = url;
        iframe.style.cssText = 'width:100%;height:100%;border:none;display:block;';
        iframe.allow = 'autoplay; fullscreen; gamepad';
        gameDiv.appendChild(iframe);
    }

    /**
     * 扫描：为未初始化的容器创建 iframe
     */
    function scan() {
        document.querySelectorAll('.ejs-container').forEach(function (c) {
            if (c.getAttribute('data-initialized')) return;
            c.setAttribute('data-initialized', '1');
            bootContainer(c);
        });
    }

    // =========================================================
    //  MutationObserver 监听 DOM 变化（适配 PJAX 等）
    // =========================================================
    var observer = null;

    function startObserver() {
        if (observer) return;
        observer = new MutationObserver(function (mutations) {
            var relevant = false;
            for (var i = 0; i < mutations.length; i++) {
                var m = mutations[i];
                if (m.removedNodes && m.removedNodes.length) { relevant = true; break; }
                if (m.addedNodes && m.addedNodes.length) {
                    for (var j = 0; j < m.addedNodes.length; j++) {
                        var node = m.addedNodes[j];
                        if (node.nodeType !== 1) continue;
                        if (node.classList && node.classList.contains('ejs-container')) { relevant = true; break; }
                        if (node.querySelector && node.querySelector('.ejs-container')) { relevant = true; break; }
                    }
                }
                if (relevant) break;
            }
            if (relevant) scan();
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    // =========================================================
    //  启动
    // =========================================================
    function start() {
        scan();
        startObserver();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }

    window.initRetroGameEmulator = scan;
})();
