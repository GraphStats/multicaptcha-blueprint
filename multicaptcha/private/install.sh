#!/bin/bash
set -euo pipefail

ROOT="${PTERODACTYL_DIRECTORY:-$(pwd)}"
EXT_ID="${EXTENSION_IDENTIFIER:-multicaptcha}"
EXT_DIR="${ROOT}/.blueprint/extensions/${EXT_ID}/private"
PATCH_DIR="${EXT_DIR}/patches"
BACKUP_DIR="${EXT_DIR}/.store/original"

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

echo "[multicaptcha] Applying panel patches..."

for file in "${FILES[@]}"; do
  src="${PATCH_DIR}/${file}"
  dst="${ROOT}/${file}"
  bkp="${BACKUP_DIR}/${file}"

  if [[ ! -f "${src}" ]]; then
    echo "[multicaptcha] Missing patch file: ${src}" >&2
    exit 1
  fi

  mkdir -p "$(dirname "${bkp}")"
  mkdir -p "$(dirname "${dst}")"

  if [[ -f "${dst}" ]] && [[ ! -f "${bkp}" ]]; then
    cp "${dst}" "${bkp}"
  fi

  cp "${src}" "${dst}"
  echo "[multicaptcha] Patched ${file}"
done

echo "[multicaptcha] Patch completed."
