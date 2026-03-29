## Hostinger Deploy

Bu proje Hostinger paylasimli hostingte `public_html` klasorunden dogrudan servis ediliyor. Laravel ise tarayici assetlerini `public/` altina urettigi icin deploy sonrasi `public/` icerigi web kokune kopyalanmak zorunda.

Temel kural:

```text
Sunucuda rsync kullanma.
Deploy her zaman SSH ile public_html icinde git pull yapilarak ilerler.
```

Canliya gondermeden once lokal makinede:

```bash
npm ci
npm run build
git add public/build
git commit -m "Update production assets"
git push origin main
```

Gercek uygulama yolu:

```bash
~/domains/cornflowerblue-ram-353200.hostingersite.com/public_html
```

Sunucuda deploy:

```bash
cd ~/domains/cornflowerblue-ram-353200.hostingersite.com/public_html
git pull origin main
/opt/alt/php84/usr/bin/php /usr/local/bin/composer install --no-dev --optimize-autoloader
/opt/alt/php84/usr/bin/php artisan migrate --force
/opt/alt/php84/usr/bin/php artisan optimize
/opt/alt/php84/usr/bin/php artisan queue:restart
sh scripts/hostinger_post_deploy.sh
```

`scripts/hostinger_post_deploy.sh` su isleri yapar:

```text
public/build      -> ./build
public/manifest   -> ./manifest.webmanifest
public/sw.js      -> ./sw.js
public/robots.txt -> ./robots.txt
public/favicon    -> ./favicon.ico
public/.htaccess  -> ./.htaccess
```

Kontrol:

```bash
cat build/manifest.json
ls build/assets
curl -I https://cornflowerblue-ram-353200.hostingersite.com/build/assets/<manifestteki-dosya>
```

`.htaccess` dosyasinin en ustunde su satirlar bulunmalidir:

```apache
<FilesMatch "\.(php4|php5|php3|php2|php|phtml)$">
    SetHandler application/x-lsphp84
</FilesMatch>
```
