#!/usr/bin/env bash
# GuroAssist: read-only check. Prints source only. Changes nothing, reads no database.
# Run from the backend-stub folder:  bash check-options-and-trashed.sh > check-output.txt 2>&1

C=app/Http/Controllers/Api

show_fn() {
  # show_fn <file> <function name>: prints the whole method body (4-space indented PHP class)
  local file="$1" fn="$2"
  echo "----- $file :: $fn()"
  if [ ! -f "$file" ]; then echo "FILE NOT FOUND: $file"; return; fi
  awk -v fn="function $fn" '
    index($0, fn) { f = 1 }
    f { print NR ": " $0 }
    f && /^    }$/ { exit }
  ' "$file"
  if ! grep -q "function $fn" "$file"; then echo "(no function named $fn in this file)"; fi
}

echo "=== 1. The options list: which key holds the methods? ==="
show_fn "$C/InterventionController.php" options

echo
echo "=== 2. Where METHODS is referenced in app/ ==="
grep -rn "METHODS" app | grep -v "^app/Support/InterventionVocabulary.php"

echo
echo "=== 3. index() of the notes controller ==="
show_fn "$C/StudentNoteController.php" index

echo
echo "=== 4. index() of the parent communications controller ==="
show_fn "$C/ParentCommunicationController.php" index

echo
echo "=== 5. index() of the interventions controller (for comparison) ==="
show_fn "$C/InterventionController.php" index

echo
echo "=== 6. Every mention of trashed in controllers, services, requests ==="
grep -rn -i "trashed" app/Http app/Services

echo
echo "=== 7. Done ==="
