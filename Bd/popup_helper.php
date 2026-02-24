<?php
if (!function_exists('cd_popup')) {
    function cd_popup(string $message, string $type = 'info', ?string $redirectUrl = null, int $autoCloseMs = 0): void
    {
        $payload = json_encode([
            'message' => $message,
            'type' => $type,
            'redirect' => $redirectUrl,
            'autoCloseMs' => $autoCloseMs
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            $payload = '{"message":"Erro interno","type":"error","redirect":null,"autoCloseMs":0}';
        }

        $payload = str_replace('</', '<\\/', $payload);

        $script = <<<'JS'
(function(){
  var cfg = __PAYLOAD__;

  var TYPE_CONFIG = {
    success: {
      title: 'Sucesso',
      icon: '&#10003;',
      iconBg: 'linear-gradient(145deg, #2dbb73, #1c8f56)',
      accent: 'rgba(45, 187, 115, 0.5)'
    },
    error: {
      title: 'Erro',
      icon: '!',
      iconBg: 'linear-gradient(145deg, #db5b5b, #ab3434)',
      accent: 'rgba(219, 91, 91, 0.5)'
    },
    info: {
      title: 'Informação',
      icon: 'i',
      iconBg: 'linear-gradient(145deg, #5ea2ff, #356ec4)',
      accent: 'rgba(94, 162, 255, 0.45)'
    }
  };

  var run = function() {
    if (!window.__cdShowPopup) {
      window.__cdShowPopup = function(message, type, onClose, autoCloseMs) {
        type = type || 'info';
        autoCloseMs = Number(autoCloseMs || 0);

        var popup = document.getElementById('cd-global-popup');
        if (!popup) {
          popup = document.createElement('div');
          popup.id = 'cd-global-popup';
          popup.setAttribute('aria-hidden', 'true');
          popup.style.cssText = 'position:fixed;inset:0;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(4,4,4,.62);backdrop-filter:blur(4px);z-index:99999;opacity:0;pointer-events:none;transition:opacity .24s ease';
          popup.innerHTML = '<div id="cd-global-popup-box" role="alertdialog" aria-live="assertive" aria-modal="true" style="position:relative;width:min(92vw,460px);border-radius:18px;border:1px solid rgba(255,255,255,.09);background:linear-gradient(160deg,#1b1b1b,#0f0f0f 72%);box-shadow:0 24px 58px rgba(0,0,0,.62);padding:16px 16px 14px;transform:translateY(18px) scale(.97);opacity:0;transition:transform .26s cubic-bezier(.2,.8,.2,1),opacity .22s ease"><div id="cd-global-popup-accent" style="position:absolute;left:14px;right:14px;top:0;height:3px;border-radius:999px"></div><button id="cd-global-popup-close" type="button" aria-label="Fechar popup" style="position:absolute;top:9px;right:12px;border:0;background:transparent;color:#f5f5f5;cursor:pointer;font-size:24px;line-height:1;padding:0;opacity:.88">&#10005;</button><div style="display:flex;gap:12px;align-items:center;padding-right:34px"><div id="cd-global-popup-icon" style="width:44px;height:44px;border-radius:13px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:20px;box-shadow:0 10px 24px rgba(0,0,0,.34)"></div><div><p id="cd-global-popup-title" style="margin:0 0 2px;color:#fff3d7;font-size:1.03rem;font-weight:700;letter-spacing:.2px"></p><p style="margin:0;color:#b8aa8c;font-size:.78rem;letter-spacing:.45px;text-transform:uppercase">Cantinho Deolinda</p></div></div><p id="cd-global-popup-message" style="color:#f5ead6;font-size:.97rem;line-height:1.55;margin:14px 0 14px;white-space:pre-line"></p><button id="cd-global-popup-btn" type="button" style="display:inline-flex;align-items:center;justify-content:center;min-width:108px;border:0;border-radius:999px;padding:10px 18px;font-weight:700;cursor:pointer;color:#101010;background:linear-gradient(135deg,#f5a623,#ffcf45);box-shadow:0 10px 22px rgba(245,166,35,.35)">Fechar</button></div>';
          document.body.appendChild(popup);
        }

        var box = document.getElementById('cd-global-popup-box');
        var accent = document.getElementById('cd-global-popup-accent');
        var icon = document.getElementById('cd-global-popup-icon');
        var title = document.getElementById('cd-global-popup-title');
        var msg = document.getElementById('cd-global-popup-message');
        var btn = document.getElementById('cd-global-popup-btn');
        var closeBtn = document.getElementById('cd-global-popup-close');

        var typeCfg = TYPE_CONFIG[type] || TYPE_CONFIG.info;

        msg.textContent = String(message || '').replace(/\\n/g, '\n');
        title.textContent = typeCfg.title;
        icon.style.background = typeCfg.iconBg;
        icon.innerHTML = typeCfg.icon;
        accent.style.background = 'linear-gradient(90deg, ' + typeCfg.accent + ', rgba(255, 207, 69, 0.35))';

        var previousClose = popup.__cdClosePopup;
        if (typeof previousClose === 'function') {
          previousClose();
        }

        popup.style.opacity = '1';
        popup.style.pointerEvents = 'auto';
        popup.setAttribute('aria-hidden', 'false');
        box.style.transform = 'translateY(0) scale(1)';
        box.style.opacity = '1';
        btn.focus();

        var closed = false;

        var closePopup = function() {
          if (closed) return;
          closed = true;
          popup.style.opacity = '0';
          popup.style.pointerEvents = 'none';
          popup.setAttribute('aria-hidden', 'true');
          box.style.transform = 'translateY(18px) scale(.97)';
          box.style.opacity = '0';
          btn.removeEventListener('click', closePopup);
          closeBtn.removeEventListener('click', closePopup);
          popup.removeEventListener('click', outsideClick);
          document.removeEventListener('keydown', onEsc);
          popup.__cdClosePopup = null;
          if (typeof onClose === 'function') {
            onClose();
          }
        };

        var outsideClick = function(event) {
          if (event.target === popup) closePopup();
        };

        var onEsc = function(event) {
          if (event.key === 'Escape') closePopup();
        };

        popup.__cdClosePopup = closePopup;
        btn.addEventListener('click', closePopup);
        closeBtn.addEventListener('click', closePopup);
        popup.addEventListener('click', outsideClick);
        document.addEventListener('keydown', onEsc);

        if (autoCloseMs > 0) {
          setTimeout(closePopup, autoCloseMs);
        }

        return closePopup;
      };

      window.alert = function(msg) {
        window.__cdShowPopup(String(msg || ''), 'info');
      };
    }

    window.__cdShowPopup(
      cfg.message,
      cfg.type,
      function() {
        if (cfg.redirect === '__HISTORY_BACK__') {
          window.history.back();
        } else if (cfg.redirect) {
          window.location.href = cfg.redirect;
        }
      },
      cfg.autoCloseMs
    );
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
JS;

        $script = str_replace('__PAYLOAD__', $payload, $script);
        echo '<script>' . $script . '</script>';
    }
}
