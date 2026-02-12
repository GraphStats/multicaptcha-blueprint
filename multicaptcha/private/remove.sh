#!/bin/bash
set -euo pipefail

ROOT="${PTERODACTYL_DIRECTORY:-$(pwd)}"
EXT_ID="${EXTENSION_IDENTIFIER:-multicaptcha}"
BACKUP_DIR="${ROOT}/.blueprint/extensions/${EXT_ID}/private/.store/original"

FILES=(
  "app/Providers/SettingsServiceProvider.php"
  "config/recaptcha.php"
  "app/Http/Middleware/VerifyReCaptcha.php"
  "app/Http/Requests/Admin/Settings/AdvancedSettingsFormRequest.php"
  "app/Http/ViewComposers/AssetComposer.php"
  "resources/views/admin/settings/advanced.blade.php"
  "resources/scripts/state/settings.ts"
  "resources/scripts/components/auth/LoginContainer.tsx"
  "resources/scripts/components/auth/ForgotPasswordContainer.tsx"
  "resources/scripts/components/elements/CaptchaWidget.tsx"
)

echo "[multicaptcha] Restoring original panel files..."

for file in "${FILES[@]}"; do
  dst="${ROOT}/${file}"
  bkp="${BACKUP_DIR}/${file}"

  if [[ -f "${bkp}" ]]; then
    mkdir -p "$(dirname "${dst}")"
    cp "${bkp}" "${dst}"
    echo "[multicaptcha] Restored ${file}"
  else
    echo "[multicaptcha] No backup for ${file}, skipping"
  fi
done

echo "[multicaptcha] Restore completed."
