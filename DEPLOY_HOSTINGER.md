## Hostinger Deploy

Bu proje Hostinger paylasimli hostingte `npm` olmadan calistigi icin `public/build` dosyalari repoya dahil edilmelidir.

Canliya gondermeden once lokal makinede:

```bash
npm ci
npm run build
git add public/build
git commit -m "Update production assets"
git push origin main
```

Sunucuda deploy:

```bash
cd ~/domains/cornflowerblue-ram-353200.hostingersite.com/public_html
git pull origin main
rm -f .htaccess.bak .htaccess.hostinger-backup
/opt/alt/php83/usr/bin/php /usr/local/bin/composer install --no-dev --optimize-autoloader
/opt/alt/php83/usr/bin/php artisan optimize:clear
/opt/alt/php83/usr/bin/php artisan migrate --force
/opt/alt/php83/usr/bin/php artisan optimize
/opt/alt/php83/usr/bin/php artisan queue:restart
```

`.htaccess` dosyasinin en ustunde su satirlar bulunmalidir:

```apache
<FilesMatch "\.(php4|php5|php3|php2|php|phtml)$">
    SetHandler application/x-lsphp84
</FilesMatch>
```
