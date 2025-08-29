
## UTILIZADOS PARA LOCALHOST COM CONEXÃO NO SERVIDOR DE TESTE
###############################################################

server=188.166.42.22
APP_TYPE=ss3
APP_KEY=base64:rCQHraEecQ5TfzJ5LLuxOzwUuyKWHnfranpBeusA0rI=
key=
DB_HOST=188.166.42.22
DB_USERNAME=root
DB_PASSWORD=03072006
admin_user=03072006
FCM_SENDER_ID=
FCM_SERVER_KEY=
APP_DEBUG=true
##Mari0307@
##gpstracker_@hotmail.com
##php artisan config:cache

###############################################################

## PHP VERSÃO SOMENTE 7.4
## BANCO DE DADOS MYSQL
## LARAVEL PHP
## INSTALAR COMPOSER

## DEPEDENCIAS PHP.INI E PHP 7.4 PRECISAM SER INSTALADAS
-- todas as depências exigidas no laravel neste projeto
-- executar composer u para conhecer as depedências


## REDIS-SERVER  INSTALL UBUNTU 22



## ALTERAÇÕES FEITAS

-- USUÁRIOS QUERY
-- VEICULOS QUERY
-- LOGO
-- LOGIN LAYOUT

## COMPILAR PROJETO // REVISAR

cd /var/www/html/current
composer update
php artisan migrate
sudo systemctl restart httpd.service

php artisan config:clear && php artisan cache:clear && php artisan view:clear && composer run-script post-update-cmd

php artisan config:clear && php artisan cache:clear && php artisan view:clear && php artisan config:cache

php artisan config:cache
