<?php

namespace TypechoPlugin\RetroGameEmulator;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Text;
use Typecho\Plugin as Typecho_Plugin;
use Utils\Helper;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 街机模拟器 - 在文章中嵌入EmulatorJS街机游戏模拟器
 *
 * @package RetroGameEmulator
 * @author xiao
 * @version 1.0.3
 * @link https://cao.qzz.io
 */
class Plugin implements PluginInterface
{
    /**
     * 激活插件方法
     */
    public static function activate()
    {
        // 后台编辑页工具栏 & 底部脚本
        \Typecho\Plugin::factory('admin/write-post.php')->bottom = __CLASS__ . '::renderEditorScript';
        \Typecho\Plugin::factory('admin/write-page.php')->bottom = __CLASS__ . '::renderEditorScript';

        // 前台内容过滤，处理 [emulator] 短代码
        \Typecho\Plugin::factory('Widget_Abstract_Contents')->content = array(__CLASS__, 'parseContent');
        \Typecho\Plugin::factory('Widget_Abstract_Contents')->excerpt = array(__CLASS__, 'parseExcerpt');

        // 前台 header 输出模拟器依赖
        \Typecho\Plugin::factory('Widget_Archive')->header = array(__CLASS__, 'outputHeader');

        return _t('RetroGameEmulator 插件已激活，可在编辑器工具栏插入模拟器短代码。');
    }

    /**
     * 禁用插件方法
     */
    public static function deactivate()
    {
        return _t('插件已禁用');
    }

    /**
     * 获取插件配置面板
     */
    public static function config(Form $form)
    {
        $enableNetplay = new \Typecho\Widget\Helper\Form\Element\Radio(
            'enableNetplay',
            array('1' => _t('启用'), '0' => _t('禁用')),
            '0',
            _t('联机模式'),
            _t('启用后模拟器将出现联机交互控件')
        );
        $form->addInput($enableNetplay);

        echo '<div style="margin:15px 0;padding:10px;background:#d4edda;border:1px solid #c3e6cb;border-radius:4px;color:#155724;">';
        echo '<p style="margin:10px 0;">在文章编辑器工具栏点击「插入模拟器」按钮，填写 ROM 信息后即可插入短代码。</p>';
        echo '<p>短代码格式：<code>[emulator data="Base64编码的JSON配置"]</code></p>';
        echo '<p>前台将自动渲染为 EmulatorJS 街机模拟器。</p>';
        echo '<p>参数通过编辑器弹窗自动编码，无需手动填写。附加 ROM 每行一个，格式：文件名$下载地址。</p>';
        echo '<p>联机模式需要配置信令服务器和 STUN/TURN 服务器，然后修改插件 play.html 中联机配置部分代码。</p>';
        echo '<p>完整的核心列表可查阅<a href="https://emulatorjs.org/docs4devs/cores/">官方核心文档</a></p>';
        echo '</div>';
    }

    /**
     * 个人用户的配置面板
     */
    public static function personalConfig(Form $form)
    {
    }

    // =========================================================
    //  后台：编辑器工具栏脚本
    // =========================================================

    /**
     * 渲染编辑器底部脚本（工具栏按钮 + 弹窗）
     */
    public static function renderEditorScript()
    {
        $pluginUrl = Helper::options()->pluginUrl . '/RetroGameEmulator';
        echo '<script type="text/javascript" src="' . $pluginUrl . '/editor.js"></script>';
    }

    // =========================================================
    //  前台：短代码解析
    // =========================================================

    /**
     * 解析 [emulator] 短代码
     *
     * @param string $content 内容
     * @param array|null $htmlStore 传入数组时启用保护模式：生成的模拟器 HTML
     *                               以占位符形式存在，避免被后续 Markdown/AutoP
     *                               转换破坏（自动链接会把属性值中的 URL 转成 <a>）
     */
    private static function parseShortCode(string $content, ?array &$htmlStore = null): string
    {
        $pattern = '/\[emulator\s+data="([A-Za-z0-9+\/=]+)"\s*\]/s';

        $render = function ($matches) {
            $json = base64_decode($matches[1], true);
            if ($json === false) return '';
            $config = json_decode($json, true);
            if (!is_array($config)) return '';

            $sourceUrl = $config['sourceUrl'] ?? '';
            $fileName  = $config['fileName'] ?? '';
            $core      = $config['core'] ?? 'arcade';
            $coreUrl   = $config['coreUrl'] ?? '';
            $patchRoms = $config['patchRoms'] ?? '';
            $romData   = $config['romData'] ?? '';
            $biosUrl   = $config['biosUrl'] ?? '';
            $biosName  = $config['biosName'] ?? '';

            if (!$sourceUrl || !$fileName) return '';

            $uid = 'ejs_' . substr(md5($fileName . $sourceUrl), 0, 8);

            $options = Helper::options();
            $enableNetplay = $options->plugin('RetroGameEmulator')->enableNetplay ?? '0';

            $html  = '<div class="ejs-container" id="' . $uid . '"';
            $html .= ' data-source-url="' . htmlspecialchars($sourceUrl) . '"';
            $html .= ' data-file-name="' . htmlspecialchars($fileName) . '"';
            $html .= ' data-core="' . htmlspecialchars($core) . '"';
            $html .= ' data-core-url="' . htmlspecialchars($coreUrl) . '"';
            $html .= ' data-patch-roms="' . htmlspecialchars($patchRoms) . '"';
            $html .= ' data-rom-data="' . htmlspecialchars($romData) . '"';
            $html .= ' data-bios-url="' . htmlspecialchars($biosUrl) . '"';
            $html .= ' data-bios-name="' . htmlspecialchars($biosName) . '"';
            $html .= ' data-netplay="' . $enableNetplay . '"';
            $html .= '>';
            $html .= '<div class="ejs-loading"><div class="ejs-spinner"></div><div>正在加载游戏...</div></div>';
            $html .= '<div class="ejs-game"></div>';
            $html .= '</div>';

            return $html;
        };

        if ($htmlStore === null) {
            // 直接替换模式（保留原行为）
            return preg_replace_callback($pattern, $render, $content);
        }

        // 保护模式：生成的模拟器 HTML 以占位符形式存在
        $htmlStore = [];
        return preg_replace_callback(
            $pattern,
            function ($m) use ($render, &$htmlStore) {
                $html = $render($m);
                if ($html === '') {
                    return '';
                }
                $key = "\x01RGH" . count($htmlStore) . "\x01";
                $htmlStore[$key] = $html;
                return $key;
            },
            $content
        );
    }

    /**
     * 还原被保护的模拟器 HTML
     *
     * 独立成段的占位符会被 Markdown/AutoP 包裹 <p>，还原后会产生
     * <p><div>...</div></p> 的非法嵌套，这里先剥离再还原。
     */
    private static function restorePlayerHtml(string $content, array $htmlStore): string
    {
        if (empty($htmlStore)) {
            return $content;
        }
        $content = preg_replace('/<p>\s*((?:\x01RGH\d+\x01\s*)+)<\/p>/s', '$1', $content);
        return str_replace(array_keys($htmlStore), array_values($htmlStore), $content);
    }

    /**
     * 提取代码块内容为占位符，避免代码块内的短代码被错误解析为播放器/模拟器
     *
     * 文章代码块中常会演示短代码的用法（如教程中写 [emulator data="..."] 示例），
     * 这些文字应当原样显示，而不是被解析成播放器/模拟器。
     * 与 HyperDown 解析器的行为保持一致，覆盖三种代码形式：
     * 1. ```/~~~ 围栏代码块（markdown 原文，含未闭合围栏）
     * 2. 行内反引号代码（markdown 原文）
     * 3. <pre>...</pre> 与 <code>...</code>（插件链中前一插件已完成 Markdown
     *    转换后的已渲染 HTML，本插件后执行时内容已是该形式）
     */
    private static function protectCodeBlocks(string $content, array &$store): string
    {
        $store = [];
        $placeholder = function (string $text) use (&$store): string {
            // \x01 控制字符作为边界，不会与正文冲突
            $key = "\x01CB" . count($store) . "\x01";
            $store[$key] = $text;
            return $key;
        };

        // 3a. <pre>...</pre> 块（先提取，内部可能嵌套 <code>）
        $content = preg_replace_callback(
            '/<pre[^>]*>.*?<\/pre>/is',
            function ($m) use ($placeholder) {
                return $placeholder($m[0]);
            },
            $content
        );

        // 1. ```/~~~ 围栏代码块：逐行状态机，围栏起止字符必须完全一致（与 HyperDown 一致）
        $lines = explode("\n", $content);
        $out = [];
        $buffer = [];
        $fence = null;
        foreach ($lines as $line) {
            if ($fence !== null) {
                $buffer[] = $line;
                if (preg_match("/^(\s*)(~{3,}|`{3,})([^`~]*)$/", $line, $m) && $m[2] === $fence) {
                    $out[] = $placeholder(implode("\n", $buffer));
                    $buffer = [];
                    $fence = null;
                }
                continue;
            }
            if (preg_match("/^(\s*)(~{3,}|`{3,})([^`~]*)$/", $line, $m)) {
                $fence = $m[2];
                $buffer = [$line];
                continue;
            }
            $out[] = $line;
        }
        if ($fence !== null) {
            // 未闭合的围栏：到文末均视为代码块，与 HyperDown 行为一致
            $out[] = $placeholder(implode("\n", $buffer));
        }
        $content = implode("\n", $out);

        // 2. 行内反引号代码（正则与 HyperDown 的行内代码匹配一致）
        $content = preg_replace_callback(
            '/(^|[^\\\\])(`+)(.+?)\2/s',
            function ($m) use ($placeholder) {
                return $m[1] . $placeholder($m[2] . $m[3] . $m[2]);
            },
            $content
        );

        // 3b. <code>...</code>（已渲染 HTML 的行内代码）
        $content = preg_replace_callback(
            '/<code[^>]*>.*?<\/code>/is',
            function ($m) use ($placeholder) {
                return $placeholder($m[0]);
            },
            $content
        );

        return $content;
    }

    /**
     * 还原被保护的代码块
     */
    private static function restoreCodeBlocks(string $content, array $store): string
    {
        if (empty($store)) {
            return $content;
        }
        return str_replace(array_keys($store), array_values($store), $content);
    }

    /**
     * 保护剩余的短代码（其他插件尚未处理的），避免被 Markdown 解析破坏
     *
     * 当本插件是插件链中第一个执行 Markdown 转换的插件时，其他插件的短代码
     * （如 VideoCollector 的 [play]）还以原文形式存在，若直接交给 Markdown 解析，
     * 短代码内的 URL、$、换行等符号会被错误解析（如 URL 变 <a>、换行变 <br>）。
     * 这里用占位符把它们先替换出来，转换完成后再还原。
     *
     * 匹配两种形式：[xxx]...[/xxx]（带闭合标签）与 [xxx 属性="值"]（自闭合带属性）。
     *
     * 正则刻意收紧以避免误伤 Markdown 链接语法（如 [IP2Region 官方项目][1]
     * 这类带空格文本的引用链接、[文本](url) 行内链接、![图片 说明](url)）：
     * - 闭合形式要求结束标签名与开始标签名完全一致（反向引用 \2），
     *   且开始标签后不能紧跟 "("（排除行内链接 [文本](url)）；
     * - 自闭合形式要求内容必须是 属性名="值" 语法（纯文字如 [IP2Region 官方项目]
     *   不匹配），且结束后不能紧跟 "(" 或 "["（排除行内/引用链接的后续部分）。
     */
    private static function protectRemainingShortCodes(string $content, array &$store): string
    {
        $store = [];
        return preg_replace_callback(
            '/\[(!?)([a-z][a-z0-9_]*)[^\]]*\](?!\().*?\[\/\2\]|\[!?[a-z][a-z0-9_]*(?:\s+[a-z][a-z0-9_-]*\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s\]]+))+\s*\](?![\(\[])/is',
            function ($m) use (&$store) {
                // 使用控制字符 \x01 作为占位符边界，确保不会与正文冲突，
                // 且不会被 htmlspecialchars 转义（即使占位符落入代码块也能还原）
                $key = "\x01SCPH" . count($store) . "\x01";
                $store[$key] = $m[0];
                return $key;
            },
            $content
        );
    }

    /**
     * 还原被保护的短代码，并去除 Markdown 自动添加的 <p> 包裹
     *
     * 独占一行的短代码占位符会被 Markdown 包裹成 <p>占位符</p>，还原后形成
     * <p>[emulator ...]</p>，再替换为块级 <div> 会产生 <p><div></div></p> 的非法嵌套。
     * 这里在还原时把这种 <p> 包裹去掉。
     */
    private static function restoreRemainingShortCodes(string $content, array $store): string
    {
        if (empty($store)) {
            return $content;
        }
        $content = str_replace(array_keys($store), array_values($store), $content);
        $content = preg_replace(
            '/<p>\s*(\[(!?)([a-z][a-z0-9_]*)[^\]]*\](?!\().*?\[\/\3\])\s*<\/p>/is',
            '$1',
            $content
        );
        $content = preg_replace(
            '/<p>\s*(\[!?[a-z][a-z0-9_]*(?:\s+[a-z][a-z0-9_-]*\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s\]]+))+\s*\])\s*<\/p>/is',
            '$1',
            $content
        );
        return $content;
    }

    /**
     * 处理文章内容
     */
    public static function parseContent(string $content, $widget, ?string $lastResult): string
    {
        $content = $lastResult ?? $content;

        // 处理自己的 [emulator] 短代码。
        // 先提取代码块为占位符，避免代码块内演示用的短代码被错误解析为模拟器
        $codeStore = [];
        $content = self::protectCodeBlocks($content, $codeStore);
        // 生成的模拟器 HTML 也以占位符存在，避免被 Markdown/AutoP 破坏
        // （自动链接会把属性值中的 URL 转成 <a> 标签）
        $htmlStore = [];
        $content = self::parseShortCode($content, $htmlStore);
        $content = self::restoreCodeBlocks($content, $codeStore);

        // 用 widget 标记确保 Markdown/AutoP 转换在整个插件链中只执行一次。
        // 替代原先基于 isRenderedHtml 的判断：该判断会误把代码块内的 HTML 标签
        // （如 ```html 代码示例中的 <div>/<p>）当成已渲染 HTML，导致跳过转换、源码泄漏。
        if (!isset($widget->__pluginShortcodeContentRendered)) {
            $widget->__pluginShortcodeContentRendered = true;

            // 先保护其他插件尚未处理的短代码，避免被 Markdown/AutoP 解析破坏
            // （Markdown 会把短代码内 URL 解析为 <a>、AutoP 会把换行转为 <br> 等）
            $store = [];
            $content = self::protectRemainingShortCodes($content, $store);
            if ($widget->isMarkdown) {
                $content = \Utils\Markdown::convert($content);
            } else {
                static $parser;
                if (empty($parser)) {
                    $parser = new \Utils\AutoP();
                }
                $content = $parser->parse($content);
            }
            $content = self::restoreRemainingShortCodes($content, $store);
        }

        // 无论本插件是否执行了 Markdown/AutoP 转换，都还原自己生成的模拟器 HTML
        return self::restorePlayerHtml($content, $htmlStore);
    }

    /**
     * 处理文章摘要（移除模拟器）
     */
    public static function parseExcerpt(string $excerpt, $widget, ?string $lastResult): string
    {
        $excerpt = $lastResult ?? $excerpt;

        // 注意：excerpt 钩子收到的输入是已经过 content 插件链渲染的 HTML
        // （见 Contents::___excerpt(): filter('excerpt', $this->content, $this)），
        // 因此这里绝不能再做 Markdown/AutoP 转换，否则会破坏已渲染的 HTML。

        // 移除摘要中的模拟器：未渲染的短代码原文 + 已渲染的模拟器容器
        // （同样先保护代码块，避免误删代码块内的示例）
        $codeStore = [];
        $excerpt = self::protectCodeBlocks($excerpt, $codeStore);
        $excerpt = preg_replace('/\[emulator\s+.*?\]/s', '', $excerpt);
        $excerpt = self::restoreCodeBlocks($excerpt, $codeStore);

        // 已渲染的模拟器容器（ejs-container 内嵌 ejs-loading 与 ejs-game）
        $excerpt = preg_replace('/<div class="ejs-container".*?<div class="ejs-game"><\/div>\s*<\/div>/s', '', $excerpt);

        return $excerpt;
    }

    // =========================================================
    //  前台：输出 CSS / JS 依赖
    // =========================================================

    /**
     * 前台 header 输出样式和脚本
     */
    public static function outputHeader(): void
    {
        $pluginUrl = Helper::options()->pluginUrl . '/RetroGameEmulator';
        echo '<link rel="stylesheet" href="' . $pluginUrl . '/emulator.css" type="text/css" />';
        // 将 play.html 的路径注入全局变量，供 emulator.js 读取
        echo '<script>window.__EJS_PLAY_HTML__="' . $pluginUrl . '/play.html";</script>';
        echo '<script type="text/javascript" src="' . $pluginUrl . '/emulator.js"></script>';
    }
}
