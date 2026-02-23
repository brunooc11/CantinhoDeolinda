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

        echo "<script>(function(){"
            . "var cfg={$payload};"
            . "var run=function(){"
            . "if(!window.__cdShowPopup){"
            . "window.__cdShowPopup=function(message,type,onClose,autoCloseMs){"
            . "type=type||'info';"
            . "autoCloseMs=Number(autoCloseMs||0);"
            . "var id='cd-global-popup';"
            . "var popup=document.getElementById(id);"
            . "if(!popup){"
            . "popup=document.createElement('div');"
            . "popup.id=id;"
            . "popup.style.cssText='position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.45);z-index:99999;opacity:0;pointer-events:none;transition:opacity .2s ease';"
            . "popup.innerHTML='<div id=\"cd-global-popup-box\" style=\"width:min(92vw,420px);border-radius:16px;border:1px solid rgba(244,185,66,.4);background:linear-gradient(170deg,#171717,#101010);box-shadow:0 20px 40px rgba(0,0,0,.5);padding:18px 18px 16px;transform:translateY(12px) scale(.98);transition:transform .22s ease\"><div id=\"cd-global-popup-icon\" style=\"width:42px;height:42px;border-radius:50%;margin-bottom:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:18px\"></div><p id=\"cd-global-popup-message\" style=\"color:#f3e9d3;font-size:.98rem;line-height:1.45;margin:0 0 14px\"></p><button id=\"cd-global-popup-btn\" type=\"button\" style=\"border:0;border-radius:999px;padding:9px 16px;font-weight:600;cursor:pointer;color:#121212;background:linear-gradient(135deg,#f5a623,#ffcc33)\">Fechar</button></div>';"
            . "document.body.appendChild(popup);"
            . "}"
            . "var box=document.getElementById('cd-global-popup-box');"
            . "var icon=document.getElementById('cd-global-popup-icon');"
            . "var msg=document.getElementById('cd-global-popup-message');"
            . "var btn=document.getElementById('cd-global-popup-btn');"
            . "msg.textContent=message||'';"
            . "if(type==='success'){icon.style.background='#1f8f55';icon.textContent='✓';}"
            . "else if(type==='error'){icon.style.background='#b03a3a';icon.textContent='!';}"
            . "else{icon.style.background='#555';icon.textContent='i';}"
            . "popup.style.opacity='1';popup.style.pointerEvents='auto';"
            . "box.style.transform='translateY(0) scale(1)';"
            . "var closed=false;"
            . "var closePopup=function(){if(closed){return;}closed=true;popup.style.opacity='0';popup.style.pointerEvents='none';box.style.transform='translateY(12px) scale(.98)';btn.removeEventListener('click',closePopup);popup.removeEventListener('click',outsideClick);if(typeof onClose==='function'){onClose();}};"
            . "var outsideClick=function(e){if(e.target===popup){closePopup();}};"
            . "btn.addEventListener('click',closePopup);"
            . "popup.addEventListener('click',outsideClick);"
            . "if(autoCloseMs>0){setTimeout(closePopup,autoCloseMs);}"
            . "return closePopup;"
            . "};"
            . "window.alert=function(msg){window.__cdShowPopup(String(msg||''),'info');};"
            . "}"
            . "window.__cdShowPopup(cfg.message,cfg.type,function(){if(cfg.redirect==='__HISTORY_BACK__'){window.history.back();}else if(cfg.redirect){window.location.href=cfg.redirect;}},cfg.autoCloseMs);"
            . "};"
            . "if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',run);}else{run();}"
            . "})();</script>";
    }
}
