#!/usr/bin/env bash
# Upotreba: bash tests/seo/check-head.sh http://yugovote.local:8080 [--sections head,robots]
exec python3 -I "$(dirname "$0")/check_head.py" "$@"
