<?php

function cd_email_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function cd_email_detail_rows(array $rows): string
{
    if ($rows === []) {
        return '';
    }

    $html = '';
    foreach ($rows as $label => $value) {
        $safeLabel = cd_email_escape((string)$label);
        $safeValue = nl2br(cd_email_escape((string)$value));
        $html .= "
            <tr>
                <td style=\"padding:0 0 12px 0;\">
                    <div style=\"background:#f8f1e7;border:1px solid #eadac3;border-radius:14px;padding:14px 16px;\">
                        <div style=\"font-size:12px;line-height:18px;letter-spacing:0.08em;text-transform:uppercase;color:#8a6a3c;font-weight:700;\">{$safeLabel}</div>
                        <div style=\"margin-top:4px;font-size:15px;line-height:24px;color:#2f2418;\">{$safeValue}</div>
                    </div>
                </td>
            </tr>
        ";
    }

    return "
        <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse:collapse;margin:24px 0 8px;\">
            {$html}
        </table>
    ";
}

function cd_email_template(
    string $eyebrow,
    string $title,
    string $intro,
    string $bodyHtml,
    ?string $ctaLabel = null,
    ?string $ctaUrl = null,
    ?string $footerNote = null
): string {
    $safeEyebrow = cd_email_escape($eyebrow);
    $safeTitle = cd_email_escape($title);
    $safeIntro = nl2br(cd_email_escape($intro));
    $ctaHtml = '';
    $footerHtml = '';

    if ($ctaLabel !== null && $ctaUrl !== null && $ctaLabel !== '' && $ctaUrl !== '') {
        $safeCtaLabel = cd_email_escape($ctaLabel);
        $safeCtaUrl = cd_email_escape($ctaUrl);
        $ctaHtml = "
            <table cellpadding=\"0\" cellspacing=\"0\" style=\"margin:28px 0 24px;\">
                <tr>
                    <td align=\"center\" bgcolor=\"#c58b2a\" style=\"border-radius:999px;\">
                        <a href=\"{$safeCtaUrl}\" style=\"display:inline-block;padding:14px 24px;font-size:14px;line-height:20px;font-weight:700;color:#fffaf2;text-decoration:none;\">{$safeCtaLabel}</a>
                    </td>
                </tr>
            </table>
        ";
    }

    if ($footerNote !== null && $footerNote !== '') {
        $safeFooter = nl2br(cd_email_escape($footerNote));
        $footerHtml = "
            <p style=\"margin:24px 0 0;font-size:13px;line-height:22px;color:#7a6a57;\">{$safeFooter}</p>
        ";
    }

    return "
<!DOCTYPE html>
<html lang=\"pt\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>{$safeTitle}</title>
</head>
<body style=\"margin:0;padding:0;background:#f4ede3;font-family:Arial,Helvetica,sans-serif;\">
    <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse:collapse;background:linear-gradient(180deg,#f4ede3 0%,#efe4d4 100%);\">
        <tr>
            <td align=\"center\" style=\"padding:32px 16px;\">
                <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse:collapse;max-width:640px;\">
                    <tr>
                        <td style=\"padding:0 0 18px 4px;font-size:12px;line-height:18px;letter-spacing:0.18em;text-transform:uppercase;color:#8a6a3c;font-weight:700;\">
                            Cantinho Deolinda
                        </td>
                    </tr>
                    <tr>
                        <td style=\"background:#fffaf4;border:1px solid #eadac3;border-radius:28px;padding:0;overflow:hidden;box-shadow:0 18px 45px rgba(88,56,18,0.12);\">
                            <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse:collapse;\">
                                <tr>
                                    <td style=\"padding:32px 32px 24px;background:linear-gradient(135deg,#2f2418 0%,#4a3821 100%);\">
                                        <div style=\"display:inline-block;padding:6px 12px;border-radius:999px;background:rgba(255,250,242,0.14);font-size:11px;line-height:16px;letter-spacing:0.12em;text-transform:uppercase;color:#f3dfbd;font-weight:700;\">{$safeEyebrow}</div>
                                        <h1 style=\"margin:18px 0 12px;font-size:30px;line-height:36px;color:#fffaf2;font-weight:700;\">{$safeTitle}</h1>
                                        <p style=\"margin:0;font-size:16px;line-height:26px;color:#f3e7d2;\">{$safeIntro}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style=\"padding:32px;\">
                                        <div style=\"font-size:15px;line-height:24px;color:#3f3122;\">
                                            {$bodyHtml}
                                        </div>
                                        {$ctaHtml}
                                        {$footerHtml}
                                    </td>
                                </tr>
                                <tr>
                                    <td style=\"padding:20px 32px;background:#f8f1e7;border-top:1px solid #eadac3;\">
                                        <p style=\"margin:0;font-size:12px;line-height:20px;color:#8a6a3c;\">
                                            &copy; " . date('Y') . " Cantinho Deolinda. Obrigado pela sua preferencia.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>";
}
