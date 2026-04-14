<?php

if (!function_exists('cd_render_theme_head')) {
    function cd_render_theme_head(string $assetPrefix, string $projectRoot): void
    {
        $href = $assetPrefix . 'Css/ModoEscuro.css?v=' . filemtime($projectRoot . '/Css/ModoEscuro.css');
        echo '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . PHP_EOL;
    }

    function cd_render_theme_toggle(string $assetPrefix): void
    {
        $sunIcon = htmlspecialchars($assetPrefix . 'Icons/sol.png', ENT_QUOTES, 'UTF-8');
        $moonIcon = htmlspecialchars($assetPrefix . 'Icons/Lua.png', ENT_QUOTES, 'UTF-8');

        echo <<<HTML
<div class="controlo" data-theme="escuro">
  <button class="tema-btn" id="claro-btn" aria-label="Modo claro" aria-pressed="false">
    <img src="{$sunIcon}" alt="Claro">
  </button>
  <button class="tema-btn active" id="escuro-btn" aria-label="Modo escuro" aria-pressed="true">
    <img src="{$moonIcon}" alt="Escuro">
  </button>
</div>

HTML;
    }

    function cd_render_theme_script(string $assetPrefix, string $projectRoot): void
    {
        $src = $assetPrefix . 'Js/ModoEscuro.js?v=' . filemtime($projectRoot . '/Js/ModoEscuro.js');
        echo '<script src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"></script>' . PHP_EOL;
    }
}
