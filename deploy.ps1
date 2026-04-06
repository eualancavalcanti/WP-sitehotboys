#!/usr/bin/env pwsh
# Deploy para Hostinger via SSH
git add -A
$msg = if ($args[0]) { $args[0] } else { "update theme" }
git commit -m $msg
git push origin main
ssh -p 65002 u466684076@147.93.37.39 "cd ~/domains/sitehotboys.com/public_html/wp-content/themes/hotboys-theme && git fetch origin && git reset --hard origin/main"
Write-Host "Deploy concluido!" -ForegroundColor Green
