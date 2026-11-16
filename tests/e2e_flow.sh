#!/bin/bash
# End-to-end (black-box) test: drives the running website with curl like a real user would
# (register, buy, confirm, review, download, sell, admin, security checks).
#
# Usage (Linux/macOS/Git Bash, with the app on SQLite and the server running):
#   php -S 127.0.0.1:8000 -t public public/index.php
#   php cli/migrate.php --fresh --seed
#   bash tests/e2e_flow.sh http://127.0.0.1:8000
B=${1:-http://127.0.0.1:8000}
ROOTP=${ROOTP:-$(cd "$(dirname "$0")/.." && pwd)}; DBP=${DBP:-$ROOTP/storage/database.sqlite}; HOSTB=${HOSTB:-$B}
J=/tmp/lv_e2e_jar; rm -f $J $J.*
pass=0; fail=0
# small sqlite3 stand-in so the script only needs PHP + curl
sqlite3(){ php -r '$p=new PDO("sqlite:".$argv[1]); foreach($p->query($argv[2])->fetchAll(PDO::FETCH_NUM) as $r) echo implode("|",$r),"\n";' "$1" "$2"; }
ok(){ if [ "$1" = "1" ]; then echo "  PASS  $2"; pass=$((pass+1)); else echo "  FAIL  $2"; fail=$((fail+1)); fi; }
tok(){ curl -s -b $1 -c $1 "$B$2" | grep -o 'name="_csrf" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//'; }
code(){ curl -s -o /tmp/lv_e2e_last.html -w "%{http_code}" "$@"; }
has(){ grep -q "$1" /tmp/lv_e2e_last.html && echo 1 || echo 0; }

echo "== Auth"
U="tester$RANDOM"
T=$(tok $J /register)
c=$(code -b $J -c $J -X POST "$B/register" -d "_csrf=$T&username=$U&email=$U@mail.test&password=short&password_confirmation=short" -H "Referer: $B/register")
ok $([ "$c" = 302 ] && echo 1 || echo 0) "short password rejected (redirect back)"
curl -s -b $J -c $J "$B/register" > /tmp/lv_e2e_last.html; ok $(has "at least 8 characters") "validation message shown"
c=$(code -b $J -c $J -X POST "$B/register" -d "_csrf=$T&username=$U&email=$U@mail.test&password=secret123&password_confirmation=secret123")
ok $([ "$c" = 302 ] && echo 1 || echo 0) "register succeeds"
curl -s -b $J -c $J "$B/" > /tmp/lv_e2e_last.html; ok $(has "Account created") "logged in after register + flash"
c=$(code -b $J -c $J "$B/login"); ok $([ "$c" = 302 ] && echo 1 || echo 0) "guest middleware: /login redirects when logged in"

echo "== CSRF"
c=$(code -b $J -c $J -X POST "$B/cart/add/12" -d "quantity=1"); ok $([ "$c" = 419 ] && echo 1 || echo 0) "POST without token → 419"

echo "== Cart & checkout"
T=$(tok $J /items/warzone-2-400-cod-points-top-up-12)
c=$(code -b $J -c $J -X POST "$B/cart/add/12" -d "_csrf=$T&quantity=2" -H "Referer: $B/browse"); ok $([ "$c" = 302 ] && echo 1 || echo 0) "add to cart"
c=$(code -b $J -c $J -X POST "$B/cart/add/15" -d "_csrf=$T&quantity=1" -H "Referer: $B/browse")
curl -s -b $J -c $J "$B/cart" > /tmp/lv_e2e_last.html; ok $(has "COD Points") "cart lists item"; ok $(has "Cyberpunk UI Kit") "cart lists 2nd item"
CID=$(grep -o '/cart/update/[0-9]*' /tmp/lv_e2e_last.html | head -1 | grep -o '[0-9]*$')
c=$(code -b $J -c $J -X POST "$B/cart/update/$CID" -d "_csrf=$T&quantity=3"); curl -s -b $J -c $J "$B/cart" > /tmp/lv_e2e_last.html
ok $(grep -q 'name="quantity" value="3"' /tmp/lv_e2e_last.html && echo 1 || echo 0) "update quantity"
curl -s -b $J -c $J "$B/checkout" > /tmp/lv_e2e_last.html; ok $(has "₱") "checkout shows peso prices"; ok $(has "GCash") "GCash option shown"
c=$(code -b $J -c $J -X POST "$B/checkout" -d "_csrf=$T&payment_method=gcash&account_name=Juan" -H "Referer: $B/checkout"); 
curl -s -b $J -c $J "$B/checkout" > /tmp/lv_e2e_last.html; ok $(has "Please tick the box") "checkout requires agreement"
c=$(code -b $J -c $J -X POST "$B/checkout" -d "_csrf=$T&payment_method=paypal&account_name=Juan&agree=1" -H "Referer: $B/checkout" -o /dev/null; curl -s -b $J -c $J "$B/checkout" > /tmp/lv_e2e_last.html; ok $(has "not a valid option") "unknown payment method rejected"
c=$(code -b $J -c $J -X POST "$B/checkout" -d "_csrf=$T&payment_method=gcash&account_name=Juan&agree=1" -D /tmp/lv_e2e_h.txt))
ORDER=$(grep -i '^location' /tmp/lv_e2e_h.txt | grep -o 'LV-[A-Z0-9-]*')
ok $([ -n "$ORDER" ] && echo 1 || echo 0) "order placed: $ORDER"
curl -s -b $J -c $J "$B/orders/$ORDER" > /tmp/lv_e2e_last.html
ok $(has "CODE: ") "code delivered on order page"; ok $(has "GCASH-") "payment reference shown"; ok $(has "/download") "download link present"
curl -s -b $J -c $J "$B/cart" > /tmp/lv_e2e_last.html; ok $(has "Your cart is empty") "cart emptied after checkout"
LINE_CODE=$(grep -o '/orders/items/[0-9]*/confirm' /tmp/lv_e2e_last.html | head -1)
curl -s -b $J -c $J "$B/orders/$ORDER" > /tmp/lv_e2e_last.html
LINES=$(grep -o '/orders/items/[0-9]*/confirm' /tmp/lv_e2e_last.html | grep -o '[0-9]*' )
DL=$(grep -o '/orders/items/[0-9]*/download' /tmp/lv_e2e_last.html | head -1)
curl -s -b $J -c $J "$B$DL" -o /tmp/lv_e2e_dl.bin -D /tmp/lv_e2e_dh.txt
ok $(grep -qi "attachment" /tmp/lv_e2e_dh.txt && unzip -l /tmp/lv_e2e_dl.bin >/dev/null 2>&1 && echo 1 || echo 0) "file download is a valid ZIP"
STOCK=$(curl -s "$B/api/items?q=COD%20Points" | grep '"stock"' | grep -o '[0-9]*')
ok $([ "$STOCK" = 96 ] && echo 1 || echo 0) "stock reduced 99 → $STOCK"

echo "== Confirm & review"
FIRST=$(echo "$LINES" | head -1)
c=$(code -b $J -c $J -X POST "$B/orders/items/$FIRST/review" -d "_csrf=$T&rating=5")
curl -s -b $J -c $J "$B/orders/$ORDER" > /tmp/lv_e2e_last.html; ok $(has "confirm delivery before") "can't review before confirming"
SB=$(sqlite3 $DBP "select balance from users where username='NovaTrades'")
c=$(code -b $J -c $J -X POST "$B/orders/items/$FIRST/confirm" -d "_csrf=$T")
SA=$(sqlite3 $DBP "select balance from users where username='NovaTrades'")
EXP=$(python3 -c "print(round($SB + 999*3*0.9, 2))")
ok $(python3 -c "print(1 if abs($SA-$EXP)<0.01 else 0)") "seller paid 90%: $SB → $SA (expected $EXP)"
c=$(code -b $J -c $J -X POST "$B/orders/items/$FIRST/confirm" -d "_csrf=$T")
SA2=$(sqlite3 $DBP "select balance from users where username='NovaTrades'")
ok $([ "$SA" = "$SA2" ] && echo 1 || echo 0) "double confirm does not pay twice"
c=$(code -b $J -c $J -X POST "$B/orders/items/$FIRST/review" -d "_csrf=$T&rating=4&comment=Great+test+item")
curl -s -b $J -c $J "$B/items/warzone-2-400-cod-points-top-up-12" > /tmp/lv_e2e_last.html; ok $(has "Great test item") "review shows on item page"
c=$(code -b $J -c $J -X POST "$B/orders/items/$FIRST/review" -d "_csrf=$T&rating=1&comment=again")
curl -s -b $J -c $J "$B/orders/$ORDER" > /tmp/lv_e2e_last.html; ok $(has "already reviewed") "only one review per item"

echo "== Ownership / access control"
J2=$J.b; T2=$(tok $J2 /login)
c=$(code -b $J2 -c $J2 -X POST "$B/login" -d "_csrf=$T2&email=buyer@lootvault.test&password=wrong" -H "Referer: $B/login")
curl -s -b $J2 -c $J2 "$B/login" > /tmp/lv_e2e_last.html; ok $(has "do not match") "wrong password rejected"
c=$(code -b $J2 -c $J2 -X POST "$B/login" -d "_csrf=$T2&email=buyer@lootvault.test&password=password"); ok $([ "$c" = 302 ] && echo 1 || echo 0) "buyer login"
T2=$(tok $J2 /)
c=$(code -b $J2 -c $J2 "$B/orders/$ORDER"); ok $([ "$c" = 404 ] && echo 1 || echo 0) "other user can't view my order (404)"
c=$(code -b $J2 -c $J2 "$B$DL"); ok $([ "$c" = 404 ] && echo 1 || echo 0) "other user can't download my file (404)"
c=$(code -b $J2 -c $J2 "$B/admin"); ok $([ "$c" = 403 ] && echo 1 || echo 0) "non-admin blocked from /admin (403)"
c=$(code -b $J2 -c $J2 "$B/sell/1/edit"); ok $([ "$c" = 403 ] && echo 1 || echo 0) "can't edit someone else's listing (403)"
c=$(code "$B/storage/database.sqlite"); ok $([ "$c" = 404 ] && echo 1 || echo 0) "database file not web-accessible"

echo "== Selling"
printf 'fake zip content' > /tmp/lv_e2e_pack.zip
T=$(tok $J /sell/new)
c=$(code -b $J -c $J -X POST "$B/sell" -F "_csrf=$T" -F "title=Hi" -F "category_id=5" -F "price=0" -F "stock=1" -F "delivery_type=code" -F "description=short" -H "Referer: $B/sell/new")
curl -s -b $J -c $J "$B/sell/new" > /tmp/lv_e2e_last.html; ok $(has "at least 5 characters") "listing validation errors shown"
c=$(code -b $J -c $J -X POST "$B/sell" -F "_csrf=$T" -F "title=Test Sprite Pack Deluxe" -F "category_id=5" -F "game=" -F "price=150" -F "stock=10" -F "delivery_type=file" -F "game_id=" -F "description=Twenty hand drawn sprites for testing purposes." -F "image=@$ROOTP/public/uploads/seed-16.jpg;type=image/jpeg" -F "digital_file=@/tmp/lv_e2e_pack.zip" -D /tmp/lv_e2e_h.txt)
NEWURL=$(grep -i '^location' /tmp/lv_e2e_h.txt | tr -d '\r' | awk '{print $2}')
ok $(echo "$NEWURL" | grep -q "/items/test-sprite-pack-deluxe" && echo 1 || echo 0) "create listing with image + file upload"
curl -s -b $J -c $J "$HOSTB$NEWURL" > /tmp/lv_e2e_last.html; ok $(has "Test Sprite Pack Deluxe") "new listing page renders"
IMG=$(grep -o 'uploads/[a-f0-9]*\.jpg' /tmp/lv_e2e_last.html | head -1); ok $([ -n "$IMG" ] && [ "$(curl -s -o /dev/null -w '%{http_code}' $B/$IMG)" = 200 ] && echo 1 || echo 0) "uploaded image served"
ok $(ls $ROOTP/storage/files/*.bin | grep -vq seed && echo 1 || echo 0) "digital file stored outside public/"
c=$(code -b $J -c $J -X POST "$B/sell" -F "_csrf=$T" -F "title=Evil upload test" -F "category_id=5" -F "price=150" -F "stock=1" -F "delivery_type=code" -F "delivery_content=x" -F "description=This should fail because the image is a php file." -F "image=@$ROOTP/public/index.php;type=image/jpeg" -H "Referer: $B/sell/new")
curl -s -b $J -c $J "$B/sell/new" > /tmp/lv_e2e_last.html; ok $(has "must be a JPG") "non-image upload rejected"
NID=$(sqlite3 $DBP "select id from items where title='Test Sprite Pack Deluxe'")
c=$(code -b $J -c $J -X POST "$B/sell/$NID" -F "_csrf=$T" -F "title=Test Sprite Pack Deluxe v2" -F "category_id=5" -F "game_id=452" -F "price=200" -F "stock=0" -F "delivery_type=file" -F "description=Twenty hand drawn sprites for testing purposes." -F "status=active")
ST=$(sqlite3 $DBP "select title||'|'||price||'|'||status||'|'||game_id from items where id=$NID")
ok $([ "$ST" = "Test Sprite Pack Deluxe v2|200|sold_out|452" ] && echo 1 || echo 0) "edit listing: price, game link, stock 0 → sold_out ($ST)"
curl -s -b $J -c $J "$B/sell" > /tmp/lv_e2e_last.html; ok $(has "My listings (1)") "seller dashboard shows listing"
c=$(code -b $J -c $J -X POST "$B/sell/$NID/delete" -d "_csrf=$T")
ok $([ "$(sqlite3 $DBP "select status from items where id=$NID")" = removed ] && echo 1 || echo 0) "delete = soft delete (status removed)"
c=$(code -b $J -c $J -X POST "$B/cart/add/12" -d "_csrf=$T&quantity=500" -H "Referer: $B/"); 
Q=$(sqlite3 $DBP "select quantity from cart_items ci join users u on u.id=ci.user_id where u.username='$U'")
ok $([ "$Q" = 96 ] && echo 1 || echo 0) "cart quantity capped at stock ($Q)"

echo "== Admin"
J3=$J.a; T3=$(tok $J3 /login)
code -b $J3 -c $J3 -X POST "$B/login" -d "_csrf=$T3&email=admin@lootvault.test&password=password" >/dev/null; T3=$(tok $J3 /admin)
for p in /admin /admin/users /admin/items /admin/categories /admin/orders; do c=$(code -b $J3 -c $J3 "$B$p"); ok $([ "$c" = 200 ] && echo 1 || echo 0) "admin page $p"; done
UID2=$(sqlite3 $DBP "select id from users where username='$U'")
code -b $J3 -c $J3 -X POST "$B/admin/users/$UID2/ban" -d "_csrf=$T3" >/dev/null
c=$(code -b $J -c $J "$B/orders"); ok $([ "$c" = 302 ] && echo 1 || echo 0) "banned user is logged out"
T=$(tok $J /login); code -b $J -c $J -X POST "$B/login" -d "_csrf=$T&email=$U@mail.test&password=secret123" > /dev/null
curl -s -b $J -c $J "$B/login" > /tmp/lv_e2e_last.html; ok $(has "do not match") "banned user can't log in"
code -b $J3 -c $J3 -X POST "$B/admin/users/$UID2/ban" -d "_csrf=$T3" >/dev/null
code -b $J3 -c $J3 -X POST "$B/admin/categories" -d "_csrf=$T3&name=Mobile+Games&slug=mobile-games&icon=box&description=Test" >/dev/null
ok $(curl -s "$B/" | grep -q "Mobile Games" && echo 1 || echo 0) "admin adds category (appears in nav)"
CATID=$(sqlite3 $DBP "select id from categories where slug='mobile-games'")
code -b $J3 -c $J3 -X POST "$B/admin/categories/$CATID/delete" -d "_csrf=$T3" >/dev/null
ok $(curl -s "$B/" | grep -q "Mobile Games" && echo 0 || echo 1) "admin deletes empty category"
code -b $J3 -c $J3 -X POST "$B/admin/categories/1/delete" -d "_csrf=$T3" >/dev/null
ok $([ "$(sqlite3 $DBP "select count(*) from categories where id=1")" = 1 ] && echo 1 || echo 0) "can't delete category that has listings"
code -b $J3 -c $J3 -X POST "$B/admin/items/3/status" -d "_csrf=$T3&status=paused" >/dev/null
ok $(curl -s "$B/browse?category=game-accounts" | grep -q "Fresh Server Account" && echo 0 || echo 1) "paused listing hidden from browse"
code -b $J3 -c $J3 -X POST "$B/admin/items/3/status" -d "_csrf=$T3&status=active" >/dev/null
c=$(code -b $J3 -c $J3 -X POST "$B/admin/games/sync" -d "_csrf=$T3"); curl -s -b $J3 -c $J3 "$B/admin" > /tmp/lv_e2e_last.html
ok $(has "FreeToGame API") "admin games sync handled"
code -b $J3 -c $J3 "$B/orders/$ORDER" >/dev/null; ok $(has "$ORDER") "admin can view any order"
echo "== Games (FreeToGame)"
c=$(code "$B/games"); ok $([ "$c" = 200 ] && has "Call of Duty: Warzone" >/dev/null && echo 1 || echo 0) "games page lists catalog"
c=$(code "$B/games?genre=Strategy"); ok $(grep -q "Forge of Empires" /tmp/lv_e2e_last.html && ! grep -q "Overwatch</h3>" /tmp/lv_e2e_last.html && echo 1 || echo 0) "genre filter"
c=$(code "$B/games/452"); ok $(has "Minimum requirements" ) "game page"; ok $(has "Processor") "system requirements shown"; ok $(has "Warzone Account") "game page lists its listings"
c=$(code "$B/browse?game=540"); ok $(has "Overwatch Account") "browse filtered by game"; ok $(grep -q "PUBG Account" /tmp/lv_e2e_last.html && echo 0 || echo 1) "other games excluded"
c=$(code "$B/items/warzone-account-level-155-40-operator-skins-1"); ok $(has "About the game") "item page shows game card"
ok $(curl -s "$B/api/games" | grep -q '"listing_count"' && echo 1 || echo 0) "JSON /api/games"
c=$(code "$B/games/123456"); ok $([ "$c" = 404 ] && echo 1 || echo 0) "unknown game → 404"
T=$(tok $J /login); code -b $J -c $J -X POST "$B/login" -d "_csrf=$T&email=$U@mail.test&password=secret123" >/dev/null
T=$(tok $J /sell/new); curl -s -b $J -c $J "$B/sell/new?game=582" > /tmp/lv_e2e_last.html; ok $(grep -q 'value="582" selected' /tmp/lv_e2e_last.html && echo 1 || echo 0) "Sell-for-this-game preselects game"
c=$(code -b $J -c $J -X POST "$B/sell" -F "_csrf=$T" -F "title=Bad game id test" -F "category_id=1" -F "game_id=999999" -F "price=150" -F "stock=1" -F "delivery_type=code" -F "delivery_content=x" -F "description=Testing that an unknown game id is rejected." -H "Referer: $B/sell/new")
curl -s -b $J -c $J "$B/sell/new" > /tmp/lv_e2e_last.html; ok $(has "does not exist") "unknown game id rejected"
c=$(code -b $J -c $J -X POST "$B/sell" -F "_csrf=$T" -F "title=Too cheap test item" -F "category_id=1" -F "price=5" -F "stock=1" -F "delivery_type=code" -F "delivery_content=x" -F "description=Testing that the minimum peso price is enforced." -H "Referer: $B/sell/new")
curl -s -b $J -c $J "$B/sell/new" > /tmp/lv_e2e_last.html; ok $(has "at least 20") "minimum price ₱20 enforced"
echo; echo "RESULT: $pass passed, $fail failed"
