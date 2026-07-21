#!/bin/bash

DATA=$(cat <<'EOF'
id user city phone
1 test Moscow 1234123
2 test2 Saint-P 1232121
3 test3 Tver 4352124
4 test4 Milan 7990923
5 test5 Moscow 908213
EOF
)

echo "$DATA" | awk 'NR>1 {print $3}' | LC_ALL=C sort | uniq -c | LC_ALL=C sort -rn | head -3
